<?php

namespace App\Traits;

use App\Facades\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait Loggable
{
    /**
     * Boot do trait
     */
    protected static function bootLoggable(): void
    {
        // Log automático ao criar
        static::created(function (Model $model) {
            if ($model->shouldLogCreation()) {
                ActivityLog::logCreated($model);
            }
        });

        // Log automático ao atualizar
        static::updated(function (Model $model) {
            if ($model->shouldLogUpdate()) {
                $oldValues = $model->getOriginal();
                ActivityLog::logUpdated($model, $oldValues);
            }
        });

        // Log automático ao deletar
        static::deleted(function (Model $model) {
            if ($model->shouldLogDeletion()) {
                ActivityLog::logDeleted($model);
            }
        });
    }

    /**
     * Define se deve logar a criação
     */
    public function shouldLogCreation(): bool
    {
        return property_exists($this, 'logCreation') ? $this->logCreation : true;
    }

    /**
     * Define se deve logar a atualização
     */
    public function shouldLogUpdate(): bool
    {
        return property_exists($this, 'logUpdate') ? $this->logUpdate : true;
    }

    /**
     * Define se deve logar a exclusão
     */
    public function shouldLogDeletion(): bool
    {
        return property_exists($this, 'logDeletion') ? $this->logDeletion : true;
    }

    /**
     * Retorna todos os logs deste modelo
     */
    public function systemLogs()
    {
        return $this->morphMany(\App\Models\SystemLog::class, 'loggable');
    }

    /**
     * Retorna o último log deste modelo
     */
    public function lastLog()
    {
        return $this->systemLogs()->latest()->first();
    }
}