<?php

namespace Tests\Feature;

use App\Models\IntegratorData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IntegratorDataApiTest extends TestCase
{
    use RefreshDatabase;

    protected $apiKey = 'alpesone-test-2024';

    /**
     * Test API authentication
     *
     * @return void
     */
    public function test_api_requires_authentication()
    {
        $response = $this->getJson('/api/integrator');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'API key is required'
            ]);
    }

    /**
     * Test listing integrator data with pagination
     *
     * @return void
     */
    public function test_can_list_integrator_data()
    {
        // Create some test data
        IntegratorData::factory()->count(5)->create();

        $response = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->getJson('/api/integrator');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'data',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                    'from',
                    'to'
                ]
            ]);
    }

    /**
     * Test creating new integrator data
     *
     * @return void
     */
    public function test_can_create_integrator_data()
    {
        $data = [
            'data' => ['key' => 'value', 'number' => 123],
            'source_url' => 'https://example.com/api/data'
        ];

        $response = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->postJson('/api/integrator', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Data created successfully'
            ]);

        $this->assertDatabaseHas('integrator_data', [
            'source_url' => 'https://example.com/api/data'
        ]);
    }

    /**
     * Test validation when creating data
     *
     * @return void
     */
    public function test_creation_validation()
    {
        $response = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->postJson('/api/integrator', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed'
            ])
            ->assertJsonValidationErrors(['data', 'source_url']);
    }

    /**
     * Test showing specific integrator data
     *
     * @return void
     */
    public function test_can_show_integrator_data()
    {
        $integratorData = IntegratorData::factory()->create();

        $response = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->getJson("/api/integrator/{$integratorData->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $integratorData->id
                ]
            ]);
    }

    /**
     * Test updating integrator data
     *
     * @return void
     */
    public function test_can_update_integrator_data()
    {
        $integratorData = IntegratorData::factory()->create();
        $updateData = [
            'data' => ['updated' => 'value'],
            'source_url' => 'https://updated.com/api/data'
        ];

        $response = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->putJson("/api/integrator/{$integratorData->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data updated successfully'
            ]);

        $this->assertDatabaseHas('integrator_data', [
            'id' => $integratorData->id,
            'source_url' => 'https://updated.com/api/data'
        ]);
    }

    /**
     * Test deleting integrator data
     *
     * @return void
     */
    public function test_can_delete_integrator_data()
    {
        $integratorData = IntegratorData::factory()->create();

        $response = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->deleteJson("/api/integrator/{$integratorData->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data deleted successfully'
            ]);

        $this->assertDatabaseMissing('integrator_data', [
            'id' => $integratorData->id
        ]);
    }

    /**
     * Test getting latest data
     *
     * @return void
     */
    public function test_can_get_latest_data()
    {
        // Create data with different timestamps
        $oldData = IntegratorData::factory()->create([
            'created_at' => now()->subHour()
        ]);
        
        $latestData = IntegratorData::factory()->create([
            'created_at' => now()
        ]);

        $response = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->getJson('/api/integrator/latest');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $latestData->id
                ]
            ]);
    }

    /**
     * Test pagination parameters
     *
     * @return void
     */
    public function test_pagination_parameters()
    {
        IntegratorData::factory()->count(25)->create();

        $response = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->getJson('/api/integrator?per_page=10');

        $response->assertStatus(200)
            ->assertJson([
                'pagination' => [
                    'per_page' => 10,
                    'total' => 25
                ]
            ]);
    }
}
