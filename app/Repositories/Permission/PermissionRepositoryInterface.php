<?php

namespace App\Repositories\Permission;

interface PermissionRepositoryInterface
{

    /**
     * Retrieve NGO data when registeration is completed.
     * 
     *
     * @param string $user_id
     * @param string $role_id
     * @return @var \Illuminate\Support\Collection<int, \stdClass|null> $formattedPermissions
     */
    public function assigningPermissions($user_id, $role_id);
    /**
     * Retrieve User permissions.
     * 
     *
     * @param string $user_id
     * @return @var \Illuminate\Support\Collection<int, \stdClass|null> $userPermissions
     */
    public function userPermissions($user_id);
    /**
     * Retrieve Formatted User permissions.
     * 
     *
     * @param @var \Illuminate\Support\Collection<int, \stdClass|null> $permissions
     * @return @var mixed $formattedPermissions
     */
    public function formatUserPermissions($permissions);
    /**
     * Retrieve Role permissions.
     * 
     *
     * @param string $role_id
     * @return @var \Illuminate\Support\Collection<int, \stdClass|null> $rolePermissions
     */
    public function rolePermissions($role_id);
    /**
     * Retrieve Formatted Role permissions.
     * 
     *
     * @param @var \Illuminate\Support\Collection<int, \stdClass|null> $formattedRolePermissions
     * @return @var mixed $formattedRolePermissions
     */
    public function formatRolePermissions($rolePermissions);
    /**
     * Retrieve Formatted Role permissions.
     * 
     *
     * @param mix user
     * @param @var mixed $permissions
     * @return @var mixed $Response
     */
    public function storeUserPermission($user_id, $permissions);
}
