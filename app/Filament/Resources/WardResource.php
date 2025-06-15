<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WardResource\Pages;
use App\Filament\Resources\WardResource\RelationManagers;
use App\Models\Ward;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WardResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Ward::class;

    protected static ?string $navigationGroup = 'Administrative Units';

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 2;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            'export_data',
            'download_template',
        ];
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Ward Name')
                            ->placeholder('e.g. Central Ward')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Enter the official name of the ward.' : null)
                            ->required()
                            ->maxLength(161),

                        Forms\Components\TextInput::make('code')
                            ->label('Ward Code')
                            ->placeholder('e.g. WD-001')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Optional government or system code for this ward.' : null)
                            ->maxLength(161),
                    ]),

                Forms\Components\Section::make('Demographics')
                    ->schema([
                        Forms\Components\TextInput::make('population')
                            ->placeholder('e.g. 12000')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Estimated population in this ward.' : null)
                            ->numeric(),

                        Forms\Components\TextInput::make('area_sq_km')
                            ->label('Area (sq. km)')
                            ->placeholder('e.g. 14.6')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Approximate size of the ward in square kilometers.' : null)
                            ->numeric(),
                    ]),

                Forms\Components\Section::make('Geolocation')
                    ->description(fn() => $form->getOperation() !== 'view' ? 'Optional GPS coordinates for the center of the ward.' : null)
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->placeholder('e.g. -1.2921')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Latitude of the ward center point.' : null)
                            ->numeric(),

                        Forms\Components\TextInput::make('longitude')
                            ->placeholder('e.g. 36.8219')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Longitude of the ward center point.' : null)
                            ->numeric(),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->label('Description or Notes')
                            ->placeholder('Write any remarks or additional info about the ward...')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Add optional details, history, or remarks about the ward.' : null)
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
                    ->searchable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('population')
                    ->numeric()
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('area_sq_km')
                    ->numeric()
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('latitude')
                    ->numeric()
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('longitude')
                    ->numeric()
                    ->sortable()
                    ->placeholder('N/A'),

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
