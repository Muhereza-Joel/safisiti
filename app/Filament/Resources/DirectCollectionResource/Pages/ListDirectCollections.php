<?php

namespace App\Filament\Resources\DirectCollectionResource\Pages;

use App\Filament\Resources\DirectCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDirectCollections extends ListRecords
{
    protected static string $resource = DirectCollectionResource::class;

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
                    $query->whereDate('created_at', now()->toDateString())
                )
                ->badge($this->getTodayCount()),

            'this_week' => Tab::make('This Week')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                )
                ->badge($this->getThisWeekCount()),

            'this_month' => Tab::make('This Month')
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                )
                ->badge($this->getThisMonthCount()),
        ];
    }

    protected function getAllCount(): int
    {
        return static::getResource()::getModel()::count();
    }

    protected function getTodayCount(): int
    {
        return static::getResource()::getModel()
            ::whereDate('created_at', now()->toDateString())
            ->count();
    }

    protected function getThisWeekCount(): int
    {
        return static::getResource()::getModel()
            ::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
    }

    protected function getThisMonthCount(): int
    {
        return static::getResource()::getModel()
            ::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }
}
