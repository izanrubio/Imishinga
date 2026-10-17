<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_another_users_board_renders_the_404_page(): void
    {
        $user = User::factory()->create();
        $otherUsersBoard = Board::factory()->create();

        $response = $this->actingAs($user)->get(route('boards.show', $otherUsersBoard));

        $response->assertNotFound();
        $response->assertSee('Page not found');
        $response->assertSee('Back to boards');
    }

    public function test_the_404_page_renders_standalone_without_the_authenticated_nav(): void
    {
        $user = User::factory()->create();
        $otherUsersBoard = Board::factory()->create();

        $response = $this->actingAs($user)->get(route('boards.show', $otherUsersBoard));

        $response->assertNotFound();
        $response->assertDontSee('Log out');
    }
}
