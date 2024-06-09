<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Traits\ApiQueryBuilder;
use Illuminate\Http\Request;
use App\Models\Server;

class ServerController extends Controller
{
    use ApiQueryBuilder;

    /**
     * Define relationships that are allowed to be loaded.
     *
     * @var array
     */
    protected $relations = [
        'project',
        'creator',
        'logs',
        'sites',
        'tasks',
        'services',
        'databases',
        'backups',
        'cronJobs',
        'queues',
        'daemons',
        'metrics',
        'snapshots',
        'sshKeys',
        'databaseUsers',
        'firewallRules',
        'serverProvider',  
    ];

    /**
     * Define allowed filters.
     *
     * @var array
     */
    protected $filters = [
        'project_id',
        'user_id',
        'name',
        'ssh_user',
        'ip',
        'local_ip',
        'port',
        'os',
        'type',
        'type_data',
        'provider',
        'provider_id',
        'provider_data',
        'authentication',
        'public_key',
        'status',
        'auto_update',
        'available_updates',
        'security_updates',
        'progress',
        'progress_step',
        'updates',
        'last_update_check',
    ];

    /**
     * Allowed sort columns and their directions.
     *
     * @var array
     */
    protected $sorts = [
        'project_id',
        'user_id',
        'name',
        'ssh_user',
        'ip',
        'local_ip',
        'port',
        'os',
        'type',
        'type_data',
        'provider',
        'provider_id',
        'provider_data',
        'authentication',
        'public_key',
        'status',
        'auto_update',
        'available_updates',
        'security_updates',
        'progress',
        'progress_step',
        'updates',
        'last_update_check',
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
     * Get all servers.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(Request $request)
    {
        $servers = Server::query();

        try {
            // add relations
            $this->applyRelations($servers);

            // apply filters
            $this->applyFilters($servers);

            // apply sorting
            $this->applySorts($servers);

            // apply date filters
            $this->applyDateFilters($servers);

        } catch (\Exception $error) {
            return response()->json(['success' => false, 'errors' => [$error->getMessage()]], 422);
        }

        return response()->json(array_merge(
            ['status' => true],
            $servers->paginate($request->get('paginate', 15))->toArray()
        ), 200);
    }
}