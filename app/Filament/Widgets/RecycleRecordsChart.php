<?php

namespace App\Filament\Widgets;

use App\Models\RecycleRecord;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\Auth;

class RecycleRecordsChart extends ApexChartWidget
{
    protected static ?string $heading = 'Recycle Records Overview';
    protected static ?string $subheading = 'Displays the total quantity of materials recycled, grouped by time periods and filtered by your organisation.';

    protected function getOptions(): array
    {
        $user = Auth::user();

        // Base query
        $query = RecycleRecord::query();

        // Filter by organisation if not super_admin
        if (!$user->hasRole('super_admin')) {
            $query->where('organisation_id', $user->organisation_id);
        }

        // Count or sum data
        $today = (clone $query)->sum('quantity');
        $week = (clone $query)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('quantity');
        $month = (clone $query)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('quantity');
        $all = (clone $query)->sum('quantity');

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 400,
            ],
            'series' => [[
                'name' => 'Recycled Quantity in Kilograms',
                'data' => [$all, $today, $week, $month],
            ]],
            'xaxis' => [
                'categories' => ['All', 'Today', 'This Week', 'This Month'],
            ],
            'colors' => ['#22c55e'], // green
        ];
    }
}
