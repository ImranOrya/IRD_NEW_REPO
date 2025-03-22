<?php

namespace App\Http\Controllers\api\template;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Permission\PermissionRepositoryInterface;

class PermissionController extends Controller
{
    protected $permissionRepository;

    public function __construct(PermissionRepositoryInterface $permissionRepository)
    {
        $this->permissionRepository = $permissionRepository;
    }
    public function rolePermissions($id)
    {
        $rolePermissions = $this->permissionRepository->rolePermissions($id);
        $formattedRolePermissions = $this->permissionRepository->formatRolePermissions($rolePermissions);
        return response()->json(
            $formattedRolePermissions,
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
    /*
    USER
    */
    public function userPermissions($id)
    {
        $user = User::where('id', $id)->first();
        if (!$user) {
            return response()->json([
                'message' => __('app_translation.user_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        return response()->json(
            $this->permissionRepository->assigningPermissions($user->id, $user->role_id),
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
    public function editUserPermissions(Request $request)
    {
        $request->validate([
            'user_id' => "required",
            'permissions' => "required"
        ]);
        $permissions = $request->permissions;
        $user = User::where('id', $request->user_id)->first();
        if (!$user) {
            return response()->json([
                'message' => __('app_translation.user_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        $result = $this->permissionRepository->storeUserPermission($user, $permissions);
        if ($result == 401) {
            return response()->json([
                'message' => __('app_translation.unauthorized_role_per'),
            ], 403, [], JSON_UNESCAPED_UNICODE);
        } else if ($result == 402) {
            return response()->json([
                'message' => __('app_translation.per_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        } else {
            return response()->json([
                'message' => __('app_translation.success'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
    }
    /*
    NGO
    */
    /*
    DONOR
    */
}
