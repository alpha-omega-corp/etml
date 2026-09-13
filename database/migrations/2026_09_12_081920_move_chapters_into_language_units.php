<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Branches and chapters give way to languages and units. The two language
 * branches become languages, their chapters become vocabulary units, and the
 * card's sides lose their German / French names so any language can use them.
 *
 * Chapters filed under a non-language branch have no home in the new model and
 * are dropped with their cards; in practice only Allemand ever had any.
 */
return new class extends Migration
{
    /**
     * The branches that were really languages, and what the new row says about
     * each: the code a pasted list may key on, and the name the language gives
     * itself on the back of a card.
     *
     * @var array<string, array{name: string, code: string, label: string, position: int}>
     */
    private const LANGUAGES = [
        'allemand' => ['name' => 'Allemand', 'code' => 'de', 'label' => 'Deutsch', 'position' => 1],
        'anglais' => ['name' => 'Anglais', 'code' => 'en', 'label' => 'English', 'position' => 2],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::LANGUAGES as $slug => $language) {
            DB::table('languages')->insertOrIgnore($language + [
                'slug' => $slug,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $languageIds = DB::table('languages')->pluck('id', 'slug');
        $branchSlugs = DB::table('branches')->pluck('slug', 'id');

        /** @var array<int, int> $unitIds  chapter id => unit id */
        $unitIds = [];

        foreach (DB::table('chapters')->orderBy('id')->get() as $chapter) {
            $slug = $branchSlugs[$chapter->branch_id] ?? null;

            if ($slug === null || ! isset($languageIds[$slug])) {
                continue;
            }

            $unitIds[$chapter->id] = DB::table('units')->insertGetId([
                'language_id' => $languageIds[$slug],
                'kind' => 'vocabulary',
                'name' => $chapter->name,
                'position' => $chapter->position,
                'created_at' => $chapter->created_at ?? $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('cards', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        foreach ($unitIds as $chapterId => $unitId) {
            DB::table('cards')->where('chapter_id', $chapterId)->update(['unit_id' => $unitId]);
        }

        DB::table('cards')->whereNull('unit_id')->delete();

        Schema::table('cards', function (Blueprint $table) {
            $table->dropUnique(['chapter_id', 'position']);
            $table->dropConstrainedForeignId('chapter_id');
            $table->renameColumn('de', 'term');
            $table->renameColumn('fr', 'translation');
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable(false)->change();
            $table->unique(['unit_id', 'position']);
        });

        Schema::dropIfExists('chapters');
        Schema::dropIfExists('branches');
    }

    public function down(): void
    {
        $now = now();

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('position')->index();
            $table->timestamps();
        });

        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(['branch_id', 'position']);
        });

        $branchIds = [];

        foreach (DB::table('languages')->orderBy('position')->get() as $language) {
            $branchIds[$language->id] = DB::table('branches')->insertGetId([
                'name' => $language->name,
                'slug' => $language->slug,
                'position' => $language->position,
                'created_at' => $language->created_at ?? $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('cards', function (Blueprint $table) {
            $table->foreignId('chapter_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->renameColumn('term', 'de');
            $table->renameColumn('translation', 'fr');
        });

        // Positions were unique per language and kind; back in one chapter list
        // per branch they have to be renumbered.
        $position = [];

        foreach (DB::table('units')->orderBy('kind')->orderBy('position')->get() as $unit) {
            $branchId = $branchIds[$unit->language_id];
            $position[$branchId] = ($position[$branchId] ?? 0) + 1;

            $chapterId = DB::table('chapters')->insertGetId([
                'branch_id' => $branchId,
                'name' => $unit->name,
                'position' => $position[$branchId],
                'created_at' => $unit->created_at ?? $now,
                'updated_at' => $now,
            ]);

            DB::table('cards')->where('unit_id', $unit->id)->update(['chapter_id' => $chapterId]);
        }

        Schema::table('cards', function (Blueprint $table) {
            $table->dropUnique(['unit_id', 'position']);
            $table->dropConstrainedForeignId('unit_id');
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->unsignedBigInteger('chapter_id')->nullable(false)->change();
            $table->unique(['chapter_id', 'position']);
        });
    }
};
