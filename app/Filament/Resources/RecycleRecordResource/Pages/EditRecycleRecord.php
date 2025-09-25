<?php

namespace App\Filament\Resources\RecycleRecordResource\Pages;

use App\Filament\Resources\RecycleRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecycleRecord extends EditRecord
{
    protected static string $resource = RecycleRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
