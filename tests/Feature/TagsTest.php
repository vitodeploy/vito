<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Web\Pages\Servers\Sites\Widgets\SiteDetails;
use App\Web\Pages\Servers\Widgets\ServerDetails;
use App\Web\Pages\Settings\Tags\Index;
use App\Web\Pages\Settings\Tags\Widgets\TagsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_tag(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Index::class)
            ->callAction('create', [
                'name' => 'test',
                'color' => config('core.tag_colors')[0],
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('tags', [
            'project_id' => $this->user->current_project_id,
            'name' => 'test',
            'color' => config('core.tag_colors')[0],
        ]);
    }

    public function test_get_tags_list(): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
        ]);

        $this->get(Index::getUrl())
            ->assertSuccessful()
            ->assertSee($tag->name);
    }

    public function test_delete_tag(): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
        ]);

        Livewire::test(TagsList::class)
            ->callTableAction('delete', $tag->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_create_tag_handles_invalid_color(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Index::class)
            ->callAction('create', [
                'name' => 'test',
                'color' => 'invalid-color',
            ])
            ->assertHasActionErrors();
    }

    public function test_create_tag_handles_invalid_name(): void
    {
        $this->actingAs($this->user);

        Livewire::test(Index::class)
            ->callAction('create', [
                'name' => '',
                'color' => config('core.tag_colors')[0],
            ])
            ->assertHasActionErrors();
    }

    public function test_edit_tag(): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
        ]);

        Livewire::test(TagsList::class)
            ->callTableAction('edit', $tag->id, [
                'name' => 'New Name',
                'color' => config('core.tag_colors')[1],
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'New Name',
            'color' => config('core.tag_colors')[1],
        ]);
    }

    public function test_attach_existing_tag_to_server(): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
            'name' => 'staging',
        ]);

        Livewire::test(ServerDetails::class, [
            'server' => $this->server,
        ])
            ->callInfolistAction('tags.*', 'edit_tags', [
                'tags' => [$tag->id],
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('taggables', [
            'taggable_id' => $this->server->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_detach_tag_from_server(): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
            'name' => 'staging',
        ]);

        $this->server->tags()->attach($tag);

        Livewire::test(ServerDetails::class, [
            'server' => $this->server,
        ])
            ->callInfolistAction('tags.*', 'edit_tags', [
                'tags' => [],
            ])
            ->assertSuccessful();

        $this->assertDatabaseMissing('taggables', [
            'taggable_id' => $this->server->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_attach_existing_tag_to_site(): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
            'name' => 'staging',
        ]);

        Livewire::test(SiteDetails::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callInfolistAction('tags.*', 'edit_tags', [
                'tags' => [$tag->id],
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('taggables', [
            'taggable_id' => $this->site->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_detach_tag_from_site(): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
            'name' => 'staging',
        ]);

        $this->site->tags()->attach($tag);

        Livewire::test(SiteDetails::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callInfolistAction('tags.*', 'edit_tags', [
                'tags' => [],
            ])
            ->assertSuccessful();

        $this->assertDatabaseMissing('taggables', [
            'taggable_id' => $this->site->id,
            'tag_id' => $tag->id,
        ]);
    }
}
