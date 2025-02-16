<?php

namespace Tests\Feature;

use App\Facades\SSH;
use App\Models\File;
use App\Web\Pages\Servers\FileManager\Index;
use App\Web\Pages\Servers\FileManager\Widgets\FilesList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_files(): void
    {
        SSH::fake(<<<'EOF'
        total 32
        drwxr-xr-x 7 vito vito 4096 Feb 2 19:42 .
        drwxr-xr-x 3 root root 4096 Feb 1 18:44 ..
        drwx------ 3 vito vito 4096 Feb 1 18:45 .cache
        drwxrwxr-x 3 vito vito 4096 Feb 1 18:45 .config
        -rw-rw-r-- 1 vito vito 82 Feb 2 14:13 .gitconfig
        drwxrwxr-x 3 vito vito 4096 Feb 1 18:45 .local
        drwxr-xr-x 2 vito vito 4096 Feb 2 14:13 .ssh
        drwxrwxr-x 3 vito vito 4096 Feb 2 21:25 test.vitodeploy.com
        EOF
        );

        $this->actingAs($this->user);

        $this->get(
            Index::getUrl([
                'server' => $this->server,
            ])
        )
            ->assertSuccessful()
            ->assertSee('.cache')
            ->assertSee('.config');
    }

    public function test_upload_file(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Livewire::test(FilesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('upload', null, [
                'file' => UploadedFile::fake()->create('test.txt'),
            ])
            ->assertSuccessful();
    }

    public function test_create_file(): void
    {
        SSH::fake(<<<'EOF'
        total 3
        drwxr-xr-x 7 vito vito 4096 Feb 2 19:42 .
        drwxr-xr-x 3 root root 4096 Feb 1 18:44 ..
        -rw-rw-r-- 1 vito vito 82 Feb 2 14:13 test.txt
        EOF
        );

        $this->actingAs($this->user);

        Livewire::test(FilesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('new-file', null, [
                'name' => 'test.txt',
                'content' => 'Hello, world!',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('files', [
            'name' => 'test.txt',
        ]);
    }

    public function test_create_directory(): void
    {
        SSH::fake(<<<'EOF'
        total 3
        drwxr-xr-x 7 vito vito 4096 Feb 2 19:42 .
        drwxr-xr-x 3 root root 4096 Feb 1 18:44 ..
        drwxr-xr-x 2 vito vito 4096 Feb 2 14:13 test
        EOF
        );

        $this->actingAs($this->user);

        Livewire::test(FilesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('new-directory', null, [
                'name' => 'test',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('files', [
            'name' => 'test',
        ]);
    }

    public function test_download_file(): void
    {
        SSH::fake(<<<'EOF'
        total 3
        drwxr-xr-x 7 vito vito 4096 Feb 2 19:42 .
        drwxr-xr-x 3 root root 4096 Feb 1 18:44 ..
        -rw-rw-r-- 1 vito vito 82 Feb 2 14:13 test.txt
        EOF
        );

        $this->actingAs($this->user);

        $this->get(
            Index::getUrl([
                'server' => $this->server,
            ])
        )->assertSuccessful();

        $file = File::query()->where('name', 'test.txt')->firstOrFail();

        Livewire::test(FilesList::class, [
            'server' => $this->server,
        ])
            ->assertTableActionVisible('download', $file)
            ->callTableAction('download', $file)
            ->assertSuccessful();
    }
}
