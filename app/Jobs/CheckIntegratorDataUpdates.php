<?php

namespace App\Jobs;

use App\Models\IntegratorData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckIntegratorDataUpdates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The API URL for the integrator data
     */
    protected $apiUrl = 'https://hub.alpes.one/api/v1/integrator/export/1902';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('Starting scheduled integrator data update check');

            // Fetch data from API
            $response = Http::timeout(30)->get($this->apiUrl);
            
            if (!$response->successful()) {
                Log::warning('Integrator API request failed during scheduled check', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return;
            }

            $data = $response->json();
            
            if (empty($data)) {
                Log::info('No data received from API during scheduled check');
                return;
            }

            // Generate hash for data comparison
            $dataHash = md5(json_encode($data));
            
            // Check if data has changed
            if (IntegratorData::hasDataChanged($dataHash)) {
                Log::info('Data has changed, importing new data');
                
                // Store data in database
                IntegratorData::create([
                    'data' => $data,
                    'data_hash' => $dataHash,
                    'last_updated' => now(),
                    'source_url' => $this->apiUrl
                ]);

                Log::info('New integrator data imported during scheduled check', [
                    'records_count' => count($data),
                    'data_hash' => $dataHash
                ]);
            } else {
                Log::info('No changes detected in integrator data during scheduled check');
            }

        } catch (\Exception $e) {
            Log::error('Scheduled integrator data update check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
