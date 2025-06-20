<?php

namespace App\Filament\Resources\CollectionRouteResource\Pages;

use App\Filament\Resources\CollectionRouteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCollectionRoute extends EditRecord
{
    protected static string $resource = CollectionRouteResource::class;

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
