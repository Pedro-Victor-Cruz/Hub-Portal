<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EndpointMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'endpoint',
        'method',
        'total_requests',
        'total_errors',
        'avg_response_time',
        'min_response_time',
        'max_response_time',
        'p50_response_time',
        'p95_response_time',
        'p99_response_time',
        'avg_memory_usage',
        'avg_cpu_usage',
        'avg_queries_count',
        'last_accessed_at',
        'date',
    ];

    protected $casts = [
        'total_requests' => 'integer',
        'total_errors' => 'integer',
        'avg_response_time' => 'float',
        'min_response_time' => 'float',
        'max_response_time' => 'float',
        'p50_response_time' => 'float',
        'p95_response_time' => 'float',
        'p99_response_time' => 'float',
        'avg_memory_usage' => 'float',
        'avg_cpu_usage' => 'float',
        'avg_queries_count' => 'float',
        'last_accessed_at' => 'datetime',
        'date' => 'date',
    ];

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeSlowest($query, $limit = 10)
    {
        return $query->orderByDesc('avg_response_time')->limit($limit);
    }

    public function scopeMostAccessed($query, $limit = 10)
    {
        return $query->orderByDesc('total_requests')->limit($limit);
    }
}