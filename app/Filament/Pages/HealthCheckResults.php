<?php

namespace App\Filament\Pages;

use ShuvroRoy\FilamentSpatieLaravelHealth\Pages\HealthCheckResults as BaseHealthCheckResults;
use Illuminate\Support\Htmlable;

class HealthCheckResults extends BaseHealthCheckResults
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    public function getHeading(): string
    {
        return 'Health Check Results';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Site Management';
    }
}
