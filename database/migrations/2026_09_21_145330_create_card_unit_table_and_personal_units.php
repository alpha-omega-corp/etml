<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A unit stops being a bag of rows and becomes a list of cards.
 *
 * `cards.unit_id` still says which unit wrote a card and therefore owns it,
 * but what a deck plays is now `card_unit`. That one indirection is what lets
 * a user keep a selection — a deck of their own made of cards a chapter
 * already holds — without copying a single word, so a word marked « je sais »
 * stays known wherever it is met.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // Null for the chapters and verb pages everyone shares; set for a
            // unit that belongs to one user alone.
            $table->foreignId('user_id')->nullable()->after('language_id')->constrained()->cascadeOnDelete();
        });

        // The shared units keep their guarantee — one chapter 3 per language —
        // while a selection is numbered inside its owner's own list. Two
        // partial indexes rather than one over the nullable column: Postgres
        // counts NULLs as distinct, so a single index would stop protecting
        // the chapters the moment the column was added.
        Schema::table('units', function (Blueprint $table) {
            $table->dropUnique(['language_id', 'kind', 'position']);
        });

        DB::statement('CREATE UNIQUE INDEX units_shared_position_unique ON units (language_id, kind, position) WHERE user_id IS NULL');
        DB::statement('CREATE UNIQUE INDEX units_owned_position_unique ON units (user_id, language_id, kind, position) WHERE user_id IS NOT NULL');

        Schema::create('card_unit', function (Blueprint $table) {
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');

            $table->primary(['unit_id', 'card_id']);
            $table->unique(['unit_id', 'position']);
        });

        // Every existing card is on its own unit's list, in its printed order.
        DB::statement('INSERT INTO card_unit (card_id, unit_id, position) SELECT id, unit_id, position FROM cards');
    }

    public function down(): void
    {
        Schema::dropIfExists('card_unit');

        // Personal units have no home once the column goes, and their
        // positions would collide with the shared ones under the old index.
        DB::table('units')->whereNotNull('user_id')->delete();

        DB::statement('DROP INDEX IF EXISTS units_shared_position_unique');
        DB::statement('DROP INDEX IF EXISTS units_owned_position_unique');

        Schema::table('units', function (Blueprint $table) {
            $table->unique(['language_id', 'kind', 'position']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
