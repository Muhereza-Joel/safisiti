<?php

namespace App\Filament\Resources\CellResource\Pages;

use App\Filament\Resources\CellResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCell extends CreateRecord
{
    protected static string $resource = CellResource::class;

    protected function getCreatedNotificationMessage(): ?string
    {
        return "New Cell Saved.";
    }
}
