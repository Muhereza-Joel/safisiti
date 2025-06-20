<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = "User Management";

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Organisation & Role Details')
                    ->description(fn() => $form->getOperation() !== 'view' ? 'Select the organisation and assign the appropriate role or privilege.' : null)
                    ->schema([
                        Forms\Components\Select::make('organisation_id')
                            ->label('Organisation')
                            ->relationship('organisation', 'name')
                            ->required()
                            ->preload()
                            ->native(false)
                            ->placeholder('Select an organisation')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'This user will belong to the selected organisation.' : null),

                        Forms\Components\Select::make('roles')
                            ->label('Privilege')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn($query) => $query->where('name', '!=', 'super_admin')
                            )
                            ->preload()
                            ->searchable()
                            ->multiple(false) // Explicit: one role per user
                            ->required()


                    ]),

                Forms\Components\Section::make('User Information')
                    ->description('Basic personal details for the user.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(161)
                            ->placeholder('Enter full name')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'This name will be used for identification.' : null),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(161)
                            ->placeholder('e.g. example@domain.com')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Make sure the email is valid and unique.' : null),
                    ]),

                Forms\Components\Section::make('Security')
                    ->description('Set or update the user\'s password.')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->maxLength(161)
                            ->placeholder('Enter a secure password')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Only required when creating a user. Leave blank to keep the current password.' : null)
                            ->dehydrated(fn($state) => filled($state)) // Save only if not empty

                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('organisation.name')
                    ->label('Member Entity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Fullname')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email Address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Prevelage')
                    ->sortable(),


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
            ->striped()
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
