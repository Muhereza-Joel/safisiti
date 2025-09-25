<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\DirectCollection;
use Illuminate\Support\Facades\Auth;

class DirectCollectionsChart extends ApexChartWidget
{
    protected static ?string $heading = 'Direct Collections Overview';
    protected static ?string $subheading = 'Shows the total amount of waste collected directly at the dumping site from individuals, excluding collections made by service providers.';


    protected function getOptions(): array
    {
        $user = Auth::user();

        // Base query
        $query = DirectCollection::query();

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
            'colors' => ['#378266'], // nice green color
        ];
    }
}
