<?php

namespace App\Filament\Resources\DumpingSiteResource\Pages;

use App\Filament\Resources\DumpingSiteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDumpingSite extends EditRecord
{
    protected static string $resource = DumpingSiteResource::class;

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
