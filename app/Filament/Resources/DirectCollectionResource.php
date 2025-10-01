<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DirectCollectionResource\Pages;
use App\Filament\Resources\DirectCollectionResource\RelationManagers;
use App\Models\DirectCollection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DirectCollectionResource extends Resource
{
    protected static ?string $model = DirectCollection::class;

    protected static ?string $navigationGroup = 'Waste Collection & Recycling';

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Site Collections';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\TextInput::make('name')
                    ->label("Brought By")
                    ->maxLength(161),
                Forms\Components\TextInput::make('contact')
                    ->maxLength(161),
                Forms\Components\TextInput::make('quantity')
                    ->numeric(),
                Forms\Components\TextInput::make('units')
                    ->maxLength(161),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\Select::make('waste_type_id')
                    ->label('Waste Type')
                    ->relationship('wasteType', 'name') // adjust "name" to the column you want to show
                    ->searchable()
                    ->preload()
                    ->required(),


                Forms\Components\Toggle::make('segregated')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->label('Recieved By')
                    ->relationship('user', 'name') // use the relationship method + the display column
                    ->searchable()
                    ->preload()
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dumpingSite.name')
                    ->searchable()
                    ->label('Dumping Site'),
                Tables\Columns\TextColumn::make('name')
                    ->label("Brought By")
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label("Recieved By"),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->getStateUsing(fn($record) => "{$record->quantity} {$record->units}")
                    ->sortable(query: function ($query, $direction) {
                        $query->orderBy('quantity', $direction);
                    }),

                Tables\Columns\TextColumn::make('wasteType.name')
                    ->numeric()
                    ->sortable(),
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])->defaultSort('created_at', 'desc'); // 👈 sort latest first;
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
            'index' => Pages\ListDirectCollections::route('/'),
            'create' => Pages\CreateDirectCollection::route('/create'),
            'view' => Pages\ViewDirectCollection::route('/{record}'),
            'edit' => Pages\EditDirectCollection::route('/{record}/edit'),
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
