<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Models\SubPermission;
use Illuminate\Database\Seeder;
use App\Enums\SubPermissionEnum;

class SubPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->subUserPermissions();
        $this->subNgoPermissions();
        $this->subSettingPermissions();
        $this->subAboutPermissions();
        $this->subNewsPermissions();
    }
    public function subUserPermissions()
    {
        foreach (SubPermissionEnum::USERS as $id => $role) {
            SubPermission::factory()->create([
                "id" => $id,
                "permission" => PermissionEnum::users->value,
                "name" => $role,
            ]);
        }
    }
    public function subNgoPermissions()
    {
        foreach (SubPermissionEnum::NGO as $id => $role) {
            SubPermission::factory()->create([
                "id" => $id,
                "permission" => PermissionEnum::ngo->value,
                "name" => $role,
            ]);
        }
    }
    public function subSettingPermissions()
    {
        foreach (SubPermissionEnum::SETTINGS as $id => $role) {
            SubPermission::factory()->create([
                "id" => $id,
                "permission" => PermissionEnum::settings->value,
                "name" => $role,
            ]);
        }
    }
    public function subAboutPermissions()
    {
        foreach (SubPermissionEnum::ABOUT as $id => $role) {
            SubPermission::factory()->create([
                "id" => $id,
                "permission" => PermissionEnum::about->value,
                "name" => $role,
            ]);
        }
    }
    public function subNewsPermissions()
    {
        foreach (SubPermissionEnum::NEWS as $id => $role) {
            SubPermission::factory()->create([
                "id" => $id,
                "permission" => PermissionEnum::news->value,
                "name" => $role,
            ]);
        }
    }
}
