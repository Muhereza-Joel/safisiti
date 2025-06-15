<?php

namespace App\Filament\Resources\WardResource\Pages;

use App\Filament\Resources\WardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWard extends CreateRecord
{
    protected static string $resource = WardResource::class;

    protected function getCreatedNotificationMessage(): ?string
    {
        return "Ward Created Successfully.";
    }
}
