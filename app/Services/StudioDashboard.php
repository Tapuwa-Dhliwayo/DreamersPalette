<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Manuscript;
use App\Models\ManuscriptRelease;
use App\Models\Poem;
use App\Models\PoetryCollection;
use App\Models\ReviewComment;
use App\Models\ReviewGrant;
use App\Models\User;
use App\Models\World;
use Illuminate\Support\Collection;

class StudioDashboard
{
    private const PUBLISHABLE = [
        'poems' => [Poem::class, 'Poems'],
        'collections' => [PoetryCollection::class, 'Collections'],
        'books' => [Book::class, 'Novels'],
    ];

    public function __construct(private WritingOverview $writing) {}

    /** @param array{manuscript_id?: int|null, days?: int, timezone?: string} $filters */
    public function forAuthor(User $user, array $filters): array
    {
        $manuscripts = $this->manuscripts($user);
        $scope = $manuscripts;
        if (! empty($filters['manuscript_id'])) {
            $scope = $manuscripts->where('id', $filters['manuscript_id']);
            abort_if($scope->isEmpty(), 404);
        }
        $projects = $this->projects($user, $manuscripts);

        return [
            'manuscripts' => $projects,
            'resume' => $this->resume($user, $projects)->sortByDesc('updated_at')->take(5)->values(),
            'publishing' => $this->publishing($user),
            'attention' => $this->attention($user, $manuscripts, $projects),
            'recent_activity' => $this->recentActivity($manuscripts)->sortByDesc('at')->take(6)->values(),
            'writing' => $this->writing->forManuscripts($scope, (int) ($filters['days'] ?? 30), $filters['timezone'] ?? config('app.timezone')),
        ];
    }

    private function manuscripts(User $user): Collection
    {
        if (! $user->can('manuscripts.view')) {
            return collect();
        }

        return Manuscript::where('author_id', $user->id)
            ->with([
                'world:id,name',
                'chapters:id,manuscript_id,title,position,word_count,updated_at',
                'drafts:id,manuscript_id,name,created_at',
            ])
            ->latest('updated_at')->get();
    }

    private function projects(User $user, Collection $manuscripts): Collection
    {
        $latestReleaseIds = ManuscriptRelease::selectRaw('MAX(id)')
            ->whereIn('manuscript_id', $manuscripts->pluck('id'))
            ->groupBy('manuscript_id');
        $releases = ManuscriptRelease::whereIn('id', $latestReleaseIds)
            ->get(['id', 'manuscript_id', 'draft_id'])->keyBy('manuscript_id');

        return $manuscripts->map(function ($manuscript) use ($releases, $user): array {
            $recent = $manuscript->chapters->sortByDesc('updated_at')->first();
            $latestDraft = $manuscript->drafts->first();
            $release = $releases->get($manuscript->id);

            return [
                'id' => $manuscript->id,
                'title' => $manuscript->title,
                'world' => $manuscript->world?->name,
                'draft_number' => $manuscript->working_draft_number,
                'words' => $manuscript->chapters->sum('word_count'),
                'chapters' => $manuscript->chapters,
                'finished_drafts' => $manuscript->drafts->count(),
                'updated_at' => $recent?->updated_at?->max($manuscript->updated_at) ?? $manuscript->updated_at,
                'recent_chapter' => $recent?->title,
                'recent_chapter_id' => $recent?->id,
                'weekly_word_goal' => $manuscript->weekly_word_goal,
                'can_update' => $user->can('update', $manuscript),
                'release_ready' => $user->can('finish', $manuscript) && $latestDraft && (! $release || (int) $latestDraft->id !== (int) $release->draft_id),
                'url' => '/studio/manuscripts/'.$manuscript->id,
            ];
        })->sortByDesc('updated_at')->values();
    }

    private function resume(User $user, Collection $projects): Collection
    {
        $resume = $projects->map(fn ($project) => [
            'title' => $project['title'],
            'type' => 'Manuscript',
            'detail' => $project['recent_chapter'] ? 'Chapter: '.$project['recent_chapter'] : 'No chapters yet',
            'updated_at' => $project['updated_at'],
            'url' => $project['url'].($project['recent_chapter_id'] ? '?chapter='.$project['recent_chapter_id'] : ''),
        ]);
        if ($user->can('poems.view')) {
            $recentPoems = Poem::where('author_id', $user->id)->latest('updated_at')->limit(3)->get(['id', 'title', 'updated_at', 'is_published']);
            $resume = $resume->concat($recentPoems->map(fn ($poem) => [
                'title' => $poem->title,
                'type' => 'Poem',
                'detail' => $poem->is_published ? 'Published' : 'Unpublished',
                'updated_at' => $poem->updated_at,
                'url' => '/dashboard/poems/'.$poem->id.'/edit',
            ]));
        }
        if ($user->can('worlds.view')) {
            $recentWorlds = World::where('author_id', $user->id)->latest('updated_at')->limit(2)->get(['id', 'name', 'updated_at']);
            $resume = $resume->concat($recentWorlds->map(fn ($world) => [
                'title' => $world->name,
                'type' => 'World',
                'detail' => 'Worldbuilding',
                'updated_at' => $world->updated_at,
                'url' => '/studio/worlds/'.$world->id,
            ]));
        }

        return $resume;
    }

    private function publishing(User $user): array
    {
        $publishing = [];
        foreach (self::PUBLISHABLE as $kind => [$model, $label]) {
            if (! $user->can($kind.'.view')) {
                continue;
            }
            $query = $model::where('author_id', $user->id);
            $publishing[] = [
                'kind' => $kind,
                'label' => $label,
                'total' => (clone $query)->count(),
                'published' => (clone $query)->where('is_published', true)->count(),
            ];
        }

        return $publishing;
    }

    private function attention(User $user, Collection $manuscripts, Collection $projects): Collection
    {
        $attention = $projects->where('release_ready', true)->map(fn ($project) => [
            'title' => $project['title'],
            'detail' => 'Finished draft available for release review',
            'url' => $project['url'].'/publishing',
        ])->values();
        if (! $user->can('reviews.view')) {
            return $attention;
        }
        $grants = ReviewGrant::whereIn('manuscript_id', $manuscripts->pluck('id'))->get(['id', 'manuscript_id']);
        $pending = ReviewComment::whereIn('grant_id', $grants->pluck('id'))
            ->whereNotNull('suggested_md')
            ->whereNull('accepted_at')
            ->whereNull('dismissed_at')
            ->select('grant_id')->selectRaw('COUNT(*) AS pending_count')->groupBy('grant_id')->get();
        foreach ($pending as $suggestions) {
            $grant = $grants->firstWhere('id', $suggestions->grant_id);
            $attention->push([
                'title' => $manuscripts->firstWhere('id', $grant->manuscript_id)->title,
                'detail' => $suggestions->pending_count.' open editorial suggestions',
                'url' => '/studio/reviews/'.$grant->id,
            ]);
        }

        return $attention;
    }

    private function recentActivity(Collection $manuscripts): Collection
    {
        $activity = ManuscriptRelease::whereIn('manuscript_id', $manuscripts->pluck('id'))
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'manuscript_id', 'number', 'created_at'])
            ->map(fn ($release) => [
                'title' => $manuscripts->firstWhere('id', $release->manuscript_id)->title,
                'detail' => 'Published release '.$release->number,
                'at' => $release->created_at,
                'url' => '/studio/manuscripts/'.$release->manuscript_id.'/publishing',
            ]);
        foreach ($manuscripts as $manuscript) {
            foreach ($manuscript->drafts->take(3) as $draft) {
                $activity->push([
                    'title' => $manuscript->title,
                    'detail' => 'Finished '.$draft->name,
                    'at' => $draft->created_at,
                    'url' => '/studio/manuscripts/'.$manuscript->id,
                ]);
            }
        }

        return $activity;
    }
}
