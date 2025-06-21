<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CollectionRouteResource\Pages;
use App\Filament\Resources\CollectionRouteResource\RelationManagers;
use App\Models\CollectionRoute;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class CollectionRouteResource extends Resource
{
    protected static ?string $model = CollectionRoute::class;

    protected static ?string $navigationGroup = 'Route Management';

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Route Information')
                    ->description('Provide detailed information about the waste collection route.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Route Name')
                            ->required()
                            ->maxLength(161)
                            ->placeholder('e.g. Central Division Route A')
                            ->helperText('Enter a unique name to identify the collection route.'),

                        Forms\Components\Textarea::make('description')
                            ->label('Route Description')
                            ->placeholder('Briefly describe the route coverage or special notes...')
                            ->columnSpanFull()
                            ->helperText('Optional. A short description of this route for internal reference.'),

                        Forms\Components\Select::make('frequency')
                            ->label('Collection Frequency')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'bi-weekly' => 'Bi-Weekly',
                                'monthly' => 'Monthly',
                                'custom' => 'Custom'
                            ])
                            ->live()
                            ->required()
                            ->placeholder('Select frequency')
                            ->helperText('How often waste is collected along this route.'),

                        Forms\Components\CheckboxList::make('collection_days')
                            ->label('Collection Days')
                            ->options([
                                'Monday' => 'Monday',
                                'Tuesday' => 'Tuesday',
                                'Wednesday' => 'Wednesday',
                                'Thursday' => 'Thursday',
                                'Friday' => 'Friday',
                                'Saturday' => 'Saturday',
                                'Sunday' => 'Sunday',
                            ])
                            ->hidden(fn(Forms\Get $get) => $get('frequency') !== 'weekly' && $get('frequency') !== 'bi-weekly')
                            ->helperText('Select the days when collection occurs (for weekly or bi-weekly routes).'),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Start Time')
                            ->seconds(false)
                            ->native(false)
                            ->required()
                            ->default(Carbon::createFromTime(8, 0)) // 08:00 AM
                            ->displayFormat('h:i A') // Show AM/PM
                            ->placeholder('e.g. 08:00 AM')
                            ->helperText('When the route is scheduled to begin.'),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('End Time')
                            ->seconds(false)
                            ->native(false)
                            ->required()
                            ->default(Carbon::createFromTime(17, 0)) // 05:00 PM
                            ->displayFormat('h:i A') // Show AM/PM
                            ->placeholder('e.g. 05:00 PM')
                            ->helperText('Expected end time of the collection on this route.'),

                        Forms\Components\Select::make('status')
                            ->label('Route Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'pending' => 'Pending',
                            ])
                            ->visibleOn('edit')
                            ->required()
                            ->placeholder('Select status')
                            ->helperText('Status indicates whether the route is in use or pending activation.'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Additional Notes')
                            ->placeholder('Any extra instructions or internal notes...')
                            ->columnSpanFull()
                            ->helperText('Optional. Add any notes that may help operators or administrators.'),
                    ])
                    ->columns(2),
            ]);
    }




    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('frequency')->label('Collection Rounds'),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Start Time')
                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('h:i A') : null)
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('End Time')
                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('h:i A') : null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('wards_count')
                    ->label('Wards')
                    ->counts('wards') // this will auto-load the count
                    ->sortable(),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->label('Deleted On')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Last Updated On')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assignWards')
                    ->label('Assign Wards')
                    ->icon('heroicon-o-map')
                    ->url(fn(CollectionRoute $record) => static::getUrl('assign-wards', [$record])),

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
            'index' => Pages\ListCollectionRoutes::route('/'),
            'create' => Pages\CreateCollectionRoute::route('/create'),
            'view' => Pages\ViewCollectionRoute::route('/{record}'),
            'edit' => Pages\EditCollectionRoute::route('/{record}/edit'),
            'assign-wards' => Pages\AssignWards::route('/{record}/assign-wards'),
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
