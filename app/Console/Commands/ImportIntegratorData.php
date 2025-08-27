<?php

namespace App\Console\Commands;

use App\Models\IntegratorData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportIntegratorData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrator:import {--force : Force import even if data has not changed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from Alpes One integrator API';

    /**
     * The API URL for the integrator data
     */
    protected $apiUrl = 'https://hub.alpes.one/api/v1/integrator/export/1902';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting integrator data import...');

        try {
            // Fetch data from API
            $response = Http::timeout(30)->get($this->apiUrl);
            
            if (!$response->successful()) {
                $this->error('Failed to fetch data from API. Status: ' . $response->status());
                Log::error('Integrator API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return 1;
            }

            $data = $response->json();
            
            if (empty($data)) {
                $this->warn('No data received from API');
                return 0;
            }

            // Generate hash for data comparison
            $dataHash = md5(json_encode($data));
            
            // Check if data has changed
            if (!IntegratorData::hasDataChanged($dataHash) && !$this->option('force')) {
                $this->info('Data has not changed since last import. Use --force to import anyway.');
                return 0;
            }

            // Validate data structure
            if (!$this->validateData($data)) {
                $this->error('Data validation failed');
                return 1;
            }

            // Store data in database
            IntegratorData::create([
                'data' => $data,
                'data_hash' => $dataHash,
                'last_updated' => now(),
                'source_url' => $this->apiUrl
            ]);

            $this->info('Data imported successfully!');
            $this->info('Records imported: ' . count($data));
            
            Log::info('Integrator data imported successfully', [
                'records_count' => count($data),
                'data_hash' => $dataHash
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            Log::error('Integrator data import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Validate the structure of the imported data
     *
     * @param array $data
     * @return bool
     */
    protected function validateData($data)
    {
        if (!is_array($data)) {
            $this->error('Data is not an array');
            return false;
        }

        // Add more specific validation rules based on the expected data structure
        // For now, we'll just check if it's not empty
        if (empty($data)) {
            $this->error('Data is empty');
            return false;
        }

        return true;
    }
}
