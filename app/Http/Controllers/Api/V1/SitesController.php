<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Traits\ApiQueryBuilder;
use Illuminate\Http\Request;
use App\Models\Site;

class SitesController extends Controller
{
    use ApiQueryBuilder;

    /**
     * Define relationships that are allowed to be loaded.
     *
     * @var array
     */
    protected $relations = [
        'server',
        'logs',
        'deployments',
        'gitHook',
        'deploymentScript',
        'queues',
        'ssls',
        'activeSsl',
    ];

    /**
     * Define allowed filters.
     *
     * @var array
     */
    protected $filters = [
        'server_id',
        'type',
        'type_data',
        'domain',
        'aliases',
        'web_directory',
        'path',
        'php_version',
        'source_control',
        'source_control_id',
        'repository',
        'ssh_key',
        'branch',
        'status',
        'port',
        'progress',
    ];

    /**
     * Allowed sort columns and their directions.
     *
     * @var array
     */
    protected $sorts = [
        'server_id',
        'type',
        'type_data',
        'domain',
        'aliases',
        'web_directory',
        'path',
        'php_version',
        'source_control',
        'source_control_id',
        'repository',
        'ssh_key',
        'branch',
        'status',
        'port',
        'progress',
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
     * Get all sites.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(Request $request)
    {
        $sites = Site::query();

        try {
            // add relations
            $this->applyRelations($sites);

            // apply filters
            $this->applyFilters($sites);

            // apply sorting
            $this->applySorts($sites);

            // apply date filters
            $this->applyDateFilters($sites);

        } catch (\Exception $error) {
            return response()->json(['success' => false, 'errors' => [$error->getMessage()]], 422);
        }

        return response()->json(array_merge(
            ['status' => true],
            $sites->paginate($request->get('paginate', 15))->toArray()
        ), 200);
    }
}