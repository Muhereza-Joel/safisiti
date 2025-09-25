<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecycleRecordResource\Pages;
use App\Filament\Resources\RecycleRecordResource\RelationManagers;
use App\Models\RecycleRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecycleRecordResource extends Resource
{
    protected static ?string $model = RecycleRecord::class;

    protected static ?string $navigationGroup = 'Waste Collection & Recycling';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('recycling_center_id')
                    ->label('Recycling Center')
                    ->relationship('recyclingCenter', 'name') // adjust "name" to the column you want to show
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('recycling_method_id')
                    ->label('Recycling Method')
                    ->relationship('recyclingMethod', 'name') // adjust "name" to the column you want to show
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('units')
                    ->required()
                    ->maxLength(161)
                    ->default('kgs'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('recyclingCenter.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('recyclingMethod.name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->getStateUsing(fn($record) => "{$record->quantity} {$record->units}")
                    ->sortable(query: function ($query, $direction) {
                        $query->orderBy('quantity', $direction);
                    }),
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
            ]);
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
            'index' => Pages\ListRecycleRecords::route('/'),
            'create' => Pages\CreateRecycleRecord::route('/create'),
            'view' => Pages\ViewRecycleRecord::route('/{record}'),
            'edit' => Pages\EditRecycleRecord::route('/{record}/edit'),
        ];
    }
}
