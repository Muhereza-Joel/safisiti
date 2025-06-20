<?php

namespace App\Filament\Resources\CollectionRouteResource\Pages;

use App\Filament\Resources\CollectionRouteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCollectionRoute extends ViewRecord
{
    protected static string $resource = CollectionRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
