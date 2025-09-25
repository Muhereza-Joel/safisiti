<?php

namespace App\Filament\Resources\DirectCollectionResource\Pages;

use App\Filament\Resources\DirectCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDirectCollection extends ViewRecord
{
    protected static string $resource = DirectCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\EditAction::make(),
        ];
    }
}
