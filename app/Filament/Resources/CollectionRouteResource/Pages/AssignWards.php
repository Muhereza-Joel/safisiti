<?php

namespace App\Filament\Resources\CollectionRouteResource\Pages;

use Filament\Notifications\Notification;
use App\Filament\Resources\CollectionRouteResource;
use App\Models\CollectionRoute;
use App\Models\Ward;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class AssignWards extends Page
{
    protected static string $resource = CollectionRouteResource::class;

    protected static ?string $title = '';

    protected static string $view = 'filament.resources.collection-route-resource.pages.assign-wards';

    public CollectionRoute $record;
    public array $wardAssignment = [];
    public array $availableWards = [];
    public array $selectedWards = [];
    public ?string $newOrder = null;

    public function mount(CollectionRoute $record): void
    {
        $this->record = $record;
        $this->loadWardData();
    }

    protected function loadWardData(): void
    {
        // Load wards and their order; treat null order as large number to sort last
        $assignments = $this->record->wards()
            ->get()
            ->mapWithKeys(function ($ward) {
                $order = $ward->pivot->collection_order;
                return [$ward->id => $order === null ? PHP_INT_MAX : $order];
            })
            ->sort()
            ->toArray();

        $this->wardAssignment = $assignments;

        // Load available wards from same organisation excluding assigned
        $this->availableWards = Ward::query()
            ->where('organisation_id', $this->record->organisation_id)
            ->whereNotIn('id', array_keys($this->wardAssignment))
            ->pluck('name', 'id')
            ->toArray();
    }

    public function saveAssignments(): void
    {
        try {
            // Normalize orders: reindex from 1, keep relative order, nulls stay null
            $orders = array_filter($this->wardAssignment, fn($order) => $order !== null && $order !== 0);
            $sortedOrders = collect($orders)->sort()->values()->toArray();

            $newAssignments = [];
            $i = 1;
            foreach ($this->wardAssignment as $wardId => $order) {
                if ($order === null || $order == 0) {
                    $newAssignments[$wardId] = null;
                } else {
                    $newAssignments[$wardId] = $i++;
                }
            }

            DB::transaction(function () use ($newAssignments) {
                $this->record->wards()->detach();

                foreach ($newAssignments as $wardId => $order) {
                    $this->record->wards()->attach($wardId, [
                        'collection_order' => $order,
                    ]);
                }
            });

            $this->loadWardData();

            Notification::make()
                ->title('Wards assigned successfully')
                ->success()
                ->body('The ward assignments have been updated for this route.')
                ->send();
        } catch (\Throwable $e) {
            logger()->error('Failed to save ward assignments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Failed to save assignments')
                ->danger()
                ->body('An unexpected error occurred. Please try again.')
                ->send();
        }
    }

    public function addWards(): void
    {
        $this->validate([
            'selectedWards' => ['required', 'array'],
            'selectedWards.*' => ['exists:wards,id'],
            'newOrder' => ['nullable', 'numeric', 'min:1'],
        ]);

        $wards = Ward::whereIn('id', $this->selectedWards)
            ->where('organisation_id', $this->record->organisation_id)
            ->get();

        // Determine starting order if none given
        $maxOrder = empty($this->wardAssignment) ? 0 : max(array_filter($this->wardAssignment, fn($o) => $o !== null));

        $order = $this->newOrder ?? ($maxOrder + 1);

        $added = [];

        foreach ($wards as $ward) {
            if (!isset($this->wardAssignment[$ward->id])) {
                $this->wardAssignment[$ward->id] = $order++;
                $added[] = $ward->name;
            }
            unset($this->availableWards[$ward->id]);
        }

        $this->reset(['selectedWards', 'newOrder']);

        $this->loadWardData();

        if (!empty($added)) {
            Notification::make()
                ->title('Wards added')
                ->success()
                ->body('Added: ' . implode(', ', $added))
                ->send();
        } else {
            Notification::make()
                ->title('No wards added')
                ->warning()
                ->body('No new wards were added. They might already be assigned or invalid.')
                ->send();
        }
    }

    public function removeWard($wardId): void
    {
        unset($this->wardAssignment[$wardId]);

        $ward = Ward::find($wardId);
        if ($ward && $ward->organisation_id === $this->record->organisation_id) {
            $this->availableWards[$ward->id] = $ward->name;
        }

        Notification::make()
            ->title('Ward removed')
            ->success()
            ->body("{$ward->name} has been removed from the route.")
            ->send();
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
