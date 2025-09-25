<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectionBatchResource\Pages;
use App\Filament\Resources\CollectionBatchResource\RelationManagers;
use App\Models\CollectionBatch;
use App\Models\WasteCollection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\WasteType;
use Filament\Notifications\Notification;

class CollectionBatchResource extends Resource
{
    protected static ?string $model = CollectionBatch::class;

    protected static ?string $navigationGroup = 'Waste Collection & Recycling';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3-bottom-right';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vehicle_id')
                    ->label('Vehicle Number Plate')
                    ->relationship(
                        name: 'vehicle',
                        titleAttribute: 'registration_number',
                        modifyQueryUsing: fn(Builder $query) => auth()->user()->hasRole('Service Provider')
                            ? $query->where('user_id', auth()->id())
                            : $query
                    )
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('status')
                    ->options([
                        'not-delivered' => 'Not Delivered',
                        'delivered' => 'Delivered',
                    ])
                    ->default('not-delivered')
                    ->visibleOn('edit')
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('collection_batch_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle.user.name')
                    ->label('Service Provider')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle.registration_number')
                    ->label('Vehicle Number Plate')
                    ->searchable(),
                Tables\Columns\TextColumn::make('Garbage Collected')
                    ->label('Amount Collected')
                    ->numeric(),
                Tables\Columns\TextColumn::make('status'),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),

                Tables\Actions\Action::make('createCollection')
                    ->label('Create Collection')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Wizard::make([
                            Forms\Components\Wizard\Step::make('Batch Information')
                                ->schema([
                                    Forms\Components\TextInput::make('batch_number')
                                        ->default(fn($record) => $record->collection_batch_number)
                                        ->disabled()
                                        ->dehydrated(),
                                    Forms\Components\TextInput::make('vehicle')
                                        ->default(fn($record) => $record->vehicle->registration_number)
                                        ->disabled()
                                        ->dehydrated(),
                                ]),

                            Forms\Components\Wizard\Step::make('Route Selection')
                                ->schema([
                                    Forms\Components\Select::make('collection_route_id')
                                        ->label('Collection Route')
                                        ->options(\App\Models\CollectionRoute::pluck('name', 'id'))
                                        ->searchable()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn(callable $set) => $set('ward_id', null)),

                                    Forms\Components\Select::make('ward_id')
                                        ->label('Ward')
                                        ->options(function (callable $get) {
                                            $routeId = $get('collection_route_id');
                                            if (!$routeId) return [];

                                            return \App\Models\CollectionRoute::find($routeId)
                                                ->wards()
                                                ->select('wards.name', 'wards.id')
                                                ->pluck('wards.name', 'wards.id');
                                        })
                                        ->required()
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(fn(callable $set) => $set('cell_id', null)),

                                    Forms\Components\Select::make('cell_id')
                                        ->label('Cell')
                                        ->options(function (callable $get) {
                                            $wardId = $get('ward_id');
                                            if (!$wardId) return [];

                                            return \App\Models\Cell::where('ward_id', $wardId)
                                                ->select('cells.name', 'cells.id')
                                                ->pluck('cells.name', 'cells.id');
                                        })
                                        ->required()
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(fn(callable $set) => $set('collection_point_id', null)),
                                ]),

                            Forms\Components\Wizard\Step::make('Collection Details')
                                ->schema([
                                    Forms\Components\Select::make('collection_point_id')
                                        ->label('Collection Point')
                                        ->options(function (callable $get) {
                                            $cellId = $get('cell_id');
                                            if (!$cellId) return [];

                                            return \App\Models\CollectionPoint::where('cell_id', $cellId)
                                                ->select('collection_points.name', 'collection_points.id')
                                                ->pluck('collection_points.name', 'collection_points.id');
                                        })
                                        ->searchable()
                                        ->required(),

                                    Forms\Components\TextInput::make('amount')
                                        ->numeric()
                                        ->required()
                                        ->label('Amount Collected (kg)'),

                                    Forms\Components\Select::make('units')
                                        ->options([
                                            'kg' => 'Kilograms',
                                            'ton' => 'Tons',
                                            'liters' => 'Liters',
                                        ])
                                        ->default('kg')
                                        ->label('Unit of Measurement')
                                        ->required(),

                                    Forms\Components\Select::make('waste_type_id')
                                        ->label('Waste Type')
                                        ->options(function () {
                                            $user = auth()->user();

                                            if (! $user) {
                                                return [];
                                            }

                                            return WasteType::where('organisation_id', $user->organisation_id)
                                                ->pluck('name', 'id')
                                                ->toArray(); // [id => name]
                                        })
                                        ->searchable()
                                        ->placeholder('Select Waste Type')
                                        ->required(),

                                    Forms\Components\Select::make('seggregated')
                                        ->options([
                                            'yes' => 'Yes',
                                            'no' => 'No',
                                        ])->placeholder('Select Segregation Status')
                                        ->required(),


                                    Forms\Components\Textarea::make('notes')
                                        ->label('Additional Notes'),


                                ]),
                        ])
                    ])
                    ->action(function (CollectionBatch $record, array $data) {
                        // Create the waste collection record
                        $wasteCollection = WasteCollection::create([
                            'amount' => $data['amount'],
                            'units' => 'kg', // Default unit, can be made dynamic if needed
                            'notes' => $data['notes'],
                            'collection_batch_id' => $record->id,
                            'collection_point_id' => $data['collection_point_id'],
                            'waste_type_id' => $data['waste_type_id'], // Helper function needed
                            'user_id' => auth()->id(),
                        ]);

                        if ($wasteCollection) {
                            Notification::make()
                                ->success()
                                ->title('Waste Collection Created')
                                ->body('Waste collection record has been successfully created.')
                                ->send();
                        }
                    })
                    ->modalWidth('3xl')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCollectionBatches::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
