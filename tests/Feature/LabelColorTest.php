<?php

namespace Tests\Feature;

use App\Enums\LabelColor;
use App\Livewire\Boards\LabelManager;
use App\Models\Board;
use App\Models\Label;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class LabelColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_enum_has_the_eight_design_keys_and_literal_classes(): void
    {
        $this->assertSame(
            ['red', 'orange', 'amber', 'green', 'teal', 'blue', 'violet', 'pink'],
            array_column(LabelColor::cases(), 'value'),
        );

        foreach (LabelColor::cases() as $color) {
            $this->assertSame('bg-label-'.$color->value, $color->bgClass());
        }
    }

    public function test_only_the_color_keys_are_accepted_when_saving(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->for($user)->create();

        foreach (['#B04632', 'purple', ''] as $invalid) {
            Livewire::actingAs($user)
                ->test(LabelManager::class, ['board' => $board])
                ->set('name', 'Bad')
                ->set('color', $invalid)
                ->call('save')
                ->assertHasErrors('color');
        }

        $this->assertDatabaseMissing('labels', ['name' => 'Bad']);

        Livewire::actingAs($user)
            ->test(LabelManager::class, ['board' => $board])
            ->set('name', 'Good')
            ->set('color', 'violet')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('labels', ['name' => 'Good', 'color' => 'violet']);
    }

    public function test_unknown_or_null_stored_value_falls_back_without_throwing(): void
    {
        $this->assertSame(LabelColor::Blue, LabelColor::fromValueOrDefault('#0079BF'));
        $this->assertSame(LabelColor::Blue, LabelColor::fromValueOrDefault(null));
        $this->assertSame(LabelColor::Pink, LabelColor::fromValueOrDefault('pink'));

        $label = Label::factory()->make(['color' => '#nonsense']);

        $this->assertSame('bg-label-blue', $label->colorClass());
    }

    public function test_migration_converts_legacy_values_to_keys(): void
    {
        $board = Board::factory()->create();
        $legacy = Label::factory()->for($board)->create(['color' => '#b04632']);
        $unknown = Label::factory()->for($board)->create(['color' => 'chartreuse']);
        $valid = Label::factory()->for($board)->create(['color' => 'teal']);

        (require database_path('migrations/2026_10_17_100000_normalize_label_colors.php'))->up();

        $this->assertSame('red', DB::table('labels')->find($legacy->id)->color);
        $this->assertSame('blue', DB::table('labels')->find($unknown->id)->color);
        $this->assertSame('teal', DB::table('labels')->find($valid->id)->color);
    }
}
