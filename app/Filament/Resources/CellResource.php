<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CellResource\Pages;
use App\Filament\Resources\CellResource\RelationManagers;
use App\Models\Cell;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;

class CellResource extends Resource
{
    protected static ?string $model = Cell::class;

    protected static ?string $navigationGroup = 'Administrative Units';

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        // If not super_admin, filter by organisation_id
        if (!Auth::user()->hasRole('super_admin')) {
            $query->where('organisation_id', Auth::user()->organisation_id);
        }

        return (string) $query->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->placeholder('e.g. Kisenyi Cell')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Enter the name of the entity (e.g. household, cell, etc.)' : null)
                            ->required()
                            ->maxLength(161),
                    ]),

                Forms\Components\Section::make('Ward Association')
                    ->description(fn() => $form->getOperation() !== 'view' ? 'Associate this record with the appropriate ward.' : null)
                    ->schema([
                        Forms\Components\Select::make('ward_id')
                            ->label('Ward')
                            ->relationship(
                                name: 'ward',
                                titleAttribute: 'name',
                                modifyQueryUsing: function ($query) {
                                    // Only select id and name columns
                                    $query->select('id', 'name');

                                    // Apply organization filter if not Administrator
                                    if (!auth()->user()->hasRole('System Administrator')) {
                                        $query->where('organisation_id', Auth::user()->organisation_id);
                                    }

                                    return $query;
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ward.name')
                    ->numeric()
                    ->searchable()
                    ->sortable(),

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
            ->striped()
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    FilamentExportBulkAction::make('export')
                ]),
            ])->defaultSort('created_at', 'desc');;
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
            'index' => Pages\ListCells::route('/'),
            'create' => Pages\CreateCell::route('/create'),
            'view' => Pages\ViewCell::route('/{record}'),
            'edit' => Pages\EditCell::route('/{record}/edit'),
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
            $query->where('organisation_id', Auth::user()->organisation_id);
        }

        return $query;
    }
}
