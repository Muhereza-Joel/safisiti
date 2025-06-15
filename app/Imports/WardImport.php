<?php

namespace App\Imports;

use App\Models\Ward;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Throwable;

class WardImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    /**
     * @param array $row
     * @return Ward|null
     */
    public function model(array $row)
    {
        // Find existing ward by code
        $existingWard = Ward::where('code', $row['code'])->first();

        // If ward exists, update it
        if ($existingWard) {
            $existingWard->update([
                'name' => $row['name'],
                'population' => $row['population'] ?? null,
                'area_sq_km' => $row['area_sq_km'] ?? null,
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'description' => $row['description'] ?? null,
                // organisation_id is not updated to maintain data integrity
            ]);
            return null; // Return null since we're updating, not creating
        }

        // Create new ward if code doesn't exist
        return new Ward([
            'name' => $row['name'],
            'code' => $row['code'],
            'population' => $row['population'] ?? null,
            'area_sq_km' => $row['area_sq_km'] ?? null,
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'description' => $row['description'] ?? null,
            'organisation_id' => Auth::user()->organisation_id,
        ]);
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'population' => 'nullable|numeric',
            'area_sq_km' => 'nullable|numeric',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'description' => 'nullable|string',
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            'longitude.between' => 'Longitude must be between -180 and 180 degrees.',
        ];
    }

    /**
     * Handle import errors
     */
    public function onError(Throwable $e)
    {
        // Error handling logic can be added here
    }
}
