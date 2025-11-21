<?php

namespace App\Filament\Resources\WasteCollectionResource\Pages;

use App\Filament\Resources\WasteCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class ListWasteCollections extends ListRecords
{
    protected static string $resource = WasteCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            // --- ADD THIS ACTION ---
            Actions\Action::make('backfill_uuids')
                ->label('Sync ID/UUIDs')
                ->icon('heroicon-o-arrow-path')
                ->color('warning') // Use a color to show it's a special action
                ->requiresConfirmation() // Ask the user "Are you sure?"
                ->modalHeading('Sync Collections IDs/UUIDs')
                ->modalDescription('Are you sure you want to run the ID/UUID sync? This will scan the database and fix any missing links between collections, waste types, batches and collection points. This is safe to run anytime.')
                ->modalSubmitActionLabel('Yes, run sync')
                ->action(function () {
                    // 1. Call your Artisan command
                    Artisan::call('backfill:waste-collection-uuids');

                    // 2. Send a success notification
                    Notification::make()
                        ->title('Sync Complete')
                        ->body('The Collections ID/UUID sync has been successfully executed.')
                        ->success()
                        ->send();
                }),
            // --- END OF ACTION ---
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge($this->getAllCount()),

            'today' => Tab::make('Today')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $this->scopeToOrg($query)->whereDate('created_at', now()->toDateString())
                )
                ->badge($this->getTodayCount()),

            'this_week' => Tab::make('This Week')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $this->scopeToOrg($query)->whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ])
                )
                ->badge($this->getThisWeekCount()),

            'this_month' => Tab::make('This Month')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $this->scopeToOrg($query)->whereBetween('created_at', [
                        now()->startOfMonth(),
                        now()->endOfMonth(),
                    ])
                )
                ->badge($this->getThisMonthCount()),
        ];
    }

    /**
     * Apply organisation scope
     */
    protected function scopeToOrg(Builder $query): Builder
    {
        $orgId = Auth::user()->organisation_id;

        return $query->where('organisation_id', $orgId);
    }

    protected function getAllCount(): int
    {
        return static::getResource()::getModel()
            ::where('organisation_id', Auth::user()->organisation_id)
            ->count();
    }

    protected function getTodayCount(): int
    {
        return static::getResource()::getModel()
            ::where('organisation_id', Auth::user()->organisation_id)
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }

    protected function getThisWeekCount(): int
    {
        return static::getResource()::getModel()
            ::where('organisation_id', Auth::user()->organisation_id)
            ->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])
            ->count();
    }

    protected function getThisMonthCount(): int
    {
        return static::getResource()::getModel()
            ::where('organisation_id', Auth::user()->organisation_id)
            ->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])
            ->count();
    }
}
