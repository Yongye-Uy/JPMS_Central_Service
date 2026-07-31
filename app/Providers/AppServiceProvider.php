<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Author;
use App\Models\CoAuthorInvitation;
use App\Models\EditorAssignment;
use App\Models\EditorialDecision;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Manuscript;
use App\Models\ManuscriptFile;
use App\Models\ManuscriptVersion;
use App\Models\Review;
use App\Models\ReviewInvitation;
use App\Models\UserRole;
use App\Observers\AuditObserver;
use App\Observers\CacheInvalidationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Models that are both audit-logged (Mongo) and cache-invalidated (Redis) on every write. */
    private const AUDITABLE_MODELS = [
        Manuscript::class,
        ManuscriptVersion::class,
        Review::class,
        EditorialDecision::class,
        ReviewInvitation::class,
        Article::class,
        Issue::class,
        UserRole::class,
        Author::class,
        CoAuthorInvitation::class,
        EditorAssignment::class,
        ManuscriptFile::class,
        Journal::class,
        \App\Models\User::class,
    ];

    public function register(): void {}

    public function boot(): void
    {
        foreach (self::AUDITABLE_MODELS as $model) {
            $model::observe(AuditObserver::class);
            $model::observe(CacheInvalidationObserver::class);
        }
    }
}
