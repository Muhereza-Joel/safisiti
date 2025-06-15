<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectionPointResource\Pages;
use App\Filament\Resources\CollectionPointResource\RelationManagers;
use App\Models\CollectionPoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CollectionPointResource extends Resource
{
    protected static ?string $model = CollectionPoint::class;

    protected static ?string $navigationGroup = 'Administrative Units';

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(161)
                            ->placeholder('e.g. Central Market Collection Point')
                            ->helperText('The official name of this collection point'),

                        Forms\Components\Select::make('category')
                            ->required()
                            ->options([
                                'household' => 'Household',
                                'market' => 'Market',
                                'school' => 'School',
                                'hospital' => 'Hospital',
                                'clinic' => 'Clinic',
                                'restaurant' => 'Restaurant',
                                'hotel' => 'Hotel',
                                'office' => 'Office',
                                'shop' => 'Shop',
                                'supermarket' => 'Supermarket',
                                'other' => 'Other',
                            ])
                            ->native(false)
                            ->placeholder('Select Category')
                            ->helperText('Type of establishment'),

                        Forms\Components\TextInput::make('head_name')
                            ->required()
                            ->maxLength(161)
                            ->placeholder('e.g. Mugume Joseph')
                            ->helperText('Name of the person in charge'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Contact Details')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(161)
                            ->placeholder('e.g. 255712345678')
                            ->helperText('Include country code without + sign'),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(161)
                            ->placeholder('e.g. contact@example.com')
                            ->helperText('Optional email address'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Location Details')
                    ->schema([
                        Forms\Components\Select::make('ward_id')
                            ->relationship(
                                name: 'ward',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->where('organisation_id', auth()->user()->organisation_id)
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live() // Makes the field update other fields on change
                            ->afterStateUpdated(fn(callable $set) => $set('cell_id', null)) // Reset cell when ward changes
                            ->helperText('Select the ward where this point is located'),

                        Forms\Components\Select::make('cell_id')
                            ->options(function (callable $get) {
                                $wardId = $get('ward_id');
                                if (!$wardId) {
                                    return [];
                                }
                                return \App\Models\Cell::where('ward_id', $wardId)
                                    ->pluck('name', 'id');
                            })
                            ->label('Cell')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select the cell where this point is located'),

                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder('Full physical address with landmarks')
                            ->helperText('Detailed address information for collection teams'),

                        Forms\Components\TextInput::make('latitude')
                            ->required()
                            ->numeric()
                            ->placeholder('e.g. -6.7924')
                            ->helperText('GPS latitude coordinate (decimal format)'),

                        Forms\Components\TextInput::make('longitude')
                            ->required()
                            ->numeric()
                            ->placeholder('e.g. 39.2083')
                            ->helperText('GPS longitude coordinate (decimal format)'),
                    ]),

                Forms\Components\Section::make('Collection Information')
                    ->schema([
                        Forms\Components\Select::make('structure_type')
                            ->required()
                            ->options([
                                'permanent' => 'Permanent',
                                'semi-permanent' => 'Semi-Permanent',
                                'temporary' => 'Temporary'
                            ])
                            ->native(false)
                            ->placeholder('Select type of structure')
                            ->helperText('Physical type of the collection point'),

                        Forms\Components\TextInput::make('household_size')
                            ->required()
                            ->numeric()
                            ->placeholder('e.g. 150')
                            ->helperText('Approximate number of households served'),

                        Forms\Components\Select::make('waste_type')
                            ->required()
                            ->options([
                                'domestic' => 'Mixed Waste',
                                'commercial' => 'Commercial',
                                'organic' => 'Organic Only',
                                'recyclable' => 'Recyclable Only',
                                'hazardous' => 'Hazardous Waste',
                                'mixed' => 'Mixed'
                            ])
                            ->native(false)
                            ->placeholder('Select waste type')
                            ->helperText('Primary type of waste collected'),

                        Forms\Components\Select::make('collection_frequency')
                            ->required()
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'biweekly' => 'Bi-Weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->placeholder('Select frequency')
                            ->helperText('How often waste is collected'),

                        Forms\Components\TextInput::make('bin_count')
                            ->required()
                            ->numeric()
                            ->placeholder('e.g. 4')
                            ->helperText('Number of bins/containers at this point'),

                        Forms\Components\Select::make('bin_type')
                            ->required()
                            ->options([
                                'plastic' => 'Plastic',
                                'metal' => 'Metal',
                                'concrete' => 'Concrete',
                                'other' => 'Other',
                            ])
                            ->native(false)
                            ->placeholder('Select bin material')
                            ->helperText('Primary material of the bins'),


                    ])
                    ->columns(3),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\RichEditor::make('notes')
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
                            ->placeholder('Any special instructions or observations')
                            ->helperText('Additional notes for collection teams'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\TextColumn::make('head_name')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('ward.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cell.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('structure_type')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('household_size')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('waste_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('collection_frequency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bin_count')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bin_type')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_collection_date')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date()
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
            'index' => Pages\ListCollectionPoints::route('/'),
            'create' => Pages\CreateCollectionPoint::route('/create'),
            'view' => Pages\ViewCollectionPoint::route('/{record}'),
            'edit' => Pages\EditCollectionPoint::route('/{record}/edit'),
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
