<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AwarenessCampaignResource\Pages;
use App\Filament\Resources\AwarenessCampaignResource\RelationManagers;
use App\Models\AwarenessCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AwarenessCampaignResource extends Resource
{
    protected static ?string $model = AwarenessCampaign::class;

    protected static ?string $navigationGroup = 'Waste Management';

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(161),
                Forms\Components\RichEditor::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('location')
                    ->maxLength(161),
                Forms\Components\DatePicker::make('date_conducted')
                    ->required(),
                Forms\Components\TextInput::make('participants_count')
                    ->required()
                    ->numeric()
                    ->default(0),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inspector.name')
                    ->label('Health Inspector')
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Campaign Title')
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_conducted')
                    ->placeholder('N/A')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('participants_count')
                    ->placeholder('N/A')
                    ->numeric()
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
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAwarenessCampaigns::route('/'),
            'create' => Pages\CreateAwarenessCampaign::route('/create'),
            'view' => Pages\ViewAwarenessCampaign::route('/{record}'),
            'edit' => Pages\EditAwarenessCampaign::route('/{record}/edit'),
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
