<?php

namespace App\Filament\Resources\CollectionBatchResource\Pages;

use App\Filament\Resources\CollectionBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class ManageCollectionBatches extends ManageRecords
{
    protected static string $resource = CollectionBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            // --- ADD THIS ACTION ---
            Actions\Action::make('backfill_uuids')
                ->label('Sync ID/UUIDs')
                ->icon('heroicon-o-arrow-path')
                ->color('warning') // Use a color to show it's a special action
                ->requiresConfirmation() // Ask the user "Are you sure?"
                ->modalHeading('Sync Collection Batch IDs/UUIDs')
                ->modalDescription('Are you sure you want to run the ID/UUID sync? This will scan the database and fix any missing links between vehicles and service providers. This is safe to run anytime.')
                ->modalSubmitActionLabel('Yes, run sync')
                ->action(function () {
                    // 1. Call your Artisan command
                    Artisan::call('backfill:collection-batch-uuids');

                    // 2. Send a success notification
                    Notification::make()
                        ->title('Sync Complete')
                        ->body('The Collection Batch ID/UUID sync has been successfully executed.')
                        ->success()
                        ->send();
                }),
            // --- END OF ACTION ---
        ];
    }
}
