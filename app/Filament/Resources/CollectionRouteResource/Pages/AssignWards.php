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
    public ?string $newWard = null;
    public ?string $newOrder = null;

    public function mount(CollectionRoute $record): void
    {
        $this->record = $record;
        $this->loadWardData();
    }

    protected function loadWardData(): void
    {
        $assignments = $this->record->wards()
            ->get()
            ->mapWithKeys(function ($ward) {
                return [$ward->id => $ward->pivot->collection_order];
            })
            ->toArray();

        $this->wardAssignment = $assignments;

        $this->availableWards = Ward::query()
            ->where('organisation_id', $this->record->organisation_id)
            ->whereNotIn('id', array_keys($this->wardAssignment))
            ->pluck('name', 'id')
            ->toArray();
    }

    public function addWards(): void
    {
        $this->validate([
            'newWard' => ['required', 'exists:wards,id'],
            'newOrder' => ['nullable', 'numeric', 'min:1'],
        ]);

        $ward = Ward::where('id', $this->newWard)
            ->where('organisation_id', $this->record->organisation_id)
            ->first();

        if (!$ward) {
            Notification::make()
                ->title('Invalid ward')
                ->danger()
                ->body('The selected ward is not valid for this route.')
                ->send();
            return;
        }

        if (!isset($this->wardAssignment[$ward->id])) {
            $order = $this->newOrder ?? (count($this->wardAssignment) + 1);
            $this->wardAssignment[$ward->id] = $order;
            unset($this->availableWards[$ward->id]);
        }

        $this->reset(['newWard', 'newOrder']);

        Notification::make()
            ->title('Ward added')
            ->success()
            ->body("{$ward->name} has been added to the route.")
            ->send();
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

    public function saveAssignments(): void
    {
        try {
            if (empty($this->wardAssignment)) {
                throw new \Exception('No ward assignments to save');
            }

            $orders = [];
            $currentOrder = 1;

            $sortedAssignments = collect($this->wardAssignment)
                ->sortBy(fn($order) => $order === null ? PHP_INT_MAX : $order);

            foreach ($sortedAssignments as $wardId => $order) {
                $orders[$wardId] = $order === null ? null : $currentOrder++;
            }

            DB::transaction(function () use ($orders) {
                $this->record->wards()->sync([]);

                foreach ($orders as $wardId => $order) {
                    $ward = Ward::find($wardId); // ✅ Load ward to get uuid

                    if (!$ward) {
                        throw new \Exception("Ward with ID {$wardId} not found.");
                    }

                    $this->record->wards()->attach($wardId, [
                        'collection_order'      => $order,
                        'collection_route_uuid' => $this->record->uuid,
                        'ward_uuid'             => $ward->uuid,
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
            ]);

            Notification::make()
                ->title('Failed to save assignments')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
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
