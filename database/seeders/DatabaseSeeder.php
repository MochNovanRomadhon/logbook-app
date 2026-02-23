<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Roles
        $roleSuperAdmin = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
        $rolePengawas = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'pengawas']);
        $rolePegawai = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'pegawai']);

        // 2. Create Organization Hierarchy
        $directorate = \App\Models\Directorate::firstOrCreate([
            'name' => 'Direktorat Teknis',
            'is_active' => true,
        ]);

        $unit = \App\Models\Unit::firstOrCreate([
            'name' => 'Unit Aplikasi',
            'directorate_id' => $directorate->id,
            'is_active' => true,
        ]);

        $subunit = \App\Models\Subunit::firstOrCreate([
            'name' => 'Subunit Backend',
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        // 3. Create Users
        
        // Super Admin
        $admin = User::firstOrCreate([
            'email' => 'admin@log.com',
        ], [
            'name' => 'Super Admin',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $admin->assignRole($roleSuperAdmin);

        // Pengawas (Supervisor)
        $pengawas = User::firstOrCreate([
            'email' => 'pengawas@log.com',
        ], [
            'name' => 'Pengawas Unit',
            'password' => bcrypt('password'),
            'is_active' => true,
            'directorate_id' => $directorate->id,
            'unit_id' => $unit->id,
        ]);
        $pengawas->assignRole($rolePengawas);

        // Pegawai (Employee)
        $pegawai = User::firstOrCreate([
            'email' => 'pegawai@log.com',
        ], [
            'name' => 'Pegawai Backend',
            'password' => bcrypt('password'),
            'is_active' => true,
            'directorate_id' => $directorate->id,
            'unit_id' => $unit->id,
            'subunit_id' => $subunit->id,
        ]);
        $pegawai->assignRole($rolePegawai);
    }
}
