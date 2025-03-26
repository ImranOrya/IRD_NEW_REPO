<?php

namespace App\Http\Controllers\api\template;

use App\Models\User;
use App\Models\Email;
use App\Enums\RoleEnum;
use App\Models\Contact;
use App\Models\ModelJob;
use App\Models\Destination;
use Illuminate\Http\Request;
use App\Traits\Helper\HelperTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use function PHPUnit\Framework\isEmpty;
use App\Repositories\User\UserRepositoryInterface;
use App\Http\Requests\template\user\UpdateUserRequest;
use App\Http\Requests\template\user\UserRegisterRequest;

use App\Http\Requests\template\user\UpdateUserPasswordRequest;
use App\Repositories\Permission\PermissionRepositoryInterface;

class UserController extends Controller
{
    use HelperTrait;

    protected $userRepository;
    protected $permissionRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        PermissionRepositoryInterface $permissionRepository
    ) {
        $this->userRepository = $userRepository;
        $this->permissionRepository = $permissionRepository;
    }
    public function users(Request $request)
    {
        $locale = App::getLocale();
        $tr = [];
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page

        // Start building the query
        $query = DB::table('users as u')
            ->where('u.role_id', '!=', RoleEnum::debugger->value)
            ->leftJoin('contacts as c', 'c.id', '=', 'u.contact_id')
            ->join('emails as e', 'e.id', '=', 'u.email_id')
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->leftjoin('destination_trans as dt', function ($join) use ($locale) {
                $join->on('dt.destination_id', '=', 'u.destination_id')
                    ->where('dt.language_name', $locale);
            })
            ->leftjoin('model_job_trans as mjt', function ($join) use ($locale) {
                $join->on('mjt.model_job_id', '=', 'u.job_id')
                    ->where('mjt.language_name', $locale);
            })
            ->select(
                "u.id",
                "u.username",
                "u.profile",
                "u.status",
                "u.created_at",
                "e.value AS email",
                "c.value AS contact",
                "dt.value as destination",
                "mjt.value as job"
            );

        $this->applyDate($query, $request);
        $this->applyFilters($query, $request);
        $this->applySearch($query, $request);

        // Apply pagination (ensure you're paginating after sorting and filtering)
        $tr = $query->paginate($perPage, ['*'], 'page', $page);
        return response()->json(
            [
                "users" => $tr,
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
    public function user($id)
    {
        $locale = App::getLocale();

        $user = DB::table('users as u')
            ->where('u.id', $id)
            ->join('model_job_trans as mjt', function ($join) use ($locale) {
                $join->on('mjt.model_job_id', '=', 'u.job_id')
                    ->where('mjt.language_name', $locale);
            })
            ->leftJoin('contacts as c', 'c.id', '=', 'u.contact_id')
            ->join('emails as e', 'e.id', '=', 'u.email_id')
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->join('destination_trans as dt', function ($join) use ($locale) {
                $join->on('dt.destination_id', '=', 'u.destination_id')
                    ->where('dt.language_name', $locale);
            })->select(
                'u.id',
                "u.profile",
                "u.status",
                "u.grant_permission",
                'u.full_name',
                'u.username',
                'c.value as contact',
                'u.contact_id',
                'e.value as email',
                'r.name as role_name',
                'u.role_id',
                'dt.value as destination',
                "mjt.value as job",
                "u.created_at",
                "u.destination_id",
                "u.job_id"
            )
            ->first();
        if (!$user) {
            return response()->json([
                'message' => __('app_translation.user_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json(
            [
                "user" => [
                    "id" => $user->id,
                    "full_name" => $user->full_name,
                    "username" => $user->username,
                    'email' => $user->email,
                    "profile" => $user->profile,
                    "status" => $user->status == 1,
                    "grant" => $user->grant_permission == 1,
                    "role" => ['id' => $user->role_id, 'name' => $user->role_name],
                    'contact' => $user->contact,
                    "destination" => ["id" => $user->destination_id, "name" => $user->destination],
                    "job" => ["id" => $user->job_id, "name" => $user->job],
                    "created_at" => $user->created_at,
                ],
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
    public function validateEmailContact(Request $request)
    {
        $request->validate(
            [
                "email" => "required",
                "contact" => "required",
            ]
        );
        $email = Email::where("value", '=', $request->email)->first();
        $contact = Contact::where("value", '=', $request->contact)->first();
        // Check if both models are found
        $emailExists = $email !== null;
        $contactExists = $contact !== null;

        return response()->json([
            'email_found' => $emailExists,
            'contact_found' => $contactExists,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function store(UserRegisterRequest $request)
    {
        $request->validated();
        // 1. Check email
        $email = Email::where('value', '=', $request->email)->first();
        if ($email) {
            return response()->json([
                'message' => __('app_translation.email_exist'),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }
        // 2. Check contact
        $contact = null;
        if ($request->contact) {
            $contact = Contact::where('value', '=', $request->contact)->first();
            if ($contact) {
                return response()->json([
                    'message' => __('app_translation.contact_exist'),
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }
        }
        DB::beginTransaction();
        // Add email and contact
        $email = Email::create([
            "value" => $request->email
        ]);
        $contact = null;
        if ($request->contact) {
            $contact = Contact::create([
                "value" => $request->contact
            ]);
        }
        // 3. Create User
        $newUser = User::create([
            "full_name" => $request->full_name,
            "username" => $request->username,
            "email_id" => $email->id,
            "password" => Hash::make($request->password),
            "role_id" => $request->role,
            "job_id" => $request->job_id,
            "destination_id" => $request->destination_id,
            "contact_id" => $contact ? $contact->id : $contact,
            "profile" => null,
            "status" => $request->status,
            "grant_permission" => $request->grant,
        ]);

        // 4. Add user permissions
        $result = $this->permissionRepository->storeUserPermission($newUser, $request->permissions);
        if ($result == 400) {
            return response()->json([
                'message' => __('app_translation.user_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        } else if ($result == 401) {
            return response()->json([
                'message' => __('app_translation.unauthorized_role_per'),
            ], 403, [], JSON_UNESCAPED_UNICODE);
        } else if ($result == 402) {
            return response()->json([
                'message' => __('app_translation.per_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        DB::commit();
        return response()->json([
            'user' => [
                "id" => $newUser->id,
                "username" => $newUser->username,
                'email' => $request->email,
                "profile" => $newUser->profile,
                "status" => $newUser->status,
                "destination" => $request->destination,
                "job" => $request->job,
                "created_at" => $newUser->created_at,
            ],
            'message' => __('app_translation.success'),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function updateInformation(UpdateUserRequest $request)
    {
        $request->validated();
        // 1. User is passed from middleware
        DB::beginTransaction();
        $user = $request->get('validatedUser');
        if ($user) {
            $email = Email::where('value', $request->email)
                ->select('id')->first();
            // Email Is taken by someone
            if ($email) {
                if ($email->id == $user->email_id) {
                    $email->value = $request->email;
                    $email->save();
                } else {
                    return response()->json([
                        'message' => __('app_translation.email_exist'),
                    ], 409, [], JSON_UNESCAPED_UNICODE);
                }
            } else {
                $email = Email::where('id', $user->email_id)->first();
                $email->value = $request->email;
                $email->save();
            }
            if (isset($request->contact)) {
                $contact = Contact::where('value', $request->contact)
                    ->select('id')->first();
                if ($contact) {
                    if ($contact->id == $user->contact_id) {
                        $contact->value = $request->contact;
                        $contact->save();
                    } else {
                        return response()->json([
                            'message' => __('app_translation.contact_exist'),
                        ], 409, [], JSON_UNESCAPED_UNICODE);
                    }
                } else {
                    if (isset($user->contact_id)) {
                        $contact = Contact::where('id', $user->contact_id)->first();
                        $contact->value = $request->contact;
                        $contact->save();
                    } else {
                        $contact = Contact::create(['value' => $request->contact]);
                        $user->contact_id = $contact->id;
                    }
                }
            }

            // 4. Update User other attributes
            $user->full_name = $request->full_name;
            $user->username = $request->username;
            $user->role_id = $request->role;
            $user->job_id = $request->job;
            $user->destination_id = $request->destination;
            $user->status = $request->status === "true" ? true : false;
            $user->grant_permission = $request->grant === "true" ? true : false;
            $user->save();

            DB::commit();
            return response()->json([
                'message' => __('app_translation.success'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
        return response()->json([
            'message' => __('app_translation.user_not_found'),
        ], 404, [], JSON_UNESCAPED_UNICODE);
    }
    public function destroy($id)
    {
        DB::beginTransaction();
        $user = User::find($id);
        if ($user->role_id == RoleEnum::super->value) {
            return response()->json([
                'message' => __('app_translation.unauthorized'),
            ], 403, [], JSON_UNESCAPED_UNICODE);
        }
        if ($user) {
            // 1. Delete user email
            Email::where('id', '=', $user->email_id)->delete();
            // 2. Delete user contact
            Contact::where('id', '=', $user->contact_id)->delete();
            $user->delete();
            DB::commit();
            return response()->json([
                'message' => __('app_translation.success'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            return response()->json([
                'message' => __('app_translation.failed'),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }
    }
    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile' => 'nullable|mimes:jpeg,png,jpg|max:2048',
            'id' => 'required',
        ]);
        $user = User::find($request->id);
        if (!$user) {
            return response()->json([
                'message' => __('app_translation.user_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        $path = $this->storeProfile($request);
        if ($path != null) {
            // 1. delete old profile
            $this->deleteDocument($this->getProfilePath($user->profile));
            // 2. Update the profile
            $user->profile = $path;
        }
        $user->save();
        return response()->json([
            'message' => __('app_translation.profile_changed'),
            "profile" => $user->profile
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function deleteProfilePicture($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => __('app_translation.user_not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
        }
        // 1. delete old profile
        $this->deleteDocument($this->getProfilePath($user->profile));
        // 2. Update the profile
        $user->profile = null;
        $user->save();
        return response()->json([
            'message' => __('app_translation.success')
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function changePassword(UpdateUserPasswordRequest $request)
    {
        $request->validated();
        $user = $request->get('validatedUser');
        $authUser = $request->user();
        DB::beginTransaction();
        if ($authUser->role_id == RoleEnum::super->value) {
            $user->password = Hash::make($request->new_password);
            $user->save();
        } else {
            $request->validate([
                "old_password" => ["required", "min:8", "max:45"],
            ]);
            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'message' => __('app_translation.incorrect_password'),
                ], 422, [], JSON_UNESCAPED_UNICODE);
            } else {
                $user->password = Hash::make($request->new_password);
                $user->save();
            }
        }
        DB::commit();
        return response()->json([
            'message' => __('app_translation.success'),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function userCount()
    {
        $statistics = DB::select("
            SELECT
                COUNT(*) AS userCount,
                (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) AS todayCount,
                (SELECT COUNT(*) FROM users WHERE status = 1) AS activeUserCount,
                (SELECT COUNT(*) FROM users WHERE status = 0) AS inActiveUserCount
            FROM users
        ");
        return response()->json([
            'counts' => [
                "userCount" => $statistics[0]->userCount,
                "todayCount" => $statistics[0]->todayCount,
                "activeUserCount" => $statistics[0]->activeUserCount,
                "inActiveUserCount" =>  $statistics[0]->inActiveUserCount
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    protected function applyDate($query, $request)
    {
        // Apply date filtering conditionally if provided
        $startDate = $request->input('filters.date.startDate');
        $endDate = $request->input('filters.date.endDate');

        if ($startDate) {
            $query->where('n.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('n.created_at', '<=', $endDate);
        }
    }
    // search function 
    protected function applySearch($query, $request)
    {
        $searchColumn = $request->input('filters.search.column');
        $searchValue = $request->input('filters.search.value');

        $allowedColumns = ['username', 'contact', 'email'];

        if ($searchColumn && $searchValue) {
            $allowedColumns = [
                'username' => 'u.username',
                'contact' => 'c.value',
                'email' => 'e.value'
            ];
            // Ensure that the search column is allowed
            if (in_array($searchColumn, array_keys($allowedColumns))) {
                $query->where($allowedColumns[$searchColumn], 'like', '%' . $searchValue . '%');
            }
        }
    }
    // filter function
    protected function applyFilters($query, $request)
    {

        $sort = $request->input('filters.sort'); // Sorting column
        $order = $request->input('filters.order', 'asc'); // Sorting order (default 
        $allowedColumns = [
            'username' => 'u.username',
            'created_at' => 'u.created_at',
            'status' => 'u.status',
            'job' => 'mjt.value',
            'destination' => 'dt.value'
        ];
        if (in_array($sort, array_keys($allowedColumns))) {
            $query->orderBy($allowedColumns[$sort], $order);
        }
    }
}
