<?php

namespace Tests\Feature;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_tag(): void
    {
        $this->actingAs($this->user);

        $this->post(route('tags.store'), [
            'name' => 'test',
            'color' => config('core.colors')[0],
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('tags', [
            'project_id' => $this->user->current_project_id,
            'name' => 'test',
            'color' => config('core.colors')[0],
        ]);
    }

    public function test_get_tags_list(): void
    {
        $this->actingAs($this->user);

        Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
        ]);

        $this->get(route('tags'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('tags/index'));
    }

    public function test_delete_tag(): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
        ]);

        $this->delete(route('tags.destroy', $tag));

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_create_tag_handles_invalid_color(): void
    {
        $this->actingAs($this->user);

        $this->post(route('tags.store'), [
            'name' => 'test',
            'color' => 'invalid-color',
        ])
            ->assertSessionHasErrors('color');
    }

    public function test_create_tag_handles_invalid_name(): void
    {
        $this->actingAs($this->user);

        $this->post(route('tags.store'), [
            'name' => '',
            'color' => 'invalid-color',
        ])
            ->assertSessionHasErrors('name');
    }

    public function test_edit_tag(): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
        ]);

        $this->patch(route('tags.update', $tag), [
            'name' => 'New Name',
            'color' => config('core.colors')[1],
        ]);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'New Name',
            'color' => config('core.colors')[1],
        ]);
    }

    public function test_attach_existing_tag_to_server(): void
    {
        $this->markTestSkipped();

        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
            'name' => 'staging',
        ]);

        // Livewire::test(ServerDetails::class, [
        //     'server' => $this->server,
        // ])
        //     ->callInfolistAction('tags.*', 'edit_tags', [
        //         'tags' => [$tag->id],
        //     ])
        //     ->assertSuccessful();

        $this->assertDatabaseHas('taggables', [
            'taggable_id' => $this->server->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_detach_tag_from_server(): void
    {
        $this->markTestSkipped();

        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
            'name' => 'staging',
        ]);

        $this->server->tags()->attach($tag);

        // Livewire::test(ServerDetails::class, [
        //     'server' => $this->server,
        // ])
        //     ->callInfolistAction('tags.*', 'edit_tags', [
        //         'tags' => [],
        //     ])
        //     ->assertSuccessful();

        $this->assertDatabaseMissing('taggables', [
            'taggable_id' => $this->server->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_attach_existing_tag_to_site(): void
    {
        $this->markTestSkipped();

        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
            'name' => 'staging',
        ]);

        // Livewire::test(SiteDetails::class, [
        //     'server' => $this->server,
        //     'site' => $this->site,
        // ])
        //     ->callInfolistAction('tags.*', 'edit_tags', [
        //         'tags' => [$tag->id],
        //     ])
        //     ->assertSuccessful();

        $this->assertDatabaseHas('taggables', [
            'taggable_id' => $this->site->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_detach_tag_from_site(): void
    {
        $this->markTestSkipped();

        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
            'name' => 'staging',
        ]);

        $this->site->tags()->attach($tag);

        // Livewire::test(SiteDetails::class, [
        //     'server' => $this->server,
        //     'site' => $this->site,
        // ])
        //     ->callInfolistAction('tags.*', 'edit_tags', [
        //         'tags' => [],
        //     ])
        //     ->assertSuccessful();

        $this->assertDatabaseMissing('taggables', [
            'taggable_id' => $this->site->id,
            'tag_id' => $tag->id,
        ]);
    }
}
