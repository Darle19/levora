<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\TourPricingService;
use BackedEnum;
use UnitEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class PricingSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $title = 'Pricing Settings';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.pricing-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'tour_markup_percent' => Setting::getValue('tour_markup_percent', '15.00'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    TextInput::make('tour_markup_percent')
                        ->label('Default Tour Markup (%)')
                        ->numeric()
                        ->step(0.01)
                        ->required()
                        ->helperText('Applied to all tours that do not have a per-tour markup override'),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save Settings')
                                ->submit('save'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::setValue('tour_markup_percent', $data['tour_markup_percent']);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculateAll')
                ->label('Recalculate All Tour Prices')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Recalculate All Prices')
                ->modalDescription('This will recalculate prices for all tours using the current hotel prices, flight prices, and markup settings. Continue?')
                ->action(function () {
                    $count = app(TourPricingService::class)->recalculateAll();

                    Notification::make()
                        ->success()
                        ->title("{$count} tour prices recalculated")
                        ->send();
                }),
        ];
    }
}
