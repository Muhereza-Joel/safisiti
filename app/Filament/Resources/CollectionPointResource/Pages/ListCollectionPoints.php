<?php

namespace App\Filament\Resources\CollectionPointResource\Pages;

use App\Filament\Resources\CollectionPointResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCollectionPoints extends ListRecords
{
    protected static string $resource = CollectionPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
