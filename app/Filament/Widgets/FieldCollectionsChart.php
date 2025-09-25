<?php

namespace App\Filament\Widgets;

use App\Models\WasteCollection;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\Auth;

class FieldCollectionsChart extends ApexChartWidget
{
    protected static ?string $heading = 'Field Collections Overview';
    protected static ?string $subheading = 'Shows the total amount of waste collected by service providers from various collection points.';


    protected function getOptions(): array
    {
        $user = Auth::user();

        // Base query
        $query = WasteCollection::query();

        // Filter by organisation if not super_admin
        if (!$user->hasRole('super_admin')) {
            $query->where('organisation_id', $user->organisation_id);
        }

        // Count data
        $today = (clone $query)->whereDate('created_at', now())->count();
        $week = (clone $query)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $month = (clone $query)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $all = (clone $query)->count();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 400,
            ],
            'series' => [[
                'name' => 'Collections',
                'data' => [$all, $today, $week, $month],
            ]],
            'xaxis' => [
                'categories' => ['All', 'Today', 'This Week', 'This Month'],
            ],
            'colors' => ['#2e69f2'], // indigo
        ];
    }
}
