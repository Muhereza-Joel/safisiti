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
            'household' => Tab::make('Household')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'household')),
            'market' => Tab::make('Market')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'market')),
            'school' => Tab::make('School')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'school')),
            'hospital' => Tab::make('Hospital')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'hospital')),
            'clinic' => Tab::make('Clinic')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'clinic')),
            'restaurant' => Tab::make('Restaurant')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'restaurant')),
            'hotel' => Tab::make('Hotel')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'hotel')),
            'office' => Tab::make('Office')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'office')),
            'shop' => Tab::make('Shop')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'shop')),
            'supermarket' => Tab::make('Supermarket')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'supermarket')),
            'other' => Tab::make('Other')
                ->modifyQueryUsing(fn($query) => $query->where('category', 'other')),
        ];
    }
}
