<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Word lists are pasted as JSON now; the photo-import table and the note that
 * went with it have no reader left.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('chapter_images');

        if (Schema::hasColumn('chapters', 'extraction_note')) {
            Schema::table('chapters', function (Blueprint $table) {
                $table->dropColumn('extraction_note');
            });
        }
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->text('extraction_note')->nullable();
        });
    }
};
