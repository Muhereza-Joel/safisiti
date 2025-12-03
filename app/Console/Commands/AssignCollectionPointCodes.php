<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CollectionPoint;
use Illuminate\Support\Facades\DB;

class AssignCollectionPointCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'codes:assign-collection-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates and assigns unique 6-character base36 codes, prefixed with "SAFISITI-", unique per Cell, using memory-efficient chunking.';

    /**
     * The prefix for the code.
     *
     * @var string
     */
    protected $codePrefix = 'SAFISITI-';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting to assign unique codes ({$this->codePrefix}xxxxxx, per Cell) to Collection Points...");

        $batchSize = 2000; // Efficient batch size for processing
        $processedCount = 0;

        // Count total points that need processing
        $totalPoints = CollectionPoint::whereNull('code')->whereNotNull('cell_id')->count();

        if ($totalPoints === 0) {
            $this->info('No Collection Points found that require a code (null or missing cell_id). Exiting.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($totalPoints);

        // 1. Iterate over the records that need an update using chunkById
        CollectionPoint::whereNull('code')
            ->whereNotNull('cell_id')
            ->orderBy('id')
            // chunkById uses less memory as it avoids offset/limit issues on large tables
            ->chunkById($batchSize, function ($points) use ($bar, &$processedCount) {

                // Group the current small chunk by cell_id
                $pointsByCell = $points->groupBy('cell_id');

                // 2. Process records, cell by cell, within the current chunk
                foreach ($pointsByCell as $cellId => $cellPoints) {

                    // Fetch ONLY the SUFFIXES that already exist for this cell from the database
                    // Use DB::table for a slightly leaner query than the Eloquent Model
                    $existingSuffixes = DB::table('collection_points')
                        ->where('cell_id', $cellId)
                        ->whereNotNull('code')
                        ->pluck('code')
                        ->map(function ($code) {
                            // Extract the 6-char suffix from the full code
                            return str_replace($this->codePrefix, '', $code);
                        })
                        ->flip() // Flip to create a fast lookup array (suffix => key)
                        ->all(); // Convert Collection to simple PHP array for maximum memory efficiency

                    foreach ($cellPoints as $point) {
                        $suffix = $this->generateUniqueCodeSuffix($existingSuffixes);

                        // Combine the prefix and the suffix
                        $fullCode = $this->codePrefix . $suffix;

                        // Assign the code and save
                        $point->code = $fullCode;
                        $point->save();

                        // Add the new suffix to the array for uniqueness within the batch/cell
                        $existingSuffixes[$suffix] = true;

                        $processedCount++;
                        $bar->advance();
                    }
                }
            });

        $bar->finish();
        $this->info("\nSuccessfully assigned codes to {$processedCount} Collection Points across multiple Cells.");

        return Command::SUCCESS;
    }

    /**
     * Generates a unique 6-character base36 code suffix.
     *
     * @param array $existingSuffixes A PHP array of existing suffixes for the current cell.
     * @return string
     */
    protected function generateUniqueCodeSuffix(array &$existingSuffixes): string
    {
        // $36^6 = 2,176,782,336 possible codes.
        $maxNum = pow(36, 6) - 1;

        do {
            // Generate a random 6-digit base36 code
            $randNum = random_int(0, $maxNum);
            $base36Code = base_convert($randNum, 10, 36);

            // Pad the code with leading zeros/characters to ensure it is exactly 6 characters long
            $suffix = strtoupper(str_pad($base36Code, 6, '0', STR_PAD_LEFT));

            // Check if the suffix is already in use within the current cell's existing suffixes
        } while (isset($existingSuffixes[$suffix]));

        return $suffix;
    }
}
