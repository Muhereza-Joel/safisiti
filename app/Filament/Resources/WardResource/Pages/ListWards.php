<?php

namespace App\Filament\Resources\WardResource\Pages;

use App\Filament\Resources\WardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListWards extends ListRecords
{
    protected static string $resource = WardResource::class;

    public function getTitle(): string
    {
        $org = Auth::user()?->organisation?->name ?? 'Wards';
        return $org . ' Wards';
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
