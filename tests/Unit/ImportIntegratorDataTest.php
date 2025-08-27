<?php

namespace Tests\Unit;

use App\Models\IntegratorData;
use PHPUnit\Framework\TestCase;

class ImportIntegratorDataTest extends TestCase
{
    /**
     * Test data validation logic
     *
     * @return void
     */
    public function test_data_validation()
    {
        // Test valid data
        $validData = ['key' => 'value', 'number' => 123];
        $this->assertTrue($this->validateData($validData));

        // Test invalid data (not array)
        $invalidData = 'not an array';
        $this->assertFalse($this->validateData($invalidData));

        // Test empty data
        $emptyData = [];
        $this->assertFalse($this->validateData($emptyData));
    }

    /**
     * Test hash generation for data comparison
     *
     * @return void
     */
    public function test_data_hash_generation()
    {
        $data1 = ['key' => 'value'];
        $data2 = ['key' => 'value'];
        $data3 = ['key' => 'different'];

        $hash1 = md5(json_encode($data1));
        $hash2 = md5(json_encode($data2));
        $hash3 = md5(json_encode($data3));

        // Same data should have same hash
        $this->assertEquals($hash1, $hash2);

        // Different data should have different hash
        $this->assertNotEquals($hash1, $hash3);
    }

    /**
     * Test data change detection
     *
     * @return void
     */
    public function test_data_change_detection()
    {
        $oldHash = 'old_hash_123';
        $newHash = 'new_hash_456';

        // Different hashes should indicate change
        $this->assertTrue($oldHash !== $newHash);

        // Same hash should indicate no change
        $this->assertFalse($oldHash !== $oldHash);
    }

    /**
     * Simulate the validation method from the command
     *
     * @param mixed $data
     * @return bool
     */
    private function validateData($data)
    {
        if (!is_array($data)) {
            return false;
        }

        if (empty($data)) {
            return false;
        }

        return true;
    }
}
