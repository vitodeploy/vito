<?php

namespace App\Jobs\Site;

use App\Exceptions\FailedToInstallWordpress;
use App\Jobs\Job;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Site;
use App\SSHCommands\Wordpress\InstallWordpressCommand;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class InstallWordpress extends Job
{
    protected Site $site;

    protected ?Database $database;

    protected ?DatabaseUser $databaseUser;

    public function __construct(Site $site)
    {
        $this->site = $site;
    }

    /**
     * @throws ValidationException
     * @throws FailedToInstallWordpress
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->setupDatabase();

        $result = $this->site->server->ssh()->exec(
            new InstallWordpressCommand(
                $this->site->path,
                $this->site->domain,
                $this->database->name,
                $this->databaseUser->username,
                $this->databaseUser->password,
                'localhost',
                'wp_',
                $this->site->type_data['username'],
                $this->site->type_data['password'],
                $this->site->type_data['email'],
                $this->site->type_data['title'],
            ),
            'install-wordpress',
            $this->site->id
        );

        if (! Str::contains($result, 'Wordpress installed!')) {
            throw new FailedToInstallWordpress($result);
        }
    }

    /**
     * @throws ValidationException
     */
    private function setupDatabase()
    {
        // create database
        $this->database = $this->site->server->databases()->where('name', $this->site->type_data['database'])->first();
        if (! $this->database) {
            $this->database = new Database([
                'server_id' => $this->site->server_id,
                'name' => $this->site->type_data['database'],
            ]);
            $this->database->server->database()->handler()->create($this->database->name);
            $this->database->is_created = true;
            $this->database->save();
        }

        // create database user
        $this->databaseUser = $this->site->server->databaseUsers()->where('username', $this->site->type_data['database_user'])->first();
        if (! $this->databaseUser) {
            $this->databaseUser = new DatabaseUser([
                'server_id' => $this->site->server_id,
                'username' => $this->site->type_data['database_user'],
                'password' => Str::random(10),
                'host' => 'localhost',
            ]);
            $this->databaseUser->save();
            $this->databaseUser->server->database()->handler()->createUser($this->databaseUser->username, $this->databaseUser->password, $this->databaseUser->host);
            $this->databaseUser->is_created = true;
            $this->databaseUser->save();
        }

        // link database user
        $linkedDatabases = $this->databaseUser->databases ?? [];
        if (! in_array($this->database->name, $linkedDatabases)) {
            $linkedDatabases[] = $this->database->name;
            $this->databaseUser->databases = $linkedDatabases;
            $this->databaseUser->server->database()->handler()->unlink(
                $this->databaseUser->username,
                $this->databaseUser->host,
            );
            $this->databaseUser->server->database()->handler()->link(
                $this->databaseUser->username,
                $this->databaseUser->host,
                $this->databaseUser->databases
            );
            $this->databaseUser->save();
        }
    }
}
