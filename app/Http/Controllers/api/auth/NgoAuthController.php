<?php

namespace App\Http\Controllers\api\auth;

use Exception;
use App\Models\Ngo;
use App\Models\Email;
use App\Enums\RoleEnum;
use App\Models\Address;
use App\Models\Contact;
use App\Models\NgoTran;
use App\Models\NgoStatus;
use App\Enums\LanguageEnum;
use App\Models\AddressTran;
use Illuminate\Http\Request;
use App\Enums\Type\StatusTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\LoginRequest;
use App\Repositories\User\UserRepositoryInterface;
use App\Http\Requests\app\ngo\NgoInfoUpdateRequest;
use App\Http\Requests\Auth\ngo\NgoProfileUpdateRequest;
use App\Http\Requests\template\user\UpdateUserPasswordRequest;
use App\Http\Requests\template\user\UpdateProfilePasswordRequest;

class NgoAuthController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function authNgo(Request $request)
    {
        $ngo = $request->user();

        $authNgo =  DB::table('ngos as n')
            ->where('n.id', $ngo->id)
            ->join('emails as e', function ($join) {
                $join->on('n.email_id', '=', 'e.id');
            })
            ->join('contacts as c', function ($join) {
                $join->on('n.contact_id', '=', 'c.id');
            })
            ->join('ngo_statuses as ns', function ($join) {
                $join->on('ns.ngo_id', '=', 'n.id')
                    ->where('ns.is_active', true);
            })
            ->join('roles as r', function ($join) {
                $join->on('n.role_id', '=', 'r.id');
            })
            ->select(
                "n.id",
                "n.profile",
                "n.username",
                "n.role_id",
                "n.email_id",
                "e.value as email",
                "n.contact_id",
                "c.value as contact",
                "n.is_editable",
                "n.created_at",
                "ns.status_type_id",
                "r.name as role_name"
            )->first();


        return response()->json(
            [
                "permissions" => $this->userRepository->ngoAuthFormattedPermissions($ngo->id),
                "user" => [
                    "id" => $authNgo->id,
                    "profile" => $authNgo->profile,
                    "username" => $authNgo->username,
                    "email" => ['id' => $authNgo->email_id, 'value' => $authNgo->email],
                    "contact" => ['id' => $authNgo->contact_id, 'value' => $authNgo->contact],
                    "is_editable" => $authNgo->is_editable,
                    "created_at" => $authNgo->created_at,
                    "role" => ["role" => $authNgo->role_id, "name" => $authNgo->role_name],
                    "status_type_id" => $authNgo->status_type_id
                ],
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

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
            $ngo = $loggedIn['user'];
            $ngoStatus = NgoStatus::where("ngo_id", $ngo->id)->first();
            if ($ngoStatus->status_type_id == StatusTypeEnum::blocked->value) {
                return response()->json([
                    'message' => __('app_translation.account_is_block'),
                ], 403, [], JSON_UNESCAPED_UNICODE);
            }
            // Check If Ngo logged in for first time change to un_registered
            else if (!$ngo->is_logged_in) {
                $ngo->is_logged_in = true;
                $ngo->save();
            }

            $authNgo =  DB::table('ngos as n')
                ->where('n.id', $ngo->id)
                ->join('contacts as c', function ($join) {
                    $join->on('n.contact_id', '=', 'c.id');
                })
                ->join('ngo_statuses as ns', function ($join) {
                    $join->on('ns.ngo_id', '=', 'n.id')
                        ->where('ns.is_active', true);
                })
                ->join('roles as r', function ($join) {
                    $join->on('n.role_id', '=', 'r.id');
                })
                ->select(
                    "n.id",
                    "n.profile",
                    "n.username",
                    "n.email_id",
                    "n.contact_id",
                    "c.value as contact",
                    "n.role_id",
                    "n.is_editable",
                    "n.created_at",
                    "ns.status_type_id",
                    "r.name as role_name"
                )->first();


            return response()->json(
                [
                    "token" => $loggedIn['tokens']['access_token'],
                    "permissions" => $this->userRepository->ngoAuthFormattedPermissions($ngo->id),
                    "user" => [
                        "id" => $authNgo->id,
                        "profile" => $authNgo->profile,
                        "username" => $authNgo->username,
                        "email" => ['id' => $authNgo->email_id, 'value' => $credentials['email']],
                        "contact" => ['id' => $authNgo->contact_id, 'value' => $authNgo->contact],
                        "is_editable" => $authNgo->is_editable,
                        "created_at" => $authNgo->created_at,
                        "role" => ["role" => $authNgo->role_id, "name" => $authNgo->role_name],
                        "status_type_id" => $authNgo->status_type_id
                    ],
                ],
                200,
                [],
                JSON_UNESCAPED_UNICODE
            );
        } else {
            return response()->json([
                'message' => __('app_translation.ngo_not_found')
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
    public function changePassword(UpdateProfilePasswordRequest $request)
    {
        $request->validated();
        $authUser = $request->user();
        DB::beginTransaction();
        $request->validate([
            "old_password" => ["required", "min:8", "max:45"],
        ]);
        if (!Hash::check($request->old_password, $authUser->password)) {
            return response()->json([
                'errors' => ['old_password' => [__('app_translation.incorrect_password')]],
            ], 422, [], JSON_UNESCAPED_UNICODE);
        } else {
            $authUser->password = Hash::make($request->new_password);
            $authUser->save();
        }
        DB::commit();
        return response()->json([
            'message' => __('app_translation.success'),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
