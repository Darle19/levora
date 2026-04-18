<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Services\DocumentGenerationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateDocuments')
                ->label('Regenerate Documents')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Regenerate Documents')
                ->modalDescription('This will delete all existing documents and create fresh ones based on current booking data.')
                ->action(function () {
                    $booking = $this->record;
                    $order = $booking->order;

                    // Delete existing documents
                    $booking->documents()->delete();

                    // Regenerate
                    try {
                        app(DocumentGenerationService::class)->generateAllForOrder($order);
                        $count = $booking->fresh()->documents->count();
                        Notification::make()
                            ->title("Regenerated {$count} document(s)")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Failed to regenerate')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('checkInsurance')
                ->label('Check Insurance & Generate')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->disabled(fn () => empty($this->record->insurance_risks))
                ->tooltip(fn () => empty($this->record->insurance_risks)
                    ? 'Set insurance_risks on the booking before generating'
                    : null)
                ->requiresConfirmation()
                ->modalHeading('Check Insurance Payment')
                ->modalDescription('This will check if the NeoInsurance policy has been paid and generate the insurance PDF documents.')
                ->action(function () {
                    $service = app(DocumentGenerationService::class);
                    $message = $service->checkAndGenerateInsurance($this->record);

                    Notification::make()
                        ->title($message)
                        ->success()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }
}
