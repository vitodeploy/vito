<?php

namespace App\Http\Controllers;

use App\Actions\FirewallRule\CreateRule;
use App\Actions\FirewallRule\DeleteRule;
use App\Facades\Toast;
use App\Helpers\HtmxResponse;
use App\Models\FirewallRule;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailServiceController extends Controller
{
    public function index(Server $server): View
    {
        $this->authorize('manage', $server);

        return view('email-service.index', [
            'server' => $server,
            'emailService' => $server->services->where('type', 'email_service')->first(),
        ]);
    }
}
