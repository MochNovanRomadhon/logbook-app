<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // *** PENTING: Reset cache permission Spatie sebelum seeding ***
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Generate semua permission dari Filament Shield (WAJIB)
        //    Tanpa ini, menu tidak akan muncul meskipun role sudah dibuat
        \Artisan::call('shield:generate', [
            '--all'     => true,
            '--minimal' => true,
            '--panel'   => 'admin',
        ]);

        // 1. Create Roles (dengan guard_name eksplisit)
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $rolePengawas   = Role::firstOrCreate(['name' => 'pengawas',   'guard_name' => 'web']);
        $rolePegawai    = Role::firstOrCreate(['name' => 'pegawai',    'guard_name' => 'web']);

        // 2. Create Organization Hierarchy
        $directorate = \App\Models\Directorate::firstOrCreate([
            'name' => 'Direktorat Teknis',
        ], [
            'is_active' => true,
        ]);

        $unit = \App\Models\Unit::firstOrCreate([
            'name'            => 'Unit Aplikasi',
            'directorate_id'  => $directorate->id,
        ], [
            'is_active' => true,
        ]);

        $subunit = \App\Models\Subunit::firstOrCreate([
            'name'    => 'Subunit Backend',
            'unit_id' => $unit->id,
        ], [
            'is_active' => true,
        ]);

        // 3. Create Users

        // Super Admin — tidak perlu unit kerja
        $admin = User::firstOrCreate([
            'email' => 'admin@log.com',
        ], [
            'name'      => 'Super Admin',
            'password'  => bcrypt('password'),
            'is_active' => true,
        ]);
        $admin->syncRoles([$roleSuperAdmin]);

        // Pengawas (Supervisor)
        $pengawas = User::firstOrCreate([
            'email' => 'pengawas@log.com',
        ], [
            'name'           => 'Pengawas Unit',
            'password'       => bcrypt('password'),
            'is_active'      => true,
            'directorate_id' => $directorate->id,
            'unit_id'        => $unit->id,
        ]);
        $pengawas->syncRoles([$rolePengawas]);

        // Pegawai (Employee)
        $pegawai = User::firstOrCreate([
            'email' => 'pegawai@log.com',
        ], [
            'name'           => 'Pegawai Backend',
            'password'       => bcrypt('password'),
            'is_active'      => true,
            'directorate_id' => $directorate->id,
            'unit_id'        => $unit->id,
            'subunit_id'     => $subunit->id,
        ]);
        $pegawai->syncRoles([$rolePegawai]);
    }
}
