<?php

namespace Tests\Feature;

use App\Models\Server;
use App\Models\Site;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_tag(): void
    {
        $this->actingAs($this->user);

        $this->post(route('settings.tags.create'), [
            'name' => 'test',
            'color' => config('core.tag_colors')[0],
        ])->assertSessionDoesntHaveErrors();

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

        $this->get(route('settings.tags'))
            ->assertSuccessful()
            ->assertSee($tag->name);
    }

    public function test_delete_tag(): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
        ]);

        $this->delete(route('settings.tags.delete', $tag->id))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_create_tag_handles_invalid_color(): void
    {
        $this->actingAs($this->user);

        $this->post(route('settings.tags.create'), [
            'name' => 'test',
            'color' => 'invalid-color',
        ])->assertSessionHasErrors('color');
    }

    public function test_create_tag_handles_invalid_name(): void
    {
        $this->actingAs($this->user);

        $this->post(route('settings.tags.create'), [
            'name' => '',
            'color' => config('core.tag_colors')[0],
        ])->assertSessionHasErrors('name');
    }

    public function test_edit_tag(): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
        ]);

        $this->post(route('settings.tags.update', ['tag' => $tag]), [
            'name' => 'New Name',
            'color' => config('core.tag_colors')[1],
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'New Name',
            'color' => config('core.tag_colors')[1],
        ]);
    }

    /**
     * @dataProvider data
     */
    public function test_attach_existing_tag_to_taggable(array $input): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
            'name' => $input['name'],
        ]);

        $input['taggable_id'] = match ($input['taggable_type']) {
            Server::class => $this->server->id,
            Site::class => $this->site->id,
            default => $this->fail('Unknown taggable type'),
        };

        $this->post(route('tags.attach'), $input)->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('taggables', [
            'taggable_id' => $input['taggable_id'],
            'tag_id' => $tag->id,
        ]);
    }

    /**
     * @dataProvider data
     */
    public function test_attach_new_tag_to_taggable(array $input): void
    {
        $this->actingAs($this->user);

        $input['taggable_id'] = match ($input['taggable_type']) {
            Server::class => $this->server->id,
            Site::class => $this->site->id,
            default => $this->fail('Unknown taggable type'),
        };

        $this->post(route('tags.attach'), $input)->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('tags', [
            'name' => $input['name'],
        ]);

        $tag = Tag::query()->where('name', $input['name'])->firstOrFail();

        $this->assertDatabaseHas('taggables', [
            'taggable_id' => $input['taggable_id'],
            'tag_id' => $tag->id,
        ]);
    }

    /**
     * @dataProvider data
     */
    public function test_detach_tag(array $input): void
    {
        $this->actingAs($this->user);

        $tag = Tag::factory()->create([
            'project_id' => $this->user->current_project_id,
            'name' => $input['name'],
        ]);

        $taggable = match ($input['taggable_type']) {
            Server::class => $this->server,
            Site::class => $this->site,
            default => $this->fail('Unknown taggable type'),
        };

        $input['taggable_id'] = $taggable->id;

        $taggable->tags()->attach($tag);

        $this->post(route('tags.detach', $tag->id), $input)->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('taggables', [
            'taggable_id' => $input['taggable_id'],
            'tag_id' => $tag->id,
        ]);
    }

    public static function data(): array
    {
        return [
            [
                [
                    'taggable_type' => Server::class,
                    'name' => 'staging',
                ],
            ],
            [
                [
                    'taggable_type' => Site::class,
                    'name' => 'production',
                ],
            ],
        ];
    }
}
