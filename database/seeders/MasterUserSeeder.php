<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use App\Utils\PermissionStatus;
use Illuminate\Database\Seeder;
use PHPUnit\Metadata\Group;

class MasterUserSeeder extends Seeder
{

    public function run(): void
    {
        $masterUser = User::create([
            'email' => 'admin@mail.com',
            'name' => 'Administrador',
            'password' => 'admin110205',
        ]);

        $adminGroup = PermissionGroup::where('name', 'administrator')->first();
        $masterUser->assignPermissionGroup($adminGroup);

        $this->command->info('Master user seeded successfully!');
    }
}
