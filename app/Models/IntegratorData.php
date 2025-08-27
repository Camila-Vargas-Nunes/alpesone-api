<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntegratorData extends Model
{
    use HasFactory;

    protected $fillable = [
        'data',
        'data_hash',
        'last_updated',
        'source_url'
    ];

    protected $casts = [
        'data' => 'array',
        'last_updated' => 'datetime'
    ];

    /**
     * Get the latest data from the integrator
     */
    public static function getLatest()
    {
        return static::latest('last_updated')->first();
    }

    /**
     * Check if data has changed based on hash
     */
    public static function hasDataChanged($newHash)
    {
        $latest = static::getLatest();
        return !$latest || $latest->data_hash !== $newHash;
    }
}
