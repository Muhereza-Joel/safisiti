<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WasteCollectionResource\Pages;
use App\Filament\Resources\WasteCollectionResource\RelationManagers;
use App\Models\WasteCollection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WasteCollectionResource extends Resource
{
    protected static ?string $model = WasteCollection::class;

    protected static ?string $navigationGroup = 'Waste Collection & Recycling';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Field Collections';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('units')
                    ->required()
                    ->maxLength(161)
                    ->default('kg'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\Select::make('collection_batch_id')
                    ->label("Collection Batch Number")
                    ->relationship("collectionBatch", "collection_batch_number")
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('collection_point_id')
                    ->label("Collection Point")
                    ->relationship("collectionPoint", "name")
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('waste_type_id')
                    ->label("Waste Type")
                    ->relationship("wasteType", "name")
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('user_id')
                    ->label("Service Provider")
                    ->relationship("user", "name")
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('user_uuid')
                    ->maxLength(36),

                Forms\Components\Toggle::make('segregated')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label("Service Provider")->searchable(),
                Tables\Columns\TextColumn::make('collectionBatch.collection_batch_number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('collectionPoint.name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('wasteType.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Quantity')
                    ->getStateUsing(fn($record) => "{$record->amount} {$record->units}")
                    ->sortable(query: function ($query, $direction) {
                        $query->orderBy('amount', $direction);
                    }),

                Tables\Columns\IconColumn::make('segregated')
                    ->boolean(),
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
                //
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWasteCollections::route('/'),
            'create' => Pages\CreateWasteCollection::route('/create'),
            'view' => Pages\ViewWasteCollection::route('/{record}'),
            'edit' => Pages\EditWasteCollection::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        // If not an Administrator, scope to the user's organisation
        if (!auth()->user()->hasRole('System Administrator')) {
            $query->where('organisation_id', auth()->user()->organisation_id);
        }

        return $query;
    }
}
