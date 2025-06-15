<?php

namespace App\Filament\Pages;

use ShuvroRoy\FilamentSpatieLaravelBackup\Pages\Backups as BaseBackups;
use Illuminate\Support\Htmlable;

class Backups extends BaseBackups
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    public function getHeading(): string
    {
        return 'Application Backups';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Backup Management';
    }
}
