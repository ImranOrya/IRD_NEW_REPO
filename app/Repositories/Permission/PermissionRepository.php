<?php

namespace App\Repositories\Permission;

use App\Models\User;
use App\Models\UserPermission;
use App\Models\UserPermissionSub;
use Illuminate\Support\Facades\DB;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function assigningPermissions($user_id, $role_id)
    {
        $rolePermissions = $this->rolePermissions($role_id);
        $formattedRolePermissions = $this->formatRolePermissions($rolePermissions);
        $permissions = $this->userPermissions($user_id);
        $formattedPermissions = $this->formatUserPermissions($permissions);

        // Merger permissions
        $formattedRolePermissions->each(function ($permission) use (&$formattedPermissions) {
            $perm = $formattedPermissions->where("permission", $permission->permission)->first();
            // 1. If permission not found set
            if (!$perm) {
                $formattedPermissions->push($permission);
            } else {
                // 2. If permission found check for any missing Sub Permissions
                $permSub = $perm->sub;
                foreach ($permission->sub as $subPermission) {
                    $subExists = false;
                    for ($i = 0; $i < count($permSub); $i++) {
                        $sub = $permSub[$i];
                        if ($sub['id'] == $subPermission['id']) {
                            $subExists = true;
                            break;
                        }
                    }
                    if (!$subExists) {
                        $perm->sub[] = $subPermission;
                    }
                }
            }
        });
        return $formattedPermissions;
    }
    public function formatUserPermissions($permissions)
    {
        return  $permissions->groupBy('user_permission_id')->map(function ($group) {
            $subPermissions = $group->filter(function ($item) {
                return $item->sub_permission_id !== null; // Filter for permissions that have sub-permissions
            });

            $permission = $group->first(); // Get the first permission for this group

            $permission->view = (bool) $permission->view;
            $permission->edit = (bool) $permission->edit;
            $permission->delete = (bool) $permission->delete;
            $permission->add = (bool) $permission->add;
            if ($subPermissions->isNotEmpty()) {
                $permission->sub = $subPermissions->map(function ($sub) {
                    return [
                        'id' => $sub->sub_permission_id,
                        'name' =>  $sub->name,
                        'add' => (bool) $sub->sub_add,
                        'delete' => (bool) $sub->sub_delete,
                        'edit' => (bool) $sub->sub_edit,
                        'view' => (bool) $sub->sub_view,
                    ];
                });
            } else {
                $permission->sub = [];
            }
            // If there are no sub-permissions, remove the unwanted fields
            unset($permission->sub_permission_id);
            unset($permission->sub_add);
            unset($permission->sub_delete);
            unset($permission->sub_edit);
            unset($permission->sub_view);
            unset($permission->name);
            unset($permission->user_permission_id);

            return $permission;
        })->values();
    }
    public function rolePermissions($role_id)
    {
        return DB::table('role_permissions as rp')
            ->where('rp.role', '=', $role_id)
            ->join('permissions as p', 'rp.permission', '=', 'p.name')
            ->leftJoin('role_permission_subs as rps', 'rps.role_permission_id', '=', 'rp.id')
            ->leftJoin('sub_permissions as sp', 'rps.sub_permission_id', '=', 'sp.id')
            ->select(
                'p.name as permission',
                'sp.name',
                'p.priority',
                "rps.sub_permission_id",
                'sp.name'
            )
            ->get();
    }
    public function formatRolePermissions($rolePermissions)
    {
        return $rolePermissions->groupBy('permission')->map(function ($group) {
            $subPermissions = $group->filter(function ($item) {
                return $item->sub_permission_id !== null; // Filter for permissions that have sub-permissions
            });

            $permission = $group->first(); // Get the first permission for this group

            $permission->view = false;
            $permission->add = false;
            $permission->delete = false;
            $permission->edit = false;
            if ($subPermissions->isNotEmpty()) {

                $permission->sub = $subPermissions->map(function ($sub) {
                    return [
                        'id' => $sub->sub_permission_id,
                        'name' => $sub->name,
                        'add' => false,
                        'delete' => false,
                        'edit' => false,
                        'view' => false
                    ];
                });
            } else {
                $permission->sub = [];
            }
            // // If there are no sub-permissions, remove the unwanted fields
            unset($permission->sub_permission_id);
            unset($permission->name);
            // unset($permission->sub_delete);
            // unset($permission->sub_edit);

            return $permission;
        })->values();
    }
    /*
    USER
    */
    public function storeUserPermission($user, $permissions)
    {
        $rolePermissions = $this->rolePermissions($user->role_id);
        $formattedRolePermissions = $this->formatRolePermissions($rolePermissions);

        foreach ($permissions as $permission) {
            $rolePerm = $formattedRolePermissions->where("permission", $permission['permission'])->first();
            // 1. If permission not found set
            if (!$rolePerm) {
                return 401;
            } else {
                // 2. Permission exist in role
                $userPermission = null;
                if (isset($permission['id'])) {
                    // UserPermission Found
                    $userPermission = UserPermission::where('id', $permission['id'])
                        ->select('id', 'edit', 'delete', 'add', 'view')->first();
                    if (!$userPermission) {
                        return 402;
                    }
                    $userPermission->edit = $permission['edit'];
                    $userPermission->delete = $permission['delete'];
                    $userPermission->add = $permission['add'];
                    $userPermission->view = $permission['view'];
                    $userPermission->save();
                } else {
                    // UserPermission Not Found
                    $userPermission = UserPermission::create([
                        "edit" => $permission['edit'],
                        "delete" => $permission['delete'],
                        "add" => $permission['add'],
                        "view" => $permission['view'],
                        "visible" => true,
                        "user_id" => $user->id,
                        "permission" => $permission['permission'],
                    ]);
                }
                // 3. Check for any missing Sub Permissions
                $rolePermSub = $rolePerm->sub;
                foreach ($permission['sub'] as $subPermission) {
                    $subFound = false;
                    $roleSub = null;
                    for ($i = 0; $i < count($rolePermSub); $i++) {
                        $roleSub = $rolePermSub[$i];
                        if ($subPermission['id'] == $roleSub['id']) {
                            $subFound = true;
                            break;
                        }
                    }
                    if ($subFound) {
                        // SubPermission Found
                        $userPermissionSub = UserPermissionSub::where("sub_permission_id", $subPermission['id'])
                            ->where("user_permission_id", $userPermission->id)
                            ->select('id', 'edit', 'delete', 'add', 'view')->first();
                        if ($userPermissionSub) {
                            $userPermissionSub->edit = $subPermission['edit'];
                            $userPermissionSub->delete = $subPermission['delete'];
                            $userPermissionSub->add = $subPermission['add'];
                            $userPermissionSub->view = $subPermission['view'];
                            $userPermissionSub->save();
                        } else {
                            UserPermissionSub::create([
                                "edit" => $subPermission['edit'],
                                "delete" => $subPermission['delete'],
                                "add" => $subPermission['add'],
                                "view" => $subPermission['view'],
                                "user_permission_id" => $userPermission['id'],
                                "sub_permission_id" => $roleSub['id'],
                            ]);
                        }
                    }
                }
            }
        }
        return 200;
    }
    public function userPermissions($user_id)
    {
        return DB::table('users as u')
            ->where('u.id', $user_id)
            ->join('user_permissions as up', 'u.id', '=', 'up.user_id')
            ->join('permissions as p', 'up.permission', '=', 'p.name')
            ->leftJoin('user_permission_subs as ups', 'up.id', '=', 'ups.user_permission_id')
            ->leftJoin('sub_permissions as sp', 'ups.sub_permission_id', '=', 'sp.id')
            ->select(
                'up.id as user_permission_id',
                'p.name as permission',
                'sp.name',
                'p.priority',
                'up.id',
                'up.view',
                'up.edit',
                'up.delete',
                'up.add',
                'ups.sub_permission_id as sub_permission_id',
                'ups.add as sub_add',
                'ups.delete as sub_delete',
                'ups.edit as sub_edit',
                'ups.view as sub_view',
            )
            ->orderBy('p.priority')  // Optional: If you want to order by priority, else remove
            ->get();
    }
    /*
    NGO
    */
    /*
    DONOR
    */
}
