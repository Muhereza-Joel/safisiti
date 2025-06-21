<?php

namespace App\Filament\Resources\RecyclingMethodResource\Pages;

use App\Filament\Resources\RecyclingMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecyclingMethod extends EditRecord
{
    protected static string $resource = RecyclingMethodResource::class;

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
