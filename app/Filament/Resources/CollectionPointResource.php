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
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use TCPDF;
use ZipArchive;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Actions\BulkAction;

class CollectionPointResource extends Resource
{
    protected static ?string $model = CollectionPoint::class;

    protected static ?string $navigationGroup = 'Administrative Units';

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?int $navigationSort = 4;

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
                            ->placeholder('e.g. Central Market Collection Point')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'The official name of this collection point' : null),

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
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Type of establishment' : null),

                        Forms\Components\TextInput::make('head_name')
                            ->required()
                            ->maxLength(161)
                            ->placeholder('e.g. Mugume Joseph')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Name of the person in charge' : null),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Contact Details')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(161)
                            ->placeholder('e.g. 255712345678')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Include country code without + sign' : null),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(161)
                            ->placeholder('e.g. contact@example.com')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Optional email address' : null),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Location Details')
                    ->schema([
                        Forms\Components\Select::make('ward_id')
                            ->relationship(
                                name: 'ward',
                                titleAttribute: 'name',
                                modifyQueryUsing: function ($query) {
                                    // Only select id and name columns
                                    $query->select('id', 'name');

                                    // Apply organization filter if not Administrator
                                    if (!auth()->user()->hasRole('System Administrator')) {
                                        $query->where('organisation_id', auth()->user()->organisation_id);
                                    }

                                    return $query;
                                }
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live() // Makes the field update other fields on change
                            ->afterStateUpdated(fn(callable $set) => $set('cell_id', null)) // Reset cell when ward changes
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Select the ward where this point is located' : null),

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
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Select the cell where this point is located' : null),

                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder('Full physical address with landmarks')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Detailed address information for collection teams' : null),

                        Forms\Components\TextInput::make('latitude')
                            ->required()
                            ->numeric()
                            ->placeholder('e.g. -6.7924')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'GPS latitude coordinate (decimal format)' : null),

                        Forms\Components\TextInput::make('longitude')
                            ->required()
                            ->numeric()
                            ->placeholder('e.g. 39.2083')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'GPS longitude coordinate (decimal format)' : null),
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
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Physical type of the collection point' : null),

                        Forms\Components\TextInput::make('household_size')
                            ->required()
                            ->numeric()
                            ->placeholder('e.g. 150')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Approximate number of households served' : null),

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
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Primary type of waste collected' : null),

                        Forms\Components\Select::make('collection_frequency')
                            ->required()
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'biweekly' => 'Bi-Weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->placeholder('Select frequency')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'How often waste is collected' : null),

                        Forms\Components\TextInput::make('bin_count')
                            ->required()
                            ->numeric()
                            ->placeholder('e.g. 4')
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Number of bins/containers at this point' : null),

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
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Primary material of the bins' : null),


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
                            ->helperText(fn() => $form->getOperation() !== 'view' ? 'Additional notes for collection teams' : null),
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
                Tables\Columns\TextColumn::make('head_name')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ward.name')
                    ->placeholder('N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cell.name')
                    ->placeholder('N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->placeholder('N/A')
                    ->formatStateUsing(fn($state) => number_format($state, 5, '.', ''))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude')
                    ->placeholder('N/A')
                    ->formatStateUsing(fn($state) => number_format($state, 5, '.', ''))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('structure_type')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('household_size')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('N/A')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bin_count')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('N/A')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bin_type')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('N/A')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_collection_date')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('N/A')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->placeholder('N/A')
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
            ->paginationPageOptions([10, 25, 50, 100, 200])
            ->filters([
                Tables\Filters\SelectFilter::make('ward')
                    ->relationship('ward', 'name', function (Builder $query) {
                        // Respect organization scoping from the form
                        if (!auth()->user()->hasRole('System Administrator')) {
                            $query->where('organisation_id', auth()->user()->organisation_id);
                        }
                        return $query;
                    })
                    ->searchable()
                    ->preload()
                    ->label('Ward'),

                Tables\Filters\SelectFilter::make('cell')
                    ->relationship('cell', 'name', function (Builder $query) {
                        // Scope cells by the user's organization via the ward
                        if (!auth()->user()->hasRole('System Administrator')) {
                            $query->whereHas('ward', function (Builder $query) {
                                $query->where('organisation_id', auth()->user()->organisation_id);
                            });
                        }
                        return $query;
                    })
                    ->searchable()
                    ->preload()
                    ->label('Cell'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Date From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'From ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Until ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),


                Tables\Filters\TrashedFilter::make(),
            ], layout: FiltersLayout::AboveContent)
            ->striped()
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])->defaultSort('created_at', 'desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    FilamentExportBulkAction::make('export'),
                    // --- ADD THIS NEW BULK ACTION ---
                    BulkAction::make('download_qr_zip')
                        ->label('Download QR Codes (ZIP)')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->action(function (Collection $records) {
                            // Create a unique zip file name in a temp directory
                            $zipFileName = 'collection_point_qrs_' . now()->format('Y-m-d_His') . '.zip';
                            $zipFilePath = storage_path('app/temp/' . $zipFileName);

                            // Ensure the temp directory exists
                            File::ensureDirectoryExists(storage_path('app/temp'));

                            $zip = new ZipArchive();

                            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                                throw new \Exception("Cannot open zip archive at $zipFilePath");
                            }

                            // Loop through selected records
                            foreach ($records as $record) {
                                // Generate the PDF content using our new helper function
                                $pdfContent = self::generateQrPdf($record);

                                // Sanitize the name for the file inside the zip
                                $fileNameInZip = preg_replace('/[^A-Za-z0-9\-_]/', '_', $record->name) . '_qr.pdf';

                                // Add the PDF string to the zip
                                $zip->addFromString($fileNameInZip, $pdfContent);
                            }

                            $zip->close();

                            // Download the zip file and delete it after sending
                            return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
                        })
                        ->deselectRecordsAfterCompletion(),
                    // --- END OF NEW BULK ACTION ---
                ]),
            ])->recordClasses(function (Model $record) {
                return $record->category
                    ? 'record-' . $record->category
                    : '';
            })
        ;
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

    public static function generateQrPdf(CollectionPoint $record): string
    {
        $qrValue = url('/dashboard/collection-points/' . $record->uuid);

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetCreator('SafiSiti System');
        $pdf->SetAuthor('SafiSiti');
        $pdf->SetTitle($record->name . ' QR Code');

        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        // --- HEADER ---
        $pdf->SetFont('helvetica', 'B', 28);
        $pdf->Cell(0, 10, 'SAFISITI', 0, 1, 'C');

        $pdf->SetFont('helvetica', 'I', 14);
        $pdf->Cell(0, 8, 'Keeping Our City Clean', 0, 1, 'C');
        $pdf->Ln(8);

        // --- CTA ---
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 12, 'SCAN ME TO PICK WASTE FROM HERE', 0, 1, 'C');
        $pdf->Ln(10);

        // --- LOCATION DETAILS ---
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 8, strtoupper($record->name), 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 6, $record->address ?? '', 0, 1, 'C');
        $pdf->Ln(8);

        // --- QR CODE (TCPDF native) ---
        $style = [
            'border' => 0,
            'vpadding' => 0,
            'hpadding' => 0,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false
        ];

        /**
         * write2DBarcode(
         * $code, $type, $x, $y, $w, $h, $style, $align
         * )
         */
        $pdf->write2DBarcode($qrValue, 'QRCODE,H', 55, $pdf->GetY(), 100, 100, $style, 'N');
        $pdf->Ln(115);

        // --- FOOTER ---
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetY(-55);
        $pdf->Cell(0, 6, 'FROM ToroDev-ODA Under The Datacities Project.', 0, 1, 'C');
        $pdf->Cell(0, 6, 'Powered By MOELS GROUP: www.moelsgroup.com', 0, 1, 'C');

        // Output PDF as a string
        return $pdf->Output($record->name . '_qr.pdf', 'S');
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
