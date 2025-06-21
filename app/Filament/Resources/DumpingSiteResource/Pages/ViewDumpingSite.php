<?php

namespace App\Filament\Resources\DumpingSiteResource\Pages;

use App\Filament\Resources\DumpingSiteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDumpingSite extends ViewRecord
{
    protected static string $resource = DumpingSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
