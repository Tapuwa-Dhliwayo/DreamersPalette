<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'unsafe-inline'; font-src data:; img-src 'none'"><title>{{ $manifest['title'] }}</title>
<style>
@page { size: A4; }
body { font-family: Georgia, 'Times New Roman', serif; color: #171717; font-size: 11pt; line-height: 1.6; overflow-wrap: anywhere; }
h1 { font-size: 28pt; line-height: 1.25; margin: 0 0 20pt; }
h2 { font-size: 20pt; line-height: 1.3; margin: 0 0 20pt; }
h3 { font-size: 13pt; margin: 20pt 0 10pt; }
p { margin: 0 0 10pt; orphans: 3; widows: 3; }
.title-page { page-break-after: always; padding-top: 120pt; }
.metadata { font-family: Arial, sans-serif; font-size: 10pt; color: #525252; }
.chapter + .chapter { page-break-before: always; }
.summary { border-top: 1pt solid #d4d4d4; padding-top: 12pt; margin-top: 24pt; }
blockquote { border-left: 1pt solid #d4d4d4; margin: 10pt 0 10pt 12pt; padding-left: 12pt; }
pre, code { font-family: monospace; font-size: 9pt; white-space: pre-wrap; overflow-wrap: break-word; }
a { color: #171717; text-decoration: underline; }
table { border-collapse: collapse; width: 100%; font-size: 9pt; } th, td { border: 1pt solid #d4d4d4; padding: 6pt; }
</style></head><body>
<div class="title-page"><h1>{{ $manifest['title'] }}</h1><p>{{ $manifest['author'] }}</p><p class="metadata">{{ $manifest['source_label'] }}<br>Compiled {{ \Carbon\CarbonImmutable::parse($manifest['captured_at'])->asDateTime() }}<br>{{ count($manifest['chapters']) }} {{ Str::plural('chapter', count($manifest['chapters'])) }}@if($manifest['summaries']) · Includes chapter summaries
@endif
</p></div>
@foreach($manifest['chapters'] as $chapter)
<section class="chapter"><h2>{{ $chapter['title'] }}</h2>
@if($manifest['prose']){!! \App\Services\ManuscriptCompiler::safePdfMarkdown($chapter['content_md']) !!}
@endif

@if($manifest['summaries'])<div class="summary"><h3>Chapter summary</h3>{!! \App\Services\ManuscriptCompiler::safePdfMarkdown($chapter['summary_md'] ?: '_No summary written._') !!}</div>
@endif

</section>

@endforeach

</body></html>
