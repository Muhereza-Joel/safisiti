<?php

namespace App\Filament\Resources\WardResource\Pages;

use App\Exports\WardTemplateExport;
use App\Filament\Resources\WardResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\FileUpload;
use App\Imports\WardImport;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ListWards extends ListRecords
{
    protected static string $resource = WardResource::class;

    public function getTitle(): string
    {
        $org = Auth::user()?->organisation?->name ?? 'Wards';
        return $org . ' Wards';
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Action::make('Download Wards Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->requiresConfirmation() // Triggers a modal before action runs
                ->modalHeading('Download Ward Excel Template')
                ->visible(auth()->user()->can('download_template_ward'))
                ->modalSubheading('This Excel file contains the required structure for uploading multiple wards at ago. Please fill in the rows with real data and do not edit the headers. You are required to remove the example row, because it is used just as a guide.')
                ->action(function () {
                    return Excel::download(new WardTemplateExport, 'ward-template.xlsx');
                })->label('Download Excel Template'),

            Action::make('Import Wards')
                ->form([
                    FileUpload::make('excel_file')
                        ->label('Upload Excel File')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel'
                        ])
                        ->visible(auth()->user()->can('create_ward'))
                        ->preserveFilenames()
                        ->required()
                        ->disk('local') // Make sure this matches your filesystem config
                        ->directory('imports'), // Custom directory for imports
                ])
                ->action(function (array $data) {
                    try {
                        // Get the full path to the uploaded file
                        $filePath = Storage::disk('local')->path($data['excel_file']);

                        // Verify file exists before importing
                        if (!file_exists($filePath)) {
                            throw new \Exception("The uploaded file could not be found.");
                        }

                        // Perform the import
                        Excel::import(new WardImport, $filePath);

                        // Delete the file after import
                        Storage::disk('local')->delete($data['excel_file']);

                        Notification::make()
                            ->title('Wards imported successfully!')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        // Delete the file if import fails
                        if (isset($data['excel_file']) && Storage::disk('local')->exists($data['excel_file'])) {
                            Storage::disk('local')->delete($data['excel_file']);
                        }

                        Notification::make()
                            ->title('Import failed')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->label('Upload Excel Template')
                ->icon('heroicon-o-arrow-up-tray')
                ->requiresConfirmation()
                ->modalHeading('Import Wards From Template')
                ->visible(auth()->user()->can('create_ward'))
                ->modalSubmitActionLabel('Upload Wards')
                ->modalSubheading('Please make sure you are uploading the official wards template you downloaded. Using the correct format ensures a successful import.')

        ];
    }
}
