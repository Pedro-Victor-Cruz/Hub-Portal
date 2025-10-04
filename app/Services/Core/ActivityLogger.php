<?php

namespace App\Services\Core;

use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActivityLogger
{
    protected ?string $batchId = null;
    protected array $metadata = [];
    protected ?string $traceId = null;

    /**
     * Inicia um novo batch de logs
     */
    public function startBatch(): string
    {
        $this->batchId = (string) Str::uuid();
        return $this->batchId;
    }

    /**
     * Define o trace ID para distributed tracing
     */
    public function setTraceId(string $traceId): self
    {
        $this->traceId = $traceId;
        return $this;
    }

    /**
     * Adiciona metadata ao log
     */
    public function withMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    /**
     * Loga uma ação genérica
     */
    public function log(
        string $action,
        ?string $description = null,
        string $level = SystemLog::LEVEL_INFO,
        ?string $module = null,
        ?Model $model = null,
        array $data = []
    ): ?SystemLog {
        try {
            $logData = $this->prepareLogData($action, $description, $level, $module, $model, $data);
            return SystemLog::create($logData);
        } catch (\Exception $e) {
            Log::error('Erro ao criar log: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Loga uma criação de registro
     */
    public function logCreated(Model $model, ?string $description = null, ?string $module = null): ?SystemLog
    {
        return $this->log(
            action: SystemLog::ACTION_CREATED,
            description: $description ?? "Registro criado",
            level: SystemLog::LEVEL_INFO,
            module: $module ?? $this->getModuleName($model),
            model: $model,
            data: ['new_values' => $this->getRelevantAttributes($model)]
        );
    }

    /**
     * Loga uma atualização de registro
     */
    public function logUpdated(Model $model, array $oldValues = [], ?string $description = null, ?string $module = null): ?SystemLog
    {
        $changes = $this->getChanges($model, $oldValues);

        if (empty($changes)) {
            return null; // Não loga se não houver mudanças
        }

        return $this->log(
            action: SystemLog::ACTION_UPDATED,
            description: $description ?? "Registro atualizado",
            level: SystemLog::LEVEL_INFO,
            module: $module ?? $this->getModuleName($model),
            model: $model,
            data: [
                'old_values' => $oldValues,
                'new_values' => $this->getRelevantAttributes($model),
                'changes' => $changes
            ]
        );
    }

    /**
     * Loga uma exclusão de registro
     */
    public function logDeleted(Model $model, ?string $description = null, ?string $module = null): ?SystemLog
    {
        return $this->log(
            action: SystemLog::ACTION_DELETED,
            description: $description ?? "Registro deletado",
            level: SystemLog::LEVEL_WARNING,
            module: $module ?? $this->getModuleName($model),
            model: $model,
            data: ['old_values' => $this->getRelevantAttributes($model)]
        );
    }

    /**
     * Loga um acesso/visualização
     */
    public function logViewed(Model $model, ?string $description = null, ?string $module = null): ?SystemLog
    {
        return $this->log(
            action: SystemLog::ACTION_VIEWED,
            description: $description ?? "Registro visualizado",
            level: SystemLog::LEVEL_DEBUG,
            module: $module ?? $this->getModuleName($model),
            model: $model
        );
    }

    /**
     * Loga um login
     */
    public function logLogin(?Model $user = null, ?string $description = null): ?SystemLog
    {
        return $this->log(
            action: SystemLog::ACTION_LOGIN,
            description: $description ?? "Usuário autenticado com sucesso",
            level: SystemLog::LEVEL_INFO,
            module: 'auth',
            model: $user,
            data: ['metadata' => ['login_time' => now()->toDateTimeString()]]
        );
    }

    /**
     * Loga uma tentativa de login falhada
     */
    public function logLoginFailed(string $email, ?string $reason = null): ?SystemLog
    {
        return $this->log(
            action: SystemLog::ACTION_LOGIN_FAILED,
            description: "Tentativa de login falhou para: {$email}" . ($reason ? " - {$reason}" : ""),
            level: SystemLog::LEVEL_WARNING,
            module: 'auth',
            data: ['metadata' => ['email' => $email, 'reason' => $reason]]
        );
    }

    /**
     * Loga um logout
     */
    public function logLogout(?Model $user = null, ?string $description = null): ?SystemLog
    {
        return $this->log(
            action: SystemLog::ACTION_LOGOUT,
            description: $description ?? "Usuário deslogado",
            level: SystemLog::LEVEL_INFO,
            module: 'auth',
            model: $user
        );
    }

    /**
     * Loga um erro
     */
    public function logError(string $description, ?string $module = null, array $context = []): ?SystemLog
    {
        return $this->log(
            action: 'error',
            description: $description,
            level: SystemLog::LEVEL_ERROR,
            module: $module,
            data: ['metadata' => $context]
        );
    }

    /**
     * Prepara os dados do log
     */
    protected function prepareLogData(
        string $action,
        ?string $description,
        string $level,
        ?string $module,
        ?Model $model,
        array $data
    ): array {
        $user = Auth::guard('auth')->user();
        $request = request();

        return array_merge([
            'level' => $level,
            'action' => $action,
            'module' => $module,
            'loggable_type' => $model ? get_class($model) : null,
            'loggable_id' => $model?->id,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'batch_id' => $this->batchId,
            'session_id' => session()->getId(),
            'trace_id' => $this->traceId ?? Str::uuid(),
            'metadata' => array_merge($this->metadata, $data['metadata'] ?? []),
        ], $data);
    }

    /**
     * Obtém o nome do módulo baseado no model
     */
    protected function getModuleName(Model $model): string
    {
        $class = class_basename($model);
        return strtolower($class);
    }

    /**
     * Obtém apenas os atributos relevantes do model (remove timestamps, etc)
     */
    protected function getRelevantAttributes(Model $model): array
    {
        $hidden = ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'];
        return collect($model->getAttributes())
            ->except($hidden)
            ->toArray();
    }

    /**
     * Calcula as mudanças entre valores antigos e novos
     */
    protected function getChanges(Model $model, array $oldValues): array
    {
        $newValues = $this->getRelevantAttributes($model);
        $changes = [];

        foreach ($newValues as $key => $newValue) {
            $oldValue = $oldValues[$key] ?? null;
            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        return $changes;
    }

    /**
     * Limpa os dados temporários
     */
    public function reset(): void
    {
        $this->batchId = null;
        $this->metadata = [];
        $this->traceId = null;
    }
}