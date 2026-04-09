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
            Action::make('checkInsurance')
                ->label('Check Insurance & Generate')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->visible(fn () => ! empty($this->record->insurance_risks))
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
