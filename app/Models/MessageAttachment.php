<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{

    protected $fillable = [
        'message_id',
        'filename',
        'original_filename',
        'path',
        'size',
        'mime_type',
        'file_hash'
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isDocument(): bool
    {
        return in_array($this->mime_type, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ]);
    }

    public function getFullPath(): string
    {
        return Storage::disk('public')->path($this->path);
    }

    public function getUrl(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function getThumbnailUrl(): string
    {
        if ($this->isImage()) {
            $thumbnailPath = str_replace('.', '_thumb.', $this->path);
            if (Storage::disk('public')->exists($thumbnailPath)) {
                return Storage::disk('public')->url($thumbnailPath);
            }
        }

        return $this->getUrl();
    }


}
