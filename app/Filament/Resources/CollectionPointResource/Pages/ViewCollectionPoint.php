<?php

namespace App\Filament\Resources\CollectionPointResource\Pages;

use App\Filament\Resources\CollectionPointResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Models\CollectionPoint;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use TCPDF;

class ViewCollectionPoint extends ViewRecord
{
    protected static string $resource = CollectionPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('download_qr_pdf')
                ->label('Download QR Code')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (CollectionPoint $record) {

                    // Call the static helper method from the resource
                    $output = CollectionPointResource::generateQrPdf($record);

                    return response()->streamDownload(
                        fn() => print($output),
                        $record->uuid . '_qr.pdf'
                    );
                }),

        ];
    }
}
