<?php

namespace App\Filament\Resources\WasteCollectionResource\Pages;

use App\Filament\Resources\WasteCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWasteCollection extends EditRecord
{
    protected static string $resource = WasteCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
