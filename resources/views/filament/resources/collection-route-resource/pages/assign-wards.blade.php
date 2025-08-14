<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold">Assign Wards to Route: {{ $record->name }}</h2>
            <div class="flex space-x-2">
                @foreach($this->getHeaderActions() as $action)
                {{ $action }}
                @endforeach
            </div>
        </div>

        <!-- Current Assignments Section -->
        <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
            <h3 class="text-lg font-medium mb-4">
                Current Wards On Route ({{ count($wardAssignment) }})
            </h3>

            @if(count($wardAssignment) > 0)
            <div class="space-y-2">
                @foreach($wardAssignment as $wardId => $order)
                @php $ward = \App\Models\Ward::find($wardId); @endphp
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded">
                    <div class="flex-1">
                        <span class="font-medium">{{ $ward->name }}</span>
                        <span class="ml-4 px-2 py-1 text-xs font-semibold rounded bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200">
                            Order: {{ $order ?? 'Not set' }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                            (Ward Population: {{ number_format($ward->population) }} people)
                        </span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <x-filament::input
                            type="number"
                            min="1"
                            wire:model.lazy="wardAssignment.{{ $wardId }}"
                            placeholder="Order"
                            class="w-20" />
                        <x-filament::icon-button
                            icon="heroicon-o-trash"
                            color="danger"
                            wire:click="removeWard('{{ $wardId }}')" />
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 dark:text-gray-400">No wards assigned to this route yet.</p>
            @endif
        </div>

        <!-- Add New Wards Section -->
        <div class="bg-white rounded-lg shadow p-6 dark:bg-gray-800">
            <h3 class="text-lg font-medium mb-4">Add Ward to Route</h3>
            <div class="flex flex-col md:flex-row gap-4 items-center">
                <div class="flex-1">
                    <x-filament::input.select wire:model="newWard">
                        <option value="">Select a ward</option>
                        @foreach($availableWards as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </div>
                <div class="w-32">
                    <x-filament::input
                        type="number"
                        min="1"
                        wire:model="newOrder"
                        placeholder="Order (optional)" />
                </div>
                <x-filament::button
                    icon="heroicon-o-plus"
                    wire:click="addWards">
                    Add
                </x-filament::button>
            </div>
        </div>

        <!-- Save Button Section -->
        <div class="flex justify-end">
            <x-filament::button
                icon="heroicon-o-check"
                wire:click="saveAssignments">
                Save Assignments
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>