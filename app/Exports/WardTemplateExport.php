<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class WardTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents
{
    public function array(): array
    {
        return [
            [
                'Central Ward',       // name
                'WD-001',            // code
                12000,               // population
                14.6,                // area_sq_km
                -1.2921,             // latitude
                36.8219,            // longitude
                'Notes about the ward...',  // description
                null                // organisation_id (will be auto-filled)
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'name',
            'code',
            'population',
            'area_sq_km',
            'latitude',
            'longitude',
            'description',
            'organisation_id (leave blank for auto-assignment)'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Freeze top row (row 1)
                $sheet->freezePane('A2');

                // Make header row bold
                $sheet->getStyle('A1:H1')->getFont()->setBold(true);

                // Set number formats for numeric fields
                $sheet->getStyle('C2:E2')->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('F2:G2')->getNumberFormat()->setFormatCode('0.0000');

                // Add instructions
                $sheet->setCellValue('A3', 'INSTRUCTIONS:');
                $sheet->setCellValue('A4', '1. Remove this instruction row and example row before uploading');
                $sheet->setCellValue('A5', '2. organisation_id will be automatically set to your organization');
                $sheet->setCellValue('A6', '3. Required fields: name, code, latitude, longitude');

                // Make instructions bold
                $sheet->getStyle('A3:A6')->getFont()->setBold(true);

                // Auto-size columns
                foreach (range('A', 'H') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                // Protect the sheet but allow editing data rows
                $sheet->getProtection()->setSheet(true);

                // Lock only the header row (A1:H1)
                $sheet->getStyle('A1:H1')->getProtection()->setLocked(true);

                // Unlock all other cells (data rows)
                $sheet->getStyle('A2:H' . $sheet->getHighestRow())->getProtection()->setLocked(false);
            }
        ];
    }
}
