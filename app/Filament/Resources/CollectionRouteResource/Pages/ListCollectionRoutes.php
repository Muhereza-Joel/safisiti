<?php

namespace App\Filament\Resources\CollectionRouteResource\Pages;

use App\Filament\Resources\CollectionRouteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCollectionRoutes extends ListRecords
{
    protected static string $resource = CollectionRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),


        ];
    }
}
