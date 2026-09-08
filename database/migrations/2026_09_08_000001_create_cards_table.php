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
            $table->string('section');
            $table->text('de');
            $table->text('fr');
            $table->text('example')->nullable();
            $table->unsignedInteger('position')->index();
            $table->timestamps();

            $table->unique('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
