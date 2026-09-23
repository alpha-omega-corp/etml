<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A revision note: one self-contained page covering a few topics of a subject
 * (« Histoire suisse », « Industrialisation »). The page is kept as written and
 * shown as is; `subjects` only names what it covers, for the list.
 */
#[Fillable(['language_id', 'title', 'subjects', 'html', 'position'])]
class Note extends Model
{
    /** @use HasFactory<\Database\Factories\NoteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['subjects' => 'array'];
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
