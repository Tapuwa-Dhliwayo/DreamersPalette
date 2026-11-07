<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AssistantMessage extends Model
{
    // Replies longer than this are documents (character sheets, timelines) rather than conversation; they open in a reader.
    private const DOCUMENT_LENGTH = 6000;

    protected $table = 'studio_assistant_messages';

    protected $fillable = ['role', 'content'];

    protected function casts(): array
    {
        return [];
    }

    public function readsAsDocument(): bool
    {
        return $this->role === 'assistant' && mb_strlen($this->content ?? '') > self::DOCUMENT_LENGTH;
    }

    public function documentTitle(): string
    {
        $firstSentence = Str::of($this->plainText())->before('. ')->rtrim('.');

        return Str::limit((string) $firstSentence, 110);
    }

    public function documentSections(): array
    {
        preg_match_all('/^#{2,3}\s+(.+)$/m', $this->content ?? '', $headings);

        return array_map(fn (string $heading): string => trim($heading, " *_\t"), $headings[1]);
    }

    public function documentExcerpt(): string
    {
        $afterIntro = preg_split('/^#{2,3}\s+.+$/m', $this->content ?? '', 2)[1] ?? $this->content;

        return Str::limit(self::markdownToText($afterIntro), 320);
    }

    public function citationCount(): int
    {
        return preg_match_all('/\]\(\/studio\/worlds\//', $this->content ?? '');
    }

    private function plainText(): string
    {
        return self::markdownToText($this->content ?? '');
    }

    private static function markdownToText(string $markdown): string
    {
        // Citation links read as noise in a summary, so they are dropped entirely.
        $withoutLinks = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '', $markdown);

        return Str::squish(strip_tags(Str::markdown($withoutLinks, ['html_input' => 'strip'])));
    }
}
