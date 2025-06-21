<?php

namespace App\Filament\Resources\RecyclingCenterResource\Pages;

use App\Filament\Resources\RecyclingCenterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecyclingCenters extends ListRecords
{
    protected static string $resource = RecyclingCenterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
