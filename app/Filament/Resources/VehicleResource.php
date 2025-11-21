<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationGroup = 'Waste Management';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Vehicle Information Section
                Forms\Components\Section::make('Vehicle Details')
                    ->description('Enter the vehicle specifications')
                    ->schema([
                        Forms\Components\TextInput::make('registration_number')
                            ->required()
                            ->maxLength(161)
                            ->placeholder('e.g. UBA 123A or KCA 789B')
                            ->helperText('Official vehicle registration number as issued by URA')
                            ->label('Registration Plate No.'),

                        Forms\Components\TextInput::make('model')
                            ->maxLength(161)
                            ->placeholder('e.g. Toyota Hiace, Isuzu NPR')
                            ->helperText('Vehicle make and model')
                            ->label('Vehicle Model'),

                        Forms\Components\TextInput::make('capacity')
                            ->maxLength(161)
                            ->placeholder('e.g. 4 passengers or 3.5 tons')
                            ->helperText('Passenger capacity or load capacity in metric tons')
                            ->label('Capacity'),

                        Forms\Components\TextInput::make('type')
                            ->maxLength(161)
                            ->placeholder('e.g. Saloon, Lorry, Minibus')
                            ->helperText('Vehicle classification type')
                            ->label('Vehicle Type'),

                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->placeholder('Additional details like color, special features...')
                            ->helperText('Describe any distinctive characteristics of the vehicle')
                            ->label('Additional Description'),
                    ]),

                // Owner/User Section
                Forms\Components\Section::make('Ownership Information')
                    ->description('Assign vehicle to owner')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->whereHas('roles', function ($q) {
                                    $q->where('name', 'Service Provider');
                                })
                            )
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->preload()
                            ->placeholder('Search for service provider...')
                            ->helperText('Only users with Service Provider role can be selected')
                            ->label('Service Provider')
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('registration_number')
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->placeholder('N/A')
                    ->label('Service Provider')
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
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
