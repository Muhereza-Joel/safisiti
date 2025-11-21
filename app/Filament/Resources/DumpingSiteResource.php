<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DumpingSiteResource\Pages;
use App\Filament\Resources\DumpingSiteResource\RelationManagers;
use App\Models\DumpingSite;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DumpingSiteResource extends Resource
{
    protected static ?string $model = DumpingSite::class;

    protected static ?string $navigationGroup = 'Waste Management';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 2;

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
                            ->required()
                            ->maxLength(161)
                            ->placeholder('Enter the location name')
                            ->helperText('The official name of the location (e.g. Central Park, Kitere Dumping Site)'),

                        Forms\Components\RichEditor::make('description')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'blockquote',
                                'redo',
                                'undo',
                            ])
                            ->columnSpanFull()
                            ->placeholder('Enter a detailed description of the location')
                            ->helperText('Describe the location, its features, and any important details'),
                    ]),

                Forms\Components\Section::make('Geographical Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('location')
                            ->maxLength(161)
                            ->placeholder('Enter the address or general area')
                            ->helperText('The physical address or general location (e.g. Fort Portal, Kitere)'),

                        Forms\Components\TextInput::make('latitude')
                            ->required()
                            ->numeric()
                            ->placeholder('e.g. 40.7128')
                            ->helperText('Decimal degrees format (between -90 and 90)'),

                        Forms\Components\TextInput::make('longitude')
                            ->required()
                            ->numeric()
                            ->placeholder('e.g. -74.0060')
                            ->helperText('Decimal degrees format (between -180 and 180)'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->placeholder('N/A')
                    ->numeric(
                        decimalPlaces: 4,
                        decimalSeparator: '.',
                        thousandsSeparator: ''
                    )
                    ->sortable()
                    ->formatStateUsing(
                        fn($state): string =>
                        is_numeric($state) ? number_format((float)$state, 5) : $state
                    ),
                Tables\Columns\TextColumn::make('longitude')
                    ->placeholder('N/A')
                    ->numeric(
                        decimalPlaces: 5,
                        decimalSeparator: '.',
                        thousandsSeparator: ''
                    )
                    ->sortable()
                    ->formatStateUsing(
                        fn($state): string =>
                        is_numeric($state) ? number_format((float)$state, 5) : $state
                    ),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListDumpingSites::route('/'),
            'create' => Pages\CreateDumpingSite::route('/create'),
            'view' => Pages\ViewDumpingSite::route('/{record}'),
            'edit' => Pages\EditDumpingSite::route('/{record}/edit'),
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
