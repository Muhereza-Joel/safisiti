<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecyclingMethodResource\Pages;
use App\Filament\Resources\RecyclingMethodResource\RelationManagers;
use App\Models\RecyclingMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class RecyclingMethodResource extends Resource
{
    protected static ?string $model = RecyclingMethod::class;

    protected static ?string $navigationGroup = 'Waste Management';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?int $navigationSort = 5;

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
                Forms\Components\Section::make('Recycling Method Details')
                    ->description('Define a new recycling process or technique')
                    ->icon('heroicon-o-arrow-path') // Recycling icon
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(161)
                            ->placeholder('e.g., "Plastic Bottle Upcycling" or "E-Waste Dismantling"')
                            ->helperText('Give this recycling method a clear, descriptive name')
                            ->label('Method Name'),

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
                            ->placeholder('Describe the recycling process step-by-step...')
                            ->helperText('Include: materials required, safety precautions, expected outcomes')
                            ->columnSpanFull()
                            ->label('Process Description'),
                    ]),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
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
            'index' => Pages\ListRecyclingMethods::route('/'),
            'create' => Pages\CreateRecyclingMethod::route('/create'),
            'view' => Pages\ViewRecyclingMethod::route('/{record}'),
            'edit' => Pages\EditRecyclingMethod::route('/{record}/edit'),
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
