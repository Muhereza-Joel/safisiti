<?php

namespace App\Filament\Resources\WasteCollectionResource\Pages;

use App\Filament\Resources\WasteCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListWasteCollections extends ListRecords
{
    protected static string $resource = WasteCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
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
