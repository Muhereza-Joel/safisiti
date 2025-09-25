<?php

namespace App\Filament\Resources\AwarenessCampaignResource\Pages;

use App\Filament\Resources\AwarenessCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAwarenessCampaign extends EditRecord
{
    protected static string $resource = AwarenessCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
