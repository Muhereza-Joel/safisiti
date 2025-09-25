<?php

namespace App\Filament\Resources\AwarenessCampaignResource\Pages;

use App\Filament\Resources\AwarenessCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAwarenessCampaigns extends ListRecords
{
    protected static string $resource = AwarenessCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
