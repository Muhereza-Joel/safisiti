<?php

namespace App\Filament\Resources\CollectionRouteResource\Pages;

use App\Filament\Resources\CollectionRouteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListCollectionRoutes extends ListRecords
{
    protected static string $resource = CollectionRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),


        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Rounds'),

            'daily' => Tab::make('Daily Rounds')
                ->modifyQueryUsing(fn($query) => $query->where('frequency', 'daily')),

            'weekly' => Tab::make('Weekly Rounds')
                ->modifyQueryUsing(fn($query) => $query->where('frequency', 'weekly')),

            'bi-weekly' => Tab::make('Bi-Weekly Rounds')
                ->modifyQueryUsing(fn($query) => $query->where('frequency', 'bi-weekly')),

            'monthly' => Tab::make('Monthly Rounds')
                ->modifyQueryUsing(fn($query) => $query->where('frequency', 'monthly')),

            'custom' => Tab::make('Custom Rounds')
                ->modifyQueryUsing(fn($query) => $query->where('frequency', 'custom')),
        ];
    }
}
