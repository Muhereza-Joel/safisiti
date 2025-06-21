<?php

namespace App\Filament\Resources\RecyclingMethodResource\Pages;

use App\Filament\Resources\RecyclingMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecyclingMethods extends ListRecords
{
    protected static string $resource = RecyclingMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
