<?php

namespace App\Filament\Resources\CollectionBatchResource\Pages;

use App\Filament\Resources\CollectionBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCollectionBatches extends ManageRecords
{
    protected static string $resource = CollectionBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
