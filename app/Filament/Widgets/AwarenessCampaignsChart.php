<?php

namespace App\Filament\Widgets;

use App\Models\AwarenessCampaign;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AwarenessCampaignsChart extends ApexChartWidget
{
    protected static ?string $heading = 'Awareness Campaigns Overview';
    protected static ?string $subheading = 'Shows the total number of awareness campaigns conducted in the field by health inspectors';


    protected function getOptions(): array
    {
        $user = Auth::user();

        // Base query
        $query = AwarenessCampaign::query();

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
                'name' => 'Awareness Campaigns',
                'data' => [$all, $today, $week, $month],
            ]],
            'xaxis' => [
                'categories' => ['All', 'Today', 'This Week', 'This Month'],
            ],
            'colors' => ['#643782'], // indigo
        ];
    }
}
