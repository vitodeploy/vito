<?php

namespace App\Http\Controllers;

use App\Actions\StorageProvider\ConnectDropbox;
use App\Actions\StorageProvider\CreateStorageProvider;
use App\Actions\StorageProvider\DeleteStorageProvider;
use App\Actions\StorageProvider\EditStorageProvider;
use App\Http\Resources\StorageProviderResource;
use App\Models\StorageProvider;
use App\Tables\StorageProviderTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\RouteAttributes\Attributes\Delete;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Middleware;
use Spatie\RouteAttributes\Attributes\Patch;
use Spatie\RouteAttributes\Attributes\Post;
use Spatie\RouteAttributes\Attributes\Prefix;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

#[Prefix('settings/storage-providers')]
#[Middleware(['auth'])]
class StorageProviderController extends Controller
{
    #[Get('/', name: 'storage-providers')]
    public function index(): Response
    {
        $this->authorize('viewAny', StorageProvider::class);

        $user = user();

        return Inertia::render('storage-providers/index', [
            'storageProviders' => StorageProviderTable::make(
                StorageProvider::getByProjectId($user->current_project_id, $user)
            )->simplePaginate(),
        ]);
    }

    #[Get('/json', name: 'storage-providers.json')]
    public function json(): ResourceCollection
    {
        $this->authorize('viewAny', StorageProvider::class);

        $user = user();
        $storageProviders = StorageProvider::getByProjectId($user->current_project_id, $user)
            ->get();

        return StorageProviderResource::collection($storageProviders);
    }

    #[Post('/', name: 'storage-providers.store')]
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StorageProvider::class);

        $projectId = $request->boolean('global') ? null : user()->currentProject?->id;

        $this->authorize('assignToProject', [StorageProvider::class, $projectId]);

        app(CreateStorageProvider::class)->create(user(), $request->all(), $projectId);

        return back()->with('success', 'Storage provider created.');
    }

    #[Post('/dropbox/redirect', name: 'storage-providers.dropbox.redirect')]
    public function dropboxRedirect(Request $request, ConnectDropbox $action): SymfonyResponse
    {
        $this->authorize('create', StorageProvider::class);

        $projectId = $request->boolean('global') ? null : user()->currentProject?->id;

        $this->authorize('assignToProject', [StorageProvider::class, $projectId]);

        return Inertia::location($action->redirectUrl($request->all(), $projectId));
    }

    #[Get('/dropbox/callback', name: 'storage-providers.dropbox.callback')]
    public function dropboxCallback(Request $request, ConnectDropbox $action): RedirectResponse
    {
        $this->authorize('create', StorageProvider::class);

        try {
            $action->handleCallback(user(), $request);
        } catch (ValidationException $e) {
            return to_route('storage-providers')->with('error', $e->validator->errors()->first());
        } catch (Throwable $e) {
            Log::error('Dropbox OAuth callback failed', ['exception' => get_class($e)]);

            return to_route('storage-providers')->with('error', __('Failed to connect to Dropbox.'));
        }

        return to_route('storage-providers')->with('success', 'Storage provider created.');
    }

    #[Patch('/{storageProvider}', name: 'storage-providers.update')]
    public function update(Request $request, StorageProvider $storageProvider): RedirectResponse
    {
        $this->authorize('update', $storageProvider);

        $projectId = $request->boolean('global') ? null : user()->currentProject?->id;

        $this->authorize('assignToProject', [StorageProvider::class, $projectId]);

        app(EditStorageProvider::class)->edit($storageProvider, $request->all(), $projectId);

        return back()->with('success', 'Storage provider updated.');
    }

    #[Delete('{storageProvider}', name: 'storage-providers.destroy')]
    public function destroy(StorageProvider $storageProvider): RedirectResponse
    {
        $this->authorize('delete', $storageProvider);

        app(DeleteStorageProvider::class)->delete($storageProvider);

        return to_route('storage-providers')->with('success', 'Storage provider deleted.');
    }
}
