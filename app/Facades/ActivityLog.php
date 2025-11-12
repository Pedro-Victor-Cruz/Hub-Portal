<?php

namespace App\Facades;

use App\Models\SystemLog;
use App\Services\Core\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static SystemLog|null log(string $action, string $description = null, string $level = 'info', string $module = null, Model $model = null, array $data = [])
 * @method static SystemLog|null logCreated(Model $model, string $description = null, string $module = null)
 * @method static SystemLog|null logUpdated(Model $model, array $oldValues = [], string $description = null, string $module = null)
 * @method static SystemLog|null logDeleted(Model $model, string $description = null, string $module = null)
 * @method static SystemLog|null logViewed(Model $model, string $description = null, string $module = null)
 * @method static SystemLog|null logLogin(Model $user = null, string $description = null)
 * @method static SystemLog|null logLoginFailed(string $email, string $reason = null)
 * @method static SystemLog|null logLogout(Model $user = null, string $description = null)
 * @method static SystemLog|null logError(string $description, string $module = null, array $context = [])
 * @method static string startBatch()
 * @method static ActivityLogger setTraceId(string $traceId)
 * @method static ActivityLogger withMetadata(array $metadata)
 * @method static void reset()
 *
 * @see ActivityLogger
 */
class ActivityLog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActivityLogger::class;
    }
}