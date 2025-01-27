<?php

namespace App\Http\Controllers\api\auth;

use App\Enums\StatusTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Email;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NgoAuthController extends Controller
{
    public function ngo(Request $request)
    {
        $locale = App::getLocale();

        $user = $request->user()->load([
            'ngoTrans' => function ($user) use ($locale) {
                $user->where('language_name', $locale)->select('id', 'ngo_id', 'name as ngo_name');
            },
            'contact:id,value',
            'email:id,value',
            'ngoStatus:id,status_type_is'

        ]);
        $userPermissions = $this->userWithPermission($user);

        return response()->json(array_merge([
            "ngo" => [
                "id" => $user->id,
                "ngo_name" => $user->ngo_name,
                'email' => $user->email ? $user->email->value : "",
                "profile" => $user->profile,
                "status" => $user->ngoStatus ? $user->ngoStatus->status_type_is : "",
                'contact' => $user->contact ? $user->contact->value : "",
                "created_at" => $user->created_at,
            ]
        ], [
            "permissions" => $userPermissions["permissions"],
        ]), 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $locale = App::getLocale();

        $email = Email::where('value', '=', $credentials['email'])->first();
        if (!$email) {
            return response()->json([
                'message' => __('app_translation.email_not_found'),
            ], 403, [], JSON_UNESCAPED_UNICODE);
        }
        $loggedIn = Auth::guard('ngo:api')->attempt([
            "email_id" => $email->id,
            "password" => $request->password,
        ]);
        if ($loggedIn) {
            // Get the auth user
            $user = $loggedIn['user'];
            if ($user->ngoStatus->status_type_id == StatusTypeEnum::blocked->value) {
                return response()->json([
                    'message' => __('app_translation.account_is_block'),
                ], 403, [], JSON_UNESCAPED_UNICODE);
            }
            // Check If Ngo logged in for first time change to un_registered

            $user = $request->user()->load([
                'ngoTrans' => function ($user) use ($locale) {
                    $user->where('language_name', $locale)->select('id', 'ngo_id', 'name as ngo_name');
                },
                'contact:id,value',
                'email:id,value',
                'ngoStatus:id,status_type_is'

            ]);
            $userPermissions = $this->userWithPermission($user->id);

            return response()->json(
                array_merge([
                    "ngo" => [
                        "id" => $user->id,
                        "ngo_name" => $user->ngo_name,
                        'email' => $user->email ? $user->email->value : "",
                        "profile" => $user->profile,
                        "status" => $user->ngoStatus ? $user->ngoStatus->status_type_is : "",
                        'contact' => $user->contact ? $user->contact->value : "",
                        "created_at" => $user->created_at,
                    ]
                ], [
                    "token" => $loggedIn['tokens']['access_token'],
                    "permissions" => $userPermissions["permissions"],
                ]),
                200,
                [],
                JSON_UNESCAPED_UNICODE
            );
        } else {
            return response()->json([
                'message' => __('app_translation.user_not_found')
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->invalidateToken(); // Calls the invalidateToken method defined in the trait
        return response()->json([
            'message' => __('app_translation.user_logged_out_success')
        ], 204, [], JSON_UNESCAPED_UNICODE);
    }
    // HELPER
    protected function userWithPermission($user)
    {
        $userId = $user->id;
        $userPermissions = DB::table('ngos')
            ->join('permissions', function ($join) use ($userId) {
                $join->on('ngo_permissions.permission', '=', 'permissions.name')
                    ->where('ngo_permissions.user_id', '=', $userId);
            })
            ->select(
                "permissions.name as permission",
                "permissions.icon as icon",
                "permissions.priority as priority",
                "ngo_permissions.view",
                "ngo_permissions.add",
                "ngo_permissions.delete",
                "ngo_permissions.edit",
                "ngo_permissions.id",
            )
            ->orderBy("priority")
            ->get();
        return ["user" => $user->toArray(), "permissions" => $userPermissions];
    }
}
