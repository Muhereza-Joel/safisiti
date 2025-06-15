<?php

namespace App\Filament\Resources\CellResource\Pages;

use App\Filament\Resources\CellResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCell extends ViewRecord
{
    protected static string $resource = CellResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
