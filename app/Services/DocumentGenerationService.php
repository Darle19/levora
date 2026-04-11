<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDocument;
use App\Models\Order;
use App\Models\Tourist;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentGenerationService
{
    public function __construct(
        private DocumentDataResolver $dataResolver,
        private NeoInsuranceService $neoInsurance,
    ) {}

    public function generateAllForOrder(Order $order): void
    {
        $order->loadMissing('bookings.tourists');

        foreach ($order->bookings as $booking) {
            $this->generateForBooking($booking);
        }
    }

    public function generateForBooking(Booking $booking): void
    {
        if ($booking->documents()->exists()) {
            return;
        }

        try {
            $data = $this->dataResolver->resolve($booking);
        } catch (\Exception $e) {
            Log::error('DocumentDataResolver failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        // Tourist Voucher — always
        $this->generateTouristVoucher($booking, $data);

        // eTicket — one per tourist (FlightPath only)
        if ($data['type'] === 'flight_path' && $data['flights']->isNotEmpty()) {
            foreach ($booking->tourists as $tourist) {
                $this->generateETicket($booking, $data, $tourist);
            }
        }

        // Insurance — register policy in NeoInsurance (get PTN + payment links)
        // but do NOT generate PDF yet — admin must pay first, then click "Generate"
        if (! empty($booking->insurance_risks) && $this->neoInsurance->isConfigured()) {
            $this->registerInsurancePolicy($booking);
        }
    }

    /**
     * Register insurance policy in NeoInsurance API.
     * Saves PTN + payment links to booking document metadata.
     * PDF is NOT generated — admin pays first, then uses "Check & Generate" button.
     */
    private function registerInsurancePolicy(Booking $booking): void
    {
        $neoResult = $this->neoInsurance->createPolicyForBooking($booking);
        if (! $neoResult || empty($neoResult['order_id'])) {
            Log::warning('NeoInsurance policy registration failed', ['booking_id' => $booking->id]);
            return;
        }

        // Store a placeholder document with payment links (no file yet)
        BookingDocument::create([
            'booking_id' => $booking->id,
            'type' => 'insurance',
            'file_path' => '',
            'metadata' => [
                'neo_order_id' => $neoResult['order_id'],
                'neo_click_url' => $neoResult['click'] ?? null,
                'neo_payme_url' => $neoResult['payme'] ?? null,
                'status' => 'pending_payment',
            ],
        ]);
    }

    /**
     * Check NeoInsurance payment status and generate insurance PDFs.
     * Called from admin "Check & Generate" button.
     *
     * @return string Status message
     */
    public function checkAndGenerateInsurance(Booking $booking): string
    {
        $booking->loadMissing(['tourists', 'order']);

        // Find pending insurance document
        $insuranceDoc = $booking->documents()
            ->where('type', 'insurance')
            ->whereJsonContains('metadata->status', 'pending_payment')
            ->first();

        if (! $insuranceDoc) {
            // No pending insurance — maybe not registered yet
            if (empty($booking->insurance_risks)) {
                return 'No insurance risks selected.';
            }

            // Try registering now
            $this->registerInsurancePolicy($booking);
            $insuranceDoc = $booking->documents()
                ->where('type', 'insurance')
                ->whereJsonContains('metadata->status', 'pending_payment')
                ->first();

            if (! $insuranceDoc) {
                return 'Failed to register policy in NeoInsurance.';
            }

            return 'Policy registered. Pay via Click/Payme links, then check again.';
        }

        $neoOrderId = $insuranceDoc->metadata['neo_order_id'] ?? null;
        if (! $neoOrderId) {
            return 'No NeoInsurance order ID found.';
        }

        // Check payment status
        $checkResult = $this->neoInsurance->checkPolicyStatus($neoOrderId);
        if (! $checkResult) {
            return 'Failed to check policy status.';
        }

        $errorCode = $checkResult['response']['error_code'] ?? null;

        // error_code 1 = not paid, 0 = paid (or null = paid)
        if ($errorCode === 1) {
            return 'Insurance not yet paid. Please pay via Click/Payme links first.';
        }

        // Paid — generate PDFs
        try {
            $data = $this->dataResolver->resolve($booking);
        } catch (\Exception $e) {
            return 'Failed to resolve booking data: ' . $e->getMessage();
        }

        // Delete placeholder document
        $metadata = $insuranceDoc->metadata;
        $insuranceDoc->delete();

        // Generate insurance PDF for each tourist
        foreach ($booking->tourists as $tourist) {
            $policyData = array_merge($data, [
                'tourist' => $tourist,
                'neo_order_id' => $neoOrderId,
                'insurance_risks' => $booking->insurance_risks,
            ]);

            $pdf = Pdf::loadView('documents.insurance-policy', $policyData);
            $path = $this->storePdf($pdf, $booking->order->id, "insurance_{$booking->id}_{$tourist->id}");

            BookingDocument::create([
                'booking_id' => $booking->id,
                'type' => 'insurance',
                'tourist_id' => $tourist->id,
                'file_path' => $path,
                'metadata' => array_merge($metadata, ['status' => 'paid']),
            ]);
        }

        return 'Insurance paid! ' . $booking->tourists->count() . ' policy PDF(s) generated.';
    }

    private function generateTouristVoucher(Booking $booking, array $data): void
    {
        $pdf = Pdf::loadView('documents.tourist-voucher', $data);
        $path = $this->storePdf($pdf, $booking->order->id, "tourist_voucher_{$booking->id}");

        BookingDocument::create([
            'booking_id' => $booking->id,
            'type' => 'tourist_voucher',
            'file_path' => $path,
        ]);
    }

    private function generateHotelVoucher(Booking $booking, array $data, object $hotelStay, int $index): void
    {
        $pdf = Pdf::loadView('documents.hotel-voucher', array_merge($data, [
            'hotelStay' => $hotelStay,
        ]));
        $path = $this->storePdf($pdf, $booking->order->id, "hotel_voucher_{$booking->id}_{$index}");

        BookingDocument::create([
            'booking_id' => $booking->id,
            'type' => 'hotel_voucher',
            'file_path' => $path,
        ]);
    }

    private function generateETicket(Booking $booking, array $data, Tourist $tourist): void
    {
        $pdf = Pdf::loadView('documents.eticket', array_merge($data, [
            'tourist' => $tourist,
        ]));
        $path = $this->storePdf($pdf, $booking->order->id, "eticket_{$booking->id}_{$tourist->id}");

        BookingDocument::create([
            'booking_id' => $booking->id,
            'type' => 'eticket',
            'tourist_id' => $tourist->id,
            'file_path' => $path,
        ]);
    }

    private function storePdf($pdf, int $orderId, string $filename): string
    {
        $path = "documents/{$orderId}/{$filename}.pdf";
        Storage::disk('local')->put($path, $pdf->output());
        return $path;
    }
}
