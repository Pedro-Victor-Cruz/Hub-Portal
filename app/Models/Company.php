<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Company
 *
 * Representa uma empresa cliente no sistema.
 * Cada empresa pode ter vários usuários e uma configuração de ERP vinculada.
 *
 * @property int $id
 * @property string $name Nome da empresa
 * @property string|null $email Email de contato da empresa
 * @property string|null $cnpj CNPJ da empresa (apenas números)
 * @property int|null $responsible_user_id ID do usuário responsável principal pela empresa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User|null $responsibleUser Usuário responsável pela empresa
 * @property Collection|User[] $users Lista de usuários da empresa
 * @property Collection|CompanyErpSetting[] $erpSettings Configurações de ERP associadas à empresa
 */
class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'email',
        'responsible_user_id',
        'cnpj',
    ];

    /**
     * Usuário responsável principal pela empresa.
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Lista de usuários vinculados à empresa.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    /**
     * Configurações de ERP vinculadas a esta empresa.
     */
    public function erpSettings(): HasMany
    {
        return $this->hasMany(CompanyErpSetting::class, 'company_id');
    }
}
