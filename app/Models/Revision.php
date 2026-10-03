<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RevisionField;
use Database\Factories\RevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $fixture_id
 * @property int $import_id
 * @property RevisionField|null $field
 * @property string|null $old_value
 * @property string|null $new_value
 * @property Carbon $created_at
 * @property-read Fixture $fixture
 * @property-read Import $import
 */
#[Fillable(['fixture_id', 'import_id', 'field', 'old_value', 'new_value'])]
final class Revision extends Model
{
    /** @use HasFactory<RevisionFactory> */
    use HasFactory;

    /**
     * Revisions are never edited after they are recorded.
     */
    public const UPDATED_AT = null;

    /**
     * Get the fixture whose field the revision records.
     *
     * @return BelongsTo<Fixture, $this>
     */
    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    /**
     * Get the import that recorded the revision.
     *
     * @return BelongsTo<Import, $this>
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'field' => RevisionField::class,
        ];
    }
}
