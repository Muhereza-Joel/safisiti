<?php

namespace App\Filament\Resources\CollectionPointResource\Pages;

use App\Filament\Resources\CollectionPointResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCollectionPoint extends CreateRecord
{
    protected static string $resource = CollectionPointResource::class;

    protected function getCreatedNotificationMessage(): ?string
    {
        return "New Collection Point Saved.";
    }
}
