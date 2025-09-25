<?php

namespace App\Filament\Resources\DirectCollectionResource\Pages;

use App\Filament\Resources\DirectCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDirectCollection extends EditRecord
{
    protected static string $resource = DirectCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
