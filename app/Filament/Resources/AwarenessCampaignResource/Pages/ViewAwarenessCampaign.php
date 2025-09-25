<?php

namespace App\Filament\Resources\AwarenessCampaignResource\Pages;

use App\Filament\Resources\AwarenessCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAwarenessCampaign extends ViewRecord
{
    protected static string $resource = AwarenessCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
