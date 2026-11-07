<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoemAnalysisSnapshot extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Analysis source snapshots are immutable.'));
    }

    public static function context(Poem $poem): array
    {
        $poem->loadMissing('collection');

        return [
            'title' => $poem->title,
            'content_md' => $poem->content_md,
            'collection_id' => $poem->collection_id,
            'collection_title' => $poem->collection?->title,
            'collection_description' => $poem->collection?->description,
        ];
    }

    public static function fingerprint(Poem $poem): string
    {
        return hash('sha256', json_encode(self::context($poem), JSON_THROW_ON_ERROR));
    }

    public static function capture(Poem $poem): self
    {
        return self::firstOrCreate(
            ['poem_id' => $poem->id, 'fingerprint' => self::fingerprint($poem)],
            self::context($poem),
        );
    }
}
