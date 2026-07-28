<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\IssuedLetterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * An issued letter is an immutable document. Nothing here is mass assignable:
 * LetterPdfService writes every field explicitly at issue time, and only
 * revocation ever changes a row afterwards.
 */
class IssuedLetter extends Model
{
    /** @use HasFactory<IssuedLetterFactory> */
    use Auditable, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [];

    /**
     * The stored path is an internal detail and must never leak into a
     * JSON response alongside a download link.
     *
     * @var list<string>
     */
    protected $hidden = [
        'file_path',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function letterRequest(): BelongsTo
    {
        return $this->belongsTo(LetterRequest::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Whether the PDF on disk still matches the hash recorded at issue time.
     * A false here means the file was replaced or corrupted underneath us.
     */
    public function fileIsIntact(): bool
    {
        $disk = Storage::disk('private');

        if (! $disk->exists($this->file_path)) {
            return false;
        }

        return hash_equals($this->file_hash, hash('sha256', $disk->get($this->file_path)));
    }
}
