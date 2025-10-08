<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemHealth extends Model
{
    use HasFactory;

    protected $table = 'system_health';

    protected $fillable = [
        'cpu_usage',
        'memory_total',
        'memory_used',
        'memory_free',
        'memory_percent',
        'disk_total',
        'disk_used',
        'disk_free',
        'disk_percent',
        'load_average_1min',
        'load_average_5min',
        'load_average_15min',
        'active_connections',
        'total_processes',
        'network_in',
        'network_out',
        'uptime',
        'status',
        'alerts',
    ];

    protected $casts = [
        'cpu_usage' => 'float',
        'memory_total' => 'integer',
        'memory_used' => 'integer',
        'memory_free' => 'integer',
        'memory_percent' => 'float',
        'disk_total' => 'integer',
        'disk_used' => 'integer',
        'disk_free' => 'integer',
        'disk_percent' => 'float',
        'load_average_1min' => 'float',
        'load_average_5min' => 'float',
        'load_average_15min' => 'float',
        'active_connections' => 'integer',
        'total_processes' => 'integer',
        'network_in' => 'integer',
        'network_out' => 'integer',
        'alerts' => 'array',
        'created_at' => 'datetime',
    ];

    // Constantes de status
    const STATUS_HEALTHY = 'healthy';
    const STATUS_DEGRADED = 'degraded';
    const STATUS_CRITICAL = 'critical';

    // Scopes
    public function scopeHealthy($query)
    {
        return $query->where('status', self::STATUS_HEALTHY);
    }

    public function scopeCritical($query)
    {
        return $query->where('status', self::STATUS_CRITICAL);
    }

    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('created_at', '>', now()->subMinutes($minutes));
    }

    // Accessors
    public function getMemoryUsedFormattedAttribute(): string
    {
        return $this->formatBytes($this->memory_used);
    }

    public function getDiskUsedFormattedAttribute(): string
    {
        return $this->formatBytes($this->disk_used);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}