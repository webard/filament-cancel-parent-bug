<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class BugPage extends Page
{
    protected string $view = 'filament.pages.bug-page';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBugAnt;

    protected static ?string $navigationLabel = 'Bug';

    public function getTitle(): string | Htmlable
    {
        return 'Mounted action with no modal blocks every other action';
    }

    protected function getHeaderActions(): array
    {
        return [
            // This action has no schema and no confirmation, so it has no modal of its
            // own. It mounts a nested action and halts, which is a supported way to
            // stop an action while leaving it mounted.
            Action::make('openChild')
                ->label('1. Open the child modal')
                ->color('danger')
                ->registerModalActions([
                    Action::make('child')
                        ->label('Child')
                        ->modalHeading('Close me with the X, Escape, or by clicking away')
                        ->modalDescription('Then try the second button. Nothing will happen.')
                        ->action(fn () => null),
                ])
                ->action(function (Action $action): void {
                    $action->getLivewire()->mountAction('child');

                    $action->halt();
                }),

            // The documented case, which must keep working as it does today: this
            // action has a modal of its own, so closing the child returns the user to
            // it rather than losing the flow.
            Action::make('openChildFromAModal')
                ->label('3. The same, from an action that has a modal')
                ->color('gray')
                ->requiresConfirmation()
                ->registerModalActions([
                    Action::make('child')
                        ->label('Child')
                        ->modalHeading('Close me: you should land back on the confirmation')
                        ->action(fn () => null),
                ])
                ->action(function (Action $action): void {
                    $action->getLivewire()->mountAction('child');

                    $action->halt();
                }),

            Action::make('other')
                ->label('2. Any other action')
                ->color('primary')
                ->action(function (): void {
                    Notification::make()
                        ->title('The other action ran.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
