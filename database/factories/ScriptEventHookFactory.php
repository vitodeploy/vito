<?php

namespace Database\Factories;

use App\Enums\ScriptEventHookEvent;
use App\Models\ScriptEventHook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScriptEventHook>
 */
class ScriptEventHookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event' => ScriptEventHookEvent::SITE_CREATED,
            'user' => 'root',
            'enabled' => true,
        ];
    }
}
