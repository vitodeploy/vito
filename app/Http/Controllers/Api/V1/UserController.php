<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Traits\ApiQueryBuilder;
use Illuminate\Http\Request;
use \Illuminate\Http\JsonResponse;

use App\Actions\User\CreateUser;
use App\Actions\User\UpdateUser;
use App\Models\User;

class UserController extends Controller
{
    use ApiQueryBuilder;

    /**
     * Define relationships that are allowed to be loaded.
     *
     * @var array
     */
    protected $relations = [
        'servers',
        'sshKeys',
        'projects',
        'currentProject',
        'sourceControls',
        'serverProviders',
        'scripts',
        'storageProviders',
        'connectedStorageProviders',
    ];

    /**
     * Define allowed filters.
     *
     * @var array
     */
    protected $filters = [
        'name',
        'email',
        'timezone',
        'current_project_id',
        'role',
    ];

    /**
     * Allowed sort columns and their directions.
     *
     * @var array
     */
    protected $sorts = [
        'name',
        'email',
        'timezone',
        'current_project_id',
        'role',
        'created_at',
        'updated_at',
    ];

    /**
     * Sorting operators.
     *
     * @var array
     */
    protected $sort_operators = [
        'asc',
        'desc',
        'random',
    ];

    /**
     * Get all users.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(Request $request): JsonResponse
    {
        $users = User::query();

        try {
            // add relations
            $this->applyRelations($users);

            // apply filters
            $this->applyFilters($users);

            // apply sorting
            $this->applySorts($users);

            // apply date filters
            $this->applyDateFilters($users);

        } catch (\Exception $error) {
            return response()->json(['success' => false, 'errors' => [$error->getMessage()]], 422);
        }

        return response()->json(array_merge(
            ['status' => true],
            $users->paginate($request->get('paginate', 15))->toArray()
        ), 200);
    }
    
    /**
     * Create a new user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $user = app(CreateUser::class)->create($request->input());
        } catch (\Exception $error) {
            if($error instanceof ValidationException) {
                return response()->json(['success' => false, 'errors' => $error->validator->errors()->all()], 422);
            }

            return response()->json(['success' => false, 'errors' => [$error->getMessage()]], 500);
        }

        return response()->json(['status' => true, 'data' => $user], 201);
    }

    /**
     * Get a single user.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function get(Request $request, $id): JsonResponse
    {
        $user = User::query()->where('id', $id)->first();

        if (!$user) {
            return response()->json(['success' => false, 'errors' => ['User not found']], 404);
        }

        try {
            // add relations
            $this->applyRelations($user);

        } catch (\Exception $error) {
            return response()->json(['success' => false, 'errors' => [$error->getMessage()]], 422);
        }

        return response()->json(['status' => true, 'data' => $user], 200);
    }

    /**
     * Update a user.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = User::query()->where('id', $id)->first();

        if (!$user) {
            return response()->json(['success' => false, 'errors' => ['User not found']], 404);
        }

        try {
            app(UpdateUser::class)->update($user, $request->input());
        } catch (\Exception $error) {
            if($error instanceof ValidationException) {
                return response()->json(['success' => false, 'errors' => $error->validator->errors()->all()], 422);
            }

            return response()->json(['success' => false, 'errors' => [$error->getMessage()]], 500);
        }

        return response()->json(['status' => true, 'data' => $user], 200);
    }

    /**
     * Delete a user.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, $id): JsonResponse
    {
        $user = User::query()->where('id', $id)->first();

        if (!$user) {
            return response()->json(['success' => false, 'errors' => ['User not found']], 404);
        }

        try {
            $user->delete();
        } catch (\Exception $error) {
            return response()->json(['success' => false, 'errors' => [$error->getMessage()]], 500);
        }

        return response()->json(['status' => true], 200);
    }
}