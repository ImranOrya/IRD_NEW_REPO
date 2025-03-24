<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Icons
        $dashboard = 'icons/home.svg';
        $users = 'icons/users-group.svg';
        $chart = 'icons/chart.svg';
        $settings = 'icons/settings.svg';
        $logs = 'icons/logs.svg';
        $audit = 'icons/audits.svg';
        $projects = 'icons/projects.svg';
        $ngo = 'icons/ngo.svg';
        $donor = 'icons/donor.svg';
        $management = 'icons/management.svg';
        $managementNews = 'icons/management-news.svg';
        $approval = 'icons/approval.svg';
        $activity = 'icons/activity.svg';

        Permission::factory()->create([
            "name" => "dashboard",
            "icon" => $dashboard,
            "priority" => 1,
        ]);
        Permission::factory()->create([
            "name" => "ngo",
            "icon" => $ngo,
            "priority" => 2,
        ]);
        Permission::factory()->create([
            "name" => "donor",
            "icon" => $donor,
            "priority" => 3,
        ]);
        Permission::factory()->create([
            "name" => "projects",
            "icon" => $projects,
            "priority" => 4,
        ]);
        Permission::factory()->create([
            "name" => "management/news",
            "icon" => $management,
            "priority" => 5,
        ]);
        Permission::factory()->create([
            "name" => "management/about",
            "icon" => $managementNews,
            "priority" => 5,
        ]);
        Permission::factory()->create([
            "name" => "users",
            "icon" => $users,
            "priority" => 6,
        ]);
        Permission::factory()->create([
            "name" => "reports",
            "icon" => $chart,
            "priority" => 7,
        ]);
        Permission::factory()->create([
            "name" => "logs",
            "icon" => $logs,
            "priority" => 8,
        ]);
        Permission::factory()->create([
            "name" => "audit",
            "icon" => $audit,
            "priority" => 9,
        ]);
        Permission::factory()->create([
            "name" => "settings",
            "icon" => $settings,
            "priority" => 10,
        ]);
        Permission::factory()->create([
            "name" => "approval",
            "icon" => $approval,
            "priority" => 11,
        ]);
        Permission::factory()->create([
            "name" => "activity",
            "icon" => $activity,
            "priority" => 11,
        ]);
    }
}
