<?php

namespace App\Http\Controllers\api\auth;

use App\Models\Donor;
use App\Models\Email;
use App\Models\DonorStatus;
use Sway\Utils\StringUtils;
use Illuminate\Http\Request;
use App\Enums\Type\StatusTypeEnum;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;
use App\Traits\Helper\HelperTrait;

class DonorAuthController extends Controller
{
    use HelperTrait;
    public function authDonor(Request $request)
    {
        $ngo = $request->user();

        $authDonor =  DB::table('donors as d')
            ->where('d.id', $ngo->id)
            ->leftjoin('emails as e', function ($join) {
                $join->on('d.email_id', '=', 'e.id');
            })
            ->leftjoin('donor_statuses as ds', function ($join) {
                $join->on('ds.ngo_id', '=', 'n.id')
                    ->whereRaw('ds.created_at = (select max(ds2.created_at) from donor_statuses as ds2 where ds2.ngo_id = d.id)');
            })
            ->leftjoin('roles as r', function ($join) {
                $join->on('d.role_id', '=', 'r.id');
            })
            ->select(
                "d.id",
                "d.profile",
                "d.username",
                "d.role_id",
                "d.email_id",
                "e.value as email",
                "d.is_editable",
                "d.created_at",
                "ds.status_type_id",
                "r.name as role_name"
            )->first();

        $ngoPermissions = $this->permission($authDonor);

        return response()->json(
            array_merge([
                "user" => [
                    "id" => $authDonor->id,
                    "profile" => $authDonor->profile,
                    "username" => $authDonor->username,
                    "email" => ['id' => $authDonor->email_id, 'value' => $authDonor->email],
                    "is_editable" => $authDonor->is_editable,
                    "created_at" => $authDonor->created_at,
                    "role" => ["role" => $authDonor->role_id, "name" => $authDonor->role_name],
                    "status_type_id" => $authDonor->status_type_id
                ]
            ], [
                "permissions" => $ngoPermissions,
            ]),
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
            $ngoStatus = DonorStatus::where("donor_id", $ngo->id)->first();
            if ($ngoStatus->status_type_id == StatusTypeEnum::blocked->value) {
                return response()->json([
                    'message' => __('app_translation.account_is_block'),
                ], 403, [], JSON_UNESCAPED_UNICODE);
            }

            $authDonor =  DB::table('donors as d')
                ->where('d.id', $ngo->id)
                ->leftjoin('donor_statuses as ds', function ($join) {
                    $join->on('ds.donor_id', '=', 'd.id')
                        ->whereRaw('ds.created_at = (select max(ds2.created_at) from donor_statuses as ds2 where ds2.ngo_id = d.id)');
                })
                ->leftjoin('roles as r', function ($join) {
                    $join->on('d.role_id', '=', 'r.id');
                })
                ->select(
                    "d.id",
                    "d.profile",
                    "d.username",
                    "d.email_id",
                    "d.role_id",
                    "d.is_editable",
                    "d.created_at",
                    "ds.status_type_id",
                    "r.name as role_name"
                )->first();

            $ngoPermissions = $this->permission($authDonor);
            $this->storeUserLog($request, $authDonor->id, StringUtils::getModelName(Donor::class), "Login");

            return response()->json(
                array_merge([
                    "user" => [
                        "id" => $authDonor->id,
                        "profile" => $authDonor->profile,
                        "username" => $authDonor->username,
                        "email" => ['id' => $authDonor->email_id, 'value' => $credentials['email']],
                        "is_editable" => $authDonor->is_editable,
                        "created_at" => $authDonor->created_at,
                        "role" => ["role" => $authDonor->role_id, "name" => $authDonor->role_name],
                        "status_type_id" => $authDonor->status_type_id
                    ]
                ], [
                    "token" => $loggedIn['tokens']['access_token'],
                    "permissions" => $ngoPermissions,
                ]),
                200,
                [],
                JSON_UNESCAPED_UNICODE
            );
        } else {
            return response()->json([
                'message' => __('app_translation.donor_not_found')
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
    protected function permission($ngo)
    {
        $ngo_id = $ngo->id;
        $ngoPermissions = DB::table('ngos as n')
            ->where('n.id', $ngo_id)
            ->leftJoin('ngo_permissions as np', function ($join) {
                $join->on('np.ngo_id', '=', 'n.id');
            })
            ->join('permissions as p', function ($join) {
                $join->on('p.name', '=', 'np.permission');
            })
            ->select(
                "p.name as permission",
                "p.icon as icon",
                "p.priority as priority",
                "np.view",
                "np.add",
                "np.delete",
                "np.edit",
                "np.id",
            )
            ->orderBy("p.priority")
            ->get();

        return $ngoPermissions;
    }
}
