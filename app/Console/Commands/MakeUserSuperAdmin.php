<?php

namespace App\Console\Commands;

use App\Models\PermissionGroup;
use App\Models\User;
use Illuminate\Console\Command;

class MakeUserSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-super-admin {user_id : ID do usuário}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atribui o grupo Super Admin a um usuário';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->argument('user_id');

        // Verifica se o usuário existe
        $user = User::find($userId);

        if (!$user) {
            $this->error('Usuário não encontrado com o ID fornecido!');
            return 1;
        }

        // Verifica se o grupo Super Admin existe
        $superAdminGroup = PermissionGroup::where('name', 'Super Admin')->first();

        if (!$superAdminGroup) {
            $this->error('Grupo Super Admin não encontrado! Execute os seeders primeiro.');
            return 1;
        }

        // Atribui o grupo ao usuário (sem duplicar)
        $user->permissionGroups()->syncWithoutDetaching([$superAdminGroup->id]);

        $this->info("Usuário {$user->name} (ID: {$user->id}) foi promovido a Super Admin com sucesso!");
        return 0;
    }

}