<?php

namespace Tests\Feature;

use App\Models\Option;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_option_api_stores_title_and_description(): void
    {
        Sanctum::actingAs($this->makeUser());

        $response = $this->postJson('/api/options', [
            'name' => 'Size',
            'title' => 'Choose a size',
            'description' => 'Select the portion size for this item.',
            'values' => [
                ['name' => 'Large', 'price' => 2.00],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Size')
            ->assertJsonPath('title', 'Choose a size')
            ->assertJsonPath('description', 'Select the portion size for this item.')
            ->assertJsonPath('option_values.0.name', 'Large');

        $this->assertDatabaseHas('options', [
            'name' => 'Size',
            'title' => 'Choose a size',
            'description' => 'Select the portion size for this item.',
        ]);
    }

    public function test_option_api_updates_title_and_description(): void
    {
        Sanctum::actingAs($this->makeUser());

        $option = Option::query()->create([
            'name' => 'Sauce',
            'title' => 'Pick sauce',
            'description' => 'Original sauce copy.',
        ]);

        $response = $this->putJson("/api/options/{$option->id}", [
            'name' => 'Sauces',
            'title' => 'Choose sauces',
            'description' => 'Updated sauce copy.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('name', 'Sauces')
            ->assertJsonPath('title', 'Choose sauces')
            ->assertJsonPath('description', 'Updated sauce copy.');

        $this->assertDatabaseHas('options', [
            'id' => $option->id,
            'name' => 'Sauces',
            'title' => 'Choose sauces',
            'description' => 'Updated sauce copy.',
        ]);
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'firstname' => 'API',
            'lastname' => 'User',
            'username' => 'api-user',
            'email' => 'api-user@example.com',
            'password' => 'password',
            'role_id' => 1,
        ]);
    }
}
