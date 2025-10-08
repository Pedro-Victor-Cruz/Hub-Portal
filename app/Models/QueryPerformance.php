<?php

// ========== QueryPerformance.php ==========
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueryPerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'performance_metric_id',
        'sql_query',
        'query_hash',
        'query_type',
        'duration',
        'table_name',
        'endpoint',
        'is_duplicate',
        'bindings',
        'stack_trace',
    ];

    protected $casts = [
        'duration' => 'float',
        'is_duplicate' => 'boolean',
        'bindings' => 'array',
        'stack_trace' => 'array',
        'created_at' => 'datetime',
    ];

    public function performanceMetric()
    {
        return $this->belongsTo(PerformanceMetric::class);
    }

    // Scopes
    public function scopeSlow($query, $threshold = 100)
    {
        return $query->where('duration', '>', $threshold);
    }

    public function scopeDuplicates($query)
    {
        return $query->where('is_duplicate', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('query_type', $type);
    }

    public function scopeByTable($query, $table)
    {
        return $query->where('table_name', $table);
    }
}