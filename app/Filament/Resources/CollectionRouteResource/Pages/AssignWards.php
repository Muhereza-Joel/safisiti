<?php

// app/Filament/Resources/CollectionRouteResource/Pages/AssignWards.php
namespace App\Filament\Resources\CollectionRouteResource\Pages;

use App\Filament\Resources\CollectionRouteResource;
use App\Models\CollectionRoute;
use App\Models\Ward;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class AssignWards extends Page
{
    protected static string $resource = CollectionRouteResource::class;

    protected static null|string $title = "";

    protected static string $view = 'filament.resources.collection-route-resource.pages.assign-wards';

    public CollectionRoute $record;
    public array $wardAssignment = [];
    public array $availableWards = [];
    public ?string $newWard = null;
    public ?string $newOrder = null;

    public function mount(CollectionRoute $record): void
    {
        $this->record = $record;
        $this->loadWardData();
    }

    protected function loadWardData(): void
    {
        // Get currently assigned wards with their order
        $this->wardAssignment = $this->record->wards()
            ->orderBy('collection_order')
            ->pluck('collection_order', 'ward_id')
            ->toArray();

        // Get all available wards (excluding already assigned ones)
        $this->availableWards = Ward::query()
            ->whereNotIn('id', array_keys($this->wardAssignment))
            ->pluck('name', 'id')
            ->toArray();
    }

    public function saveAssignments(): void
    {
        DB::transaction(function () {
            // Clear existing assignments
            $this->record->wards()->detach();

            // Add new assignments
            foreach ($this->wardAssignment as $wardId => $order) {
                $this->record->wards()->attach($wardId, [
                    'collection_order' => $order ?: null
                ]);
            }
        });

        $this->loadWardData();
        $this->dispatch('notify', type: 'success', message: 'Ward assignments saved successfully!');
    }

    public function addWard(): void
    {
        $this->validate([
            'newWard' => ['required', 'exists:wards,id'],
            'newOrder' => ['nullable', 'numeric', 'min:1'],
        ]);

        $wardId = $this->newWard;
        $this->wardAssignment[$wardId] = $this->newOrder ?? null;
        unset($this->availableWards[$wardId]);

        $this->reset(['newWard', 'newOrder']);
        $this->dispatch('notify', type: 'success', message: 'Ward added to route!');
    }

    public function removeWard($wardId): void
    {
        unset($this->wardAssignment[$wardId]);
        $this->availableWards[$wardId] = Ward::find($wardId)->name;
        $this->dispatch('notify', type: 'success', message: 'Ward removed from route!');
    }

    public function getBreadcrumbs(): array
    {
        return [
            CollectionRouteResource::getUrl() => 'Collection Routes',
            'Assign Wards',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Route')
                ->url(fn() => CollectionRouteResource::getUrl('view', [$this->record])),
        ];
    }
}
