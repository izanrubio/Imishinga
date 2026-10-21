<?php

use App\Enums\LabelColor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The hex palette used before label colours became keys, mapped to the
     * closest key. Anything else falls back to the default colour.
     *
     * @var array<string, string>
     */
    private const array LEGACY_HEX = [
        '#0079BF' => 'blue',
        '#D29034' => 'amber',
        '#519839' => 'green',
        '#B04632' => 'red',
        '#89609E' => 'violet',
        '#CD5A91' => 'pink',
        '#4BBF6B' => 'green',
        '#838C91' => 'teal',
    ];

    public function up(): void
    {
        foreach (self::LEGACY_HEX as $hex => $key) {
            DB::table('labels')->whereRaw('UPPER(color) = ?', [$hex])->update(['color' => $key]);
        }

        DB::table('labels')
            ->whereNotIn('color', array_column(LabelColor::cases(), 'value'))
            ->update(['color' => LabelColor::Blue->value]);
    }

    public function down(): void
    {
        // Data normalization only; the original values are not recoverable.
    }
};
