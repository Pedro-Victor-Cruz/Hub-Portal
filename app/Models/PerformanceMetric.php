<?php

// ========== PerformanceMetric.php ==========
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'endpoint',
        'method',
        'status_code',
        'response_time',
        'memory_usage',
        'memory_peak',
        'cpu_usage',
        'queries_count',
        'user_id',
        'ip_address',
        'user_agent',
        'request_data',
        'response_data',
        'session_id',
    ];

    protected $casts = [
        'response_time' => 'float',
        'memory_usage' => 'integer',
        'memory_peak' => 'integer',
        'cpu_usage' => 'float',
        'queries_count' => 'integer',
        'request_data' => 'array',
        'response_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function queries()
    {
        return $this->hasMany(QueryPerformance::class, 'performance_metric_id');
    }

    // Scopes
    public function scopeSlow($query, $threshold = 1000)
    {
        return $query->where('response_time', '>', $threshold);
    }

    public function scopeErrors($query)
    {
        return $query->where('status_code', '>=', 400);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeByEndpoint($query, $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }
}