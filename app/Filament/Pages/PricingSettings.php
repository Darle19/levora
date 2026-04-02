<?php

namespace App\Filament\Pages;

use App\Models\Setting;
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
            'tour_hidden_fee' => Setting::getValue('tour_hidden_fee', '60.00'),
            'tour_agent_fee' => Setting::getValue('tour_agent_fee', '50.00'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    TextInput::make('tour_hidden_fee')
                        ->label('Hidden Fee per Person ($)')
                        ->numeric()
                        ->step(0.01)
                        ->required()
                        ->helperText('Internal fee added to tour price, not visible to agents (default: $60)'),
                    TextInput::make('tour_agent_fee')
                        ->label('Agent Fee per Person ($)')
                        ->numeric()
                        ->step(0.01)
                        ->required()
                        ->helperText('Fee visible to agents, added to tour price (default: $50)'),
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

        Setting::setValue('tour_hidden_fee', $data['tour_hidden_fee']);
        Setting::setValue('tour_agent_fee', $data['tour_agent_fee']);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            // Prices are now dynamic — no recalculation needed
        ];
    }
}
