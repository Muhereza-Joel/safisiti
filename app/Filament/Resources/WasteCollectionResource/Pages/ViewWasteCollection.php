<?php

namespace App\Filament\Resources\WasteCollectionResource\Pages;

use App\Filament\Resources\WasteCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWasteCollection extends ViewRecord
{
    protected static string $resource = WasteCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
