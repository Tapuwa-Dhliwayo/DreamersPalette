<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorldMap extends Model
{
    use ArchivesStudioRecords, RevisesWorldRecord;

    protected $table = 'studio_world_maps';

    protected $fillable = ['title', 'image_path', 'width_px', 'height_px', 'width_units', 'unit', 'pins', 'journey', 'notes_md'];

    protected function casts(): array
    {
        return ['pins' => 'array', 'journey' => 'array', 'width_units' => 'float', 'width_px' => 'integer', 'height_px' => 'integer', 'revision' => 'integer'];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MapVersion::class)->orderByDesc('revision');
    }

    public function captureVersion(int $author): void
    {
        $this->refresh();
        $this->versions()->create(['revision' => $this->revision, 'snapshot' => $this->only($this->getFillable()), 'created_by' => $author, 'created_at' => now()]);
    }

    protected function afterArchiveChange(int $authorId): void
    {
        $this->captureVersion($authorId);
    }

    public function journeyLegs(): array
    {
        $pins = collect($this->pins)->keyBy('key');
        $legs = [];
        foreach (array_slice($this->journey, 1, null, true) as $index => $key) {
            $from = $pins->get($this->journey[$index - 1]);
            $to = $pins->get($key);
            if (! $from || ! $to) {
                continue;
            }
            $distance = $this->width_units ? hypot(($to['x'] - $from['x']) / 100, ($to['y'] - $from['y']) / 100 * $this->height_px / $this->width_px) * $this->width_units : null;
            $legs[] = ['from' => $from['name'], 'to' => $to['name'], 'distance' => $distance];
        }

        return $legs;
    }
}
