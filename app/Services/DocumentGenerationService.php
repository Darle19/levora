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
        private NeoInsuranceService $insuranceService
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

        $booking->loadMissing([
            'order.agency',
            'order.currency',
            'tourists',
            'bookable.hotel.category',
            'bookable.country',
            'bookable.resort',
            'bookable.mealType',
            'bookable.flights.airline',
            'bookable.flights.fromAirport',
            'bookable.flights.toAirport',
        ]);

        $this->generateConfirmation($booking);
        $this->generateMemo($booking);
        $this->generateVoucher($booking);

        foreach ($booking->tourists as $tourist) {
            $this->generateTicket($booking, $tourist);
            $this->generateInsurance($booking, $tourist);
        }
    }

    private function generateConfirmation(Booking $booking): void
    {
        $order = $booking->order;
        $tour = $booking->bookable;

        $pdf = Pdf::loadView('documents.confirmation', [
            'booking' => $booking,
            'order' => $order,
            'tour' => $tour,
            'agency' => $order->agency,
        ]);

        $path = $this->storePdf($pdf, $order->id, "confirmation_{$booking->id}");

        BookingDocument::create([
            'booking_id' => $booking->id,
            'type' => 'confirmation',
            'file_path' => $path,
        ]);
    }

    private function generateMemo(Booking $booking): void
    {
        $order = $booking->order;
        $tour = $booking->bookable;

        $pdf = Pdf::loadView('documents.memo', [
            'booking' => $booking,
            'order' => $order,
            'tour' => $tour,
        ]);

        $path = $this->storePdf($pdf, $order->id, "memo_{$booking->id}");

        BookingDocument::create([
            'booking_id' => $booking->id,
            'type' => 'memo',
            'file_path' => $path,
        ]);
    }

    private function generateVoucher(Booking $booking): void
    {
        $order = $booking->order;
        $tour = $booking->bookable;

        $pdf = Pdf::loadView('documents.voucher', [
            'booking' => $booking,
            'order' => $order,
            'tour' => $tour,
        ]);

        $path = $this->storePdf($pdf, $order->id, "voucher_{$booking->id}");

        BookingDocument::create([
            'booking_id' => $booking->id,
            'type' => 'voucher',
            'file_path' => $path,
        ]);
    }

    private function generateTicket(Booking $booking, Tourist $tourist): void
    {
        $order = $booking->order;
        $tour = $booking->bookable;
        $flights = $tour?->flights ?? collect();

        $pdf = Pdf::loadView('documents.ticket', [
            'booking' => $booking,
            'order' => $order,
            'tour' => $tour,
            'tourist' => $tourist,
            'flights' => $flights,
        ]);

        $path = $this->storePdf($pdf, $order->id, "ticket_{$booking->id}_{$tourist->id}");

        BookingDocument::create([
            'booking_id' => $booking->id,
            'type' => 'ticket',
            'tourist_id' => $tourist->id,
            'file_path' => $path,
        ]);
    }

    private function generateInsurance(Booking $booking, Tourist $tourist): void
    {
        $order = $booking->order;
        $tour = $booking->bookable;

        $policyData = [];

        try {
            $apiResponse = $this->insuranceService->createPolicy($tourist, $booking);

            if ($apiResponse) {
                $policyData = [
                    'policy_number' => $apiResponse['policy_number'] ?? $apiResponse['id'] ?? null,
                    'premium' => $apiResponse['premium'] ?? $apiResponse['summa'] ?? null,
                    'api_response' => $apiResponse,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Insurance API failed, generating document without policy data', [
                'tourist_id' => $tourist->id,
                'error' => $e->getMessage(),
            ]);
        }

        $pdf = Pdf::loadView('documents.insurance', [
            'booking' => $booking,
            'order' => $order,
            'tour' => $tour,
            'tourist' => $tourist,
            'policyData' => $policyData,
        ]);

        $path = $this->storePdf($pdf, $order->id, "insurance_{$booking->id}_{$tourist->id}");

        BookingDocument::create([
            'booking_id' => $booking->id,
            'type' => 'insurance',
            'tourist_id' => $tourist->id,
            'file_path' => $path,
            'metadata' => !empty($policyData) ? $policyData : null,
        ]);
    }

    private function storePdf($pdf, int $orderId, string $filename): string
    {
        $path = "documents/{$orderId}/{$filename}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
