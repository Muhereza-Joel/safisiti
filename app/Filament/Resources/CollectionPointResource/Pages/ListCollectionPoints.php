<?php

namespace App\Filament\Resources\CollectionPointResource\Pages;

use App\Filament\Resources\CollectionPointResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class ListCollectionPoints extends ListRecords
{
    protected static string $resource = CollectionPointResource::class;

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
                ->modalHeading('Sync Collection Point IDs/UUIDs')
                ->modalDescription('Are you sure you want to run the ID/UUID sync? This will scan the database and fix any missing links between collection points, wards and cells. This is safe to run anytime.')
                ->modalSubmitActionLabel('Yes, run sync')
                ->action(function () {
                    // 1. Call your Artisan command
                    Artisan::call('backfill:collection-point-uuids');

                    // 2. Send a success notification
                    Notification::make()
                        ->title('Sync Complete')
                        ->body('The Collection Point ID/UUID sync has been successfully executed.')
                        ->success()
                        ->send();
                }),
            // --- END OF ACTION ---
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'household' => Tab::make('Households')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'household')),
            'market' => Tab::make('Markets')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'market')),
            'school' => Tab::make('Schools')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'school')),
            'hospital' => Tab::make('Hospitals')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'hospital')),
            'clinic' => Tab::make('Clinics')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'clinic')),
            'restaurant' => Tab::make('Restaurants')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'restaurant')),
            'hotel' => Tab::make('Hotels')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'hotel')),
            'office' => Tab::make('Offices')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'office')),
            'shop' => Tab::make('Shops')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'shop')),
            'supermarket' => Tab::make('Supermarkets')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'supermarket')),
            'other' => Tab::make('Others')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'other')),
        ];
    }
}
