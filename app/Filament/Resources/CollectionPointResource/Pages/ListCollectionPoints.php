<?php

namespace App\Filament\Resources\CollectionPointResource\Pages;

use App\Filament\Resources\CollectionPointResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListCollectionPoints extends ListRecords
{
    protected static string $resource = CollectionPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
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
