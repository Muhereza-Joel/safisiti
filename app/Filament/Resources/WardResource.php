<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WardResource\Pages;
use App\Filament\Resources\WardResource\RelationManagers;
use App\Models\Ward;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WardResource extends Resource
{
    protected static ?string $model = Ward::class;

    protected static ?string $navigationGroup = 'Administrative Units';

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Ward Name')
                            ->placeholder('e.g. Central Ward')
                            ->helperText('Enter the official name of the ward.')
                            ->required()
                            ->maxLength(161),

                        Forms\Components\TextInput::make('code')
                            ->label('Ward Code')
                            ->placeholder('e.g. WD-001')
                            ->helperText('Optional government or system code for this ward.')
                            ->maxLength(161),
                    ]),

                Forms\Components\Section::make('Demographics')
                    ->schema([
                        Forms\Components\TextInput::make('population')
                            ->placeholder('e.g. 12000')
                            ->helperText('Estimated population in this ward.')
                            ->numeric(),

                        Forms\Components\TextInput::make('area_sq_km')
                            ->label('Area (sq. km)')
                            ->placeholder('e.g. 14.6')
                            ->helperText('Approximate size of the ward in square kilometers.')
                            ->numeric(),
                    ]),

                Forms\Components\Section::make('Geolocation')
                    ->description('Optional GPS coordinates for the center of the ward.')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->placeholder('e.g. -1.2921')
                            ->helperText('Latitude of the ward center point.')
                            ->numeric(),

                        Forms\Components\TextInput::make('longitude')
                            ->placeholder('e.g. 36.8219')
                            ->helperText('Longitude of the ward center point.')
                            ->numeric(),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->label('Description or Notes')
                            ->placeholder('Write any remarks or additional info about the ward...')
                            ->helperText('Add optional details, history, or remarks about the ward.')
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
                            ->columnSpanFull(),
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('population')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('area_sq_km')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
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
            'index' => Pages\ListWards::route('/'),
            'create' => Pages\CreateWard::route('/create'),
            'view' => Pages\ViewWard::route('/{record}'),
            'edit' => Pages\EditWard::route('/{record}/edit'),
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
