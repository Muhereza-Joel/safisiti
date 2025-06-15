<?php

namespace App\Filament\Resources\CollectionPointResource\Pages;

use App\Filament\Resources\CollectionPointResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCollectionPoint extends ViewRecord
{
    protected static string $resource = CollectionPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
