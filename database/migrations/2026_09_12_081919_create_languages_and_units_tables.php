<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The card game stops belonging to one subject and becomes a module: a
 * language owns units (vocabulary chapters, verb pages) and a programme of
 * dated tests pointing at them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // Allemand
            $table->string('slug')->unique();  // allemand
            $table->string('code', 8);         // de — the key a pasted word list may use
            $table->string('label');           // Deutsch — the title on the back of a card
            $table->unsignedInteger('position')->index();
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('kind');            // vocabulary | verbs
            $table->string('name');
            $table->unsignedInteger('position');
            $table->timestamps();

            // Order is per language and per kind: chapter 1 and page 1 coexist.
            $table->unique(['language_id', 'kind', 'position']);
            $table->index(['language_id', 'kind']);
        });

        Schema::create('program_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->string('title')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('program_entry_unit', function (Blueprint $table) {
            $table->foreignId('program_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();

            $table->primary(['program_entry_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_entry_unit');
        Schema::dropIfExists('program_entries');
        Schema::dropIfExists('units');
        Schema::dropIfExists('languages');
    }
};
