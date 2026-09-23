<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A subject that is read rather than drilled — history — keeps revision notes
 * instead of decks. A note is one finished page covering a few of the
 * subject's topics, stored whole.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->json('subjects');          // ["Histoire suisse", "Industrialisation"]
            $table->longText('html');          // the page, as written
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(['language_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
