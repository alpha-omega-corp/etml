<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->string('section')->nullable();
            $table->text('de');
            $table->text('fr');
            $table->text('example')->nullable();
            $table->unsignedInteger('position');
            $table->timestamps();

            // Order is per chapter, so the seeder can upsert on it safely.
            $table->unique(['chapter_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
