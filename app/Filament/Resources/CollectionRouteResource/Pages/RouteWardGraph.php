<?php

namespace App\Filament\Resources\CollectionRouteResource\Pages;

use App\Filament\Resources\CollectionRouteResource;
use App\Models\CollectionRoute;
use App\Models\Ward;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class RouteWardGraph extends Page
{
    protected static string $resource = CollectionRouteResource::class;
    protected static string $view = 'filament.resources.collection-route-resource.pages.route-ward-graph';

    public array $routes = [];
    public array $wards = [];
    public array $connections = [];
    public ?string $selectedNode = null;
    public bool $isLoading = false;
    public ?string $search = null;

    public function mount(): void
    {
        $this->loadGraphData();
    }

    public function loadGraphData(): void
    {
        $this->isLoading = true;

        try {
            $query = CollectionRoute::with(['wards' => function ($query) {
                $query->withPivot('collection_order');
            }]);

            if ($this->search) {
                $query->where('name', 'like', "%{$this->search}%");
            }

            // ✅ Keys are now like 'route_5'
            $this->routes = $query->get()
                ->mapWithKeys(fn($route) => ['route_' . $route->id => $route->toArray()])
                ->toArray();

            // ✅ Keys are now like 'ward_3'
            $this->wards = Ward::all()
                ->mapWithKeys(fn($ward) => ['ward_' . $ward->id => $ward->toArray()])
                ->toArray();

            $this->connections = [];

            $routeIds = array_keys($this->routes);
            $usedColors = [];

            foreach ($routeIds as $index => $routeKey) {
                $usedColors[$routeKey] = $this->generateColor($index, count($routeIds));
            }

            foreach ($this->routes as $routeKey => $route) {
                $color = $usedColors[$routeKey];

                if (isset($route['wards'])) {
                    foreach ($route['wards'] as $ward) {
                        $this->connections[] = [
                            'source' => $routeKey,
                            'target' => 'ward_' . $ward['id'],
                            'order' => $ward['pivot']['collection_order'] ?? null,
                            'route_name' => $route['name'],
                            'ward_name' => $ward['name'],
                            'color' => $color,
                        ];
                    }
                }
            }
        } finally {
            $this->isLoading = false;
        }
    }

    protected function generateColor(int $index, int $total): string
    {
        $hue = ($index * (360 / max($total, 1))) % 360;
        return "hsl($hue, 70%, 55%)";
    }

    public function updatedSearch(): void
    {
        $this->loadGraphData();
    }

    public function refreshGraph(): void
    {
        $this->search = null;
        $this->loadGraphData();
        $this->dispatch('refreshGraph');
    }

    public function selectNode($nodeId): void
    {
        $this->selectedNode = $nodeId;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Graph')
                ->icon('heroicon-o-arrow-path')
                ->action('refreshGraph')
                ->disabled($this->isLoading),
        ];
    }
}
