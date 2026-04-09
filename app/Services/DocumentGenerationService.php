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

        // Tourist Voucher — always generated (summary page)
        $this->generateTouristVoucher($booking, $data);

        // Hotel Voucher — one per hotel stay
        foreach ($data['hotels'] as $i => $hotelStay) {
            $this->generateHotelVoucher($booking, $data, $hotelStay, $i);
        }

        // eTicket — one per tourist (only for FlightPath with flights)
        if ($data['type'] === 'flight_path' && $data['flights']->isNotEmpty()) {
            foreach ($booking->tourists as $tourist) {
                $this->generateETicket($booking, $data, $tourist);
            }
        }

        // Insurance — one per tourist (if insurance services exist)
        if ($data['insurances']->isNotEmpty()) {
            foreach ($booking->tourists as $tourist) {
                $this->generateInsurancePolicy($booking, $data, $tourist);
            }
        }
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

    private function generateInsurancePolicy(Booking $booking, array $data, Tourist $tourist): void
    {
        $pdf = Pdf::loadView('documents.insurance-policy', array_merge($data, [
            'tourist' => $tourist,
        ]));
        $path = $this->storePdf($pdf, $booking->order->id, "insurance_{$booking->id}_{$tourist->id}");

        BookingDocument::create([
            'booking_id' => $booking->id,
            'type' => 'insurance',
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
