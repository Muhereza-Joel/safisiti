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
                ->label('Download QR Code PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (CollectionPoint $record) {

                    $qrValue = url('/dashboard/collection-points/' . $record->uuid);

                    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
                    $pdf->SetCreator('SafiSiti System');
                    $pdf->SetAuthor('SafiSiti');
                    $pdf->SetTitle($record->name . ' QR Code');
                    $pdf->SetMargins(20, 20, 20, true);
                    $pdf->AddPage();

                    $pdf->SetFont('helvetica', 'B', 18);
                    $pdf->Cell(0, 10, $record->name . ' - QR Code', 0, 1, 'C');
                    $pdf->Ln(5);

                    $style = [
                        'border' => false,
                        'padding' => 0,
                        'fgcolor' => [0, 0, 0],
                        'bgcolor' => false,
                    ];

                    $pdf->write2DBarcode($qrValue, 'QRCODE,H', 60, 50, 90, 90, $style, 'N');

                    $pdf->Ln(100);

                    $pdf->SetFont('helvetica', '', 12);
                    $pdf->Cell(0, 10, $qrValue, 0, 1, 'C');

                    // ✅ Get PDF output as string (not direct download)
                    $output = $pdf->Output($record->name . '_qr.pdf', 'S');

                    // ✅ Return a Laravel download response (this stops the spinner)
                    return response()->streamDownload(
                        fn() => print($output),
                        $record->name . '_qr.pdf'
                    );
                }),

        ];
    }
}
