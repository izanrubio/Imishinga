<?php

use App\Enums\BoardColor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $validKeys = array_column(BoardColor::cases(), 'value');

        DB::table('boards')
            ->where(function ($query) use ($validKeys) {
                $query->whereNotIn('background_color', $validKeys)
                    ->orWhereNull('background_color');
            })
            ->update(['background_color' => BoardColor::Slate->value]);
    }

    public function down(): void
    {
        // Data normalization only; the original values are not recoverable.
    }
};
