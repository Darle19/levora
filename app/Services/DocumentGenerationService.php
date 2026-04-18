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

        // Insurance — register policy in NeoInsurance; generate PDF immediately if police_number returned
        if (! empty($booking->insurance_risks) && $this->neoInsurance->isConfigured()) {
            $this->registerInsurancePolicy($booking, $data);
        }
    }

    /**
     * Register insurance policy in NeoInsurance API.
     *
     * save-polis returns one of two response shapes:
     *  - Payment required: {order_id, contract_id, url (Click pay link), payme_url}
     *  - No-payment access: {order_id, url (view_api link), police_number}
     */
    private function registerInsurancePolicy(Booking $booking, array $data): void
    {
        $neoResult = $this->neoInsurance->createPolicyForBooking($booking);
        if (! $neoResult || empty($neoResult['order_id'])) {
            Log::warning('NeoInsurance policy registration failed', ['booking_id' => $booking->id]);
            return;
        }

        $policeNumber = $neoResult['police_number'] ?? null;
        $isPaymentFlow = ! empty($neoResult['contract_id']) || ! empty($neoResult['payme_url']);

        // If police_number was issued immediately, generate PDFs per tourist right away
        if ($policeNumber && ! $isPaymentFlow) {
            $metadata = [
                'neo_order_id' => $neoResult['order_id'],
                'police_number' => $policeNumber,
                'neo_view_url' => $neoResult['url'] ?? null,
                'status' => 'issued',
            ];

            foreach ($booking->tourists as $tourist) {
                $policyData = array_merge($data, [
                    'tourist' => $tourist,
                    'neo_order_id' => $neoResult['order_id'],
                    'police_number' => $policeNumber,
                    'insurance_risks' => $booking->insurance_risks,
                ]);

                $pdf = Pdf::loadView('documents.insurance-policy', $policyData);
                $path = $this->storePdf($pdf, $booking->order->id, "insurance_{$booking->id}_{$tourist->id}");

                BookingDocument::create([
                    'booking_id' => $booking->id,
                    'type' => 'insurance',
                    'tourist_id' => $tourist->id,
                    'file_path' => $path,
                    'metadata' => $metadata,
                ]);
            }
            return;
        }

        // Payment flow: create placeholder with Click/Payme links
        BookingDocument::create([
            'booking_id' => $booking->id,
            'type' => 'insurance',
            'file_path' => '',
            'metadata' => [
                'neo_order_id' => $neoResult['order_id'],
                'neo_contract_id' => $neoResult['contract_id'] ?? null,
                'neo_click_url' => $neoResult['url'] ?? null,
                'neo_payme_url' => $neoResult['payme_url'] ?? null,
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

            // Already-issued insurance docs must be cleared before a fresh
            // register, otherwise repeated clicks just pile up duplicates.
            // We don't touch pending_payment docs — the earlier branch
            // handles those (payment check + upgrade to issued).
            $booking->documents()
                ->where('type', 'insurance')
                ->whereJsonContains('metadata->status', 'issued')
                ->delete();

            // Try registering now; registerInsurancePolicy needs the resolved
            // booking data to build the policy PDF if NeoInsurance replies
            // with police_number immediately (no-payment flow).
            try {
                $data = $this->dataResolver->resolve($booking);
            } catch (\Exception $e) {
                return 'Failed to resolve booking data: ' . $e->getMessage();
            }

            $this->registerInsurancePolicy($booking, $data);

            $booking->refresh()->load('documents');

            // No-payment flow: PDF was generated inline and saved as an 'issued' document
            if ($booking->documents()->where('type', 'insurance')->whereJsonContains('metadata->status', 'issued')->exists()) {
                return 'Policy issued and PDF generated.';
            }

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

        if (! $this->neoInsurance->isPolicyPaid($checkResult)) {
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
        $metadata['polis_seria'] = $checkResult['polis_seria'] ?? null;
        $metadata['polis_number'] = $checkResult['polis_number'] ?? null;
        $metadata['neo_view_url'] = $checkResult['url'] ?? null;
        $insuranceDoc->delete();

        // Generate insurance PDF for each tourist
        foreach ($booking->tourists as $tourist) {
            $policyData = array_merge($data, [
                'tourist' => $tourist,
                'neo_order_id' => $neoOrderId,
                'polis_seria' => $checkResult['polis_seria'] ?? null,
                'polis_number' => $checkResult['polis_number'] ?? null,
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
        $disk = Storage::disk('local');

        // If an old copy of this file was written by a different OS user
        // (e.g. a root tinker run), the current PHP process may not have
        // write access to the file itself — only to its parent directory.
        // unlink first, then write; then unlink succeeds via directory perms
        // and put always lands on a fresh inode we own.
        if ($disk->exists($path)) {
            $disk->delete($path);
        }

        if (! $disk->put($path, $pdf->output())) {
            throw new \RuntimeException("storePdf: could not write {$path}");
        }

        return $path;
    }
}
