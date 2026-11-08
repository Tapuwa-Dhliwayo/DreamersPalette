<div><x-studio.header active="reviews" /><main id="studio-main" class="manuscript-library"><h1 class="studio-page-title">Editorial reviews</h1>@forelse($grants as $grant)<a class="compile-export-row" href="{{ route('studio.review', $grant) }}"><div><h2>{{ $grant->draft->manuscript_title }}</h2><p>{{ $grant->draft->name }} · Access until {{ $grant->expires_at->asDateTime() }}</p></div><flux:icon.arrow-right /></a>
@empty<p>No active reviews have been shared with you.</p>
@endforelse</main></div>
