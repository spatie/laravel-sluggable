<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction;
use Spatie\Sluggable\Exceptions\StaleSelfHealingUrl;
use Spatie\Sluggable\Facades\SelfHealing;
use Spatie\Sluggable\Tests\TestSupport\SelfHealingModel;
use Spatie\Sluggable\Tests\TestSupport\SelfHealingModelSoftDeletes;
use Spatie\Sluggable\Tests\TestSupport\SelfHealingTranslatableModel;
use Spatie\Sluggable\Tests\TestSupport\TestModel;

afterEach(function () {
    SelfHealingModel::$separator = '-';
});

it('returns the slug-and-identifier combination as the route key', function () {
    $model = SelfHealingModel::create(['name' => 'Hello World']);

    expect($model->getRouteKey())->toBe("hello-world-{$model->id}");
});

it('falls back to the parent route key when self-healing is disabled', function () {
    $model = TestModel::create(['name' => 'Hello World']);

    expect($model->getRouteKey())->toBe($model->getKey());
});

it('resolves a model by identifier when the slug matches the canonical value', function () {
    $model = SelfHealingModel::create(['name' => 'Hello World']);

    $resolved = (new SelfHealingModel)->resolveRouteBinding($model->getRouteKey());

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($model->id);
});

it('returns null when the identifier cannot be found', function () {
    expect((new SelfHealingModel)->resolveRouteBinding('missing-999'))->toBeNull();
});

it('returns null when the value contains no identifier separator', function () {
    expect((new SelfHealingModel)->resolveRouteBinding('noseparator'))->toBeNull();
});

it('throws a StaleSelfHealingUrl exception when the slug is stale', function () {
    $model = SelfHealingModel::create(['name' => 'Fresh Title']);

    $staleKey = "old-title-{$model->id}";

    try {
        (new SelfHealingModel)->resolveRouteBinding($staleKey);
    } catch (StaleSelfHealingUrl $exception) {
        expect($exception->model->id)->toBe($model->id);
        expect($exception->staleRouteKey)->toBe($staleKey);

        return;
    }

    $this->fail('Expected StaleSelfHealingUrl to be thrown.');
});

it('respects a custom separator', function () {
    SelfHealingModel::$separator = '--';

    $model = SelfHealingModel::create(['name' => 'Hello World']);

    expect($model->getRouteKey())->toBe("hello-world--{$model->id}");

    $resolved = (new SelfHealingModel)->resolveRouteBinding($model->getRouteKey());

    expect($resolved->id)->toBe($model->id);
});

it('redirects with a 308 when an implicit route binding encounters a stale slug', function () {
    $model = SelfHealingModel::create(['name' => 'Fresh Title']);

    Route::get('/posts/{post}', fn (SelfHealingModel $post) => $post->name)
        ->middleware(SubstituteBindings::class);

    $response = $this->get("/posts/old-slug-{$model->id}");

    $response->assertStatus(308);
    $response->assertRedirect("/posts/fresh-title-{$model->id}");
});

it('responds to a POST with a 308 status', function () {
    $model = SelfHealingModel::create(['name' => 'Fresh Title']);

    Route::post('/posts/{post}', fn (SelfHealingModel $post) => $post->name)
        ->middleware(SubstituteBindings::class);

    $response = $this->post("/posts/old-slug-{$model->id}");

    $response->assertStatus(308);
    $response->assertRedirect("/posts/fresh-title-{$model->id}");
});

it('redirects to the canonical URL when the stale slug contains URL-encoded characters', function () {
    $model = SelfHealingModel::create(['name' => 'Fresh Title']);

    Route::get('/posts/{post}', fn (SelfHealingModel $post) => $post->name)
        ->middleware(SubstituteBindings::class);

    $staleSlug = 'héllo';
    $response = $this->get('/posts/'.rawurlencode("{$staleSlug}-{$model->id}"));

    $response->assertStatus(308);
    $response->assertRedirect("/posts/fresh-title-{$model->id}");
});

it('responds with 200 when the URL is already canonical', function () {
    $model = SelfHealingModel::create(['name' => 'Fresh Title']);

    Route::get('/posts/{post}', fn (SelfHealingModel $post) => $post->name)
        ->middleware(SubstituteBindings::class);

    $response = $this->get("/posts/fresh-title-{$model->id}");

    $response->assertStatus(200);
    $response->assertSee('Fresh Title');
});

it('invokes a custom handler registered through the SelfHealing facade', function () {
    $model = SelfHealingModel::create(['name' => 'Fresh Title']);

    SelfHealing::onStaleSelfHealingUrl(function (Model $model, string $staleRouteKey, Request $request) {
        return response("stale:{$staleRouteKey}:canonical:{$model->getRouteKey()}", 418);
    });

    Route::get('/posts/{post}', fn (SelfHealingModel $post) => $post->name)
        ->middleware(SubstituteBindings::class);

    $response = $this->get("/posts/old-slug-{$model->id}");

    $response->assertStatus(418);
    $response->assertSee("stale:old-slug-{$model->id}:canonical:fresh-title-{$model->id}");
});

it('uses a custom action class registered in config', function () {
    config()->set(
        'sluggable.actions.build_self_healing_route_key',
        UppercaseSlugRouteKeyAction::class,
    );

    $model = SelfHealingModel::create(['name' => 'Hello World']);

    expect($model->getRouteKey())->toBe("HELLO-WORLD-{$model->id}");
});

it('builds a self-healing route key per locale on translatable models', function () {
    $model = new SelfHealingTranslatableModel;
    $model->setTranslation('name', 'en', 'English Title');
    $model->setTranslation('name', 'nl', 'Nederlandse Titel');
    $model->save();

    $model->setLocale('en');
    expect($model->getRouteKey())->toBe("english-title-{$model->id}");

    $model->setLocale('nl');
    expect($model->getRouteKey())->toBe("nederlandse-titel-{$model->id}");
});

it('redirects stale translatable URLs to the current locale canonical URL', function () {
    $model = new SelfHealingTranslatableModel;
    $model->setTranslation('name', 'en', 'Fresh English');
    $model->save();

    Route::get('/posts/{post}', fn (SelfHealingTranslatableModel $post) => $post->getTranslation('name', 'en'))
        ->middleware(SubstituteBindings::class);

    $response = $this->get("/posts/stale-english-{$model->id}");

    $response->assertStatus(308);
    $response->assertRedirect("/posts/fresh-english-{$model->id}");
});

it('resolves a self-healing route key on a route that allows trashed models', function () {
    $model = SelfHealingModelSoftDeletes::create(['name' => 'Hello World']);
    $model->delete();

    Route::get('/posts/{post}', fn (SelfHealingModelSoftDeletes $post) => $post->name)
        ->middleware(SubstituteBindings::class)
        ->withTrashed();

    $this->get("/posts/{$model->getRouteKey()}")->assertOk()->assertSee('Hello World');
});

it('does not resolve a trashed model when the route does not allow trashed models', function () {
    $model = SelfHealingModelSoftDeletes::create(['name' => 'Hello World']);
    $model->delete();

    Route::get('/posts/{post}', fn (SelfHealingModelSoftDeletes $post) => $post->name)
        ->middleware(SubstituteBindings::class);

    $this->get("/posts/{$model->getRouteKey()}")->assertNotFound();
});

class UppercaseSlugRouteKeyAction extends BuildSelfHealingRouteKeyAction
{
    public function execute(string $slug, int|string $identifier, string $separator): string
    {
        return parent::execute(strtoupper($slug), $identifier, $separator);
    }
}

it('resolves current and stale keys through the route binding query', function (?string $field, string $separator) {
    SelfHealingModel::$separator = $separator;

    $model = SelfHealingModel::create(['name' => 'Hello World']);
    $routeKey = $model->getRouteKey();

    $resolved = (new SelfHealingModel)
        ->resolveRouteBindingQuery(SelfHealingModel::query(), $routeKey, $field)
        ->first();

    expect($resolved?->id)->toBe($model->id);

    $model->update(['name' => 'Changed Title']);

    $resolved = (new SelfHealingModel)
        ->resolveRouteBindingQuery(SelfHealingModel::query(), $routeKey, $field)
        ->first();

    expect($resolved?->name)->toBe('Changed Title');
})->with([null, 'id', 'test_models.id'])->with(['-', '--']);

it('resolves a self-healing route key through a scoped translatable route binding query', function () {
    $parent = TestModel::create(['name' => 'Parent']);
    $otherParent = TestModel::create(['name' => 'Other Parent']);

    $model = new SelfHealingTranslatableModel;
    $model->setTranslation('name', 'en', 'Hello World');
    $model->setTranslation('name', 'nl', 'Hallo Wereld');
    $model->test_model_id = $parent->id;
    $model->save();

    foreach (['en', 'nl'] as $locale) {
        $routeKey = $model->getLocalizedRouteKey($locale);

        $resolved = (new SelfHealingTranslatableModel)
            ->resolveRouteBindingQuery($parent->hasMany(SelfHealingTranslatableModel::class, 'test_model_id'), $routeKey, 'id')
            ->first();

        expect($resolved?->id)->toBe($model->id);

        $resolved = (new SelfHealingTranslatableModel)
            ->resolveRouteBindingQuery($otherParent->hasMany(SelfHealingTranslatableModel::class, 'test_model_id'), $routeKey, 'id')
            ->first();

        expect($resolved)->toBeNull();
    }
});

it('keeps the default route binding query for plain keys and other fields', function () {
    $decoy = SelfHealingModel::create(['name' => 'Decoy']);
    $model = SelfHealingModel::create(['name' => 'Hello World', 'other_field' => "reference-{$decoy->id}"]);

    expect((new SelfHealingModel)->resolveRouteBindingQuery(SelfHealingModel::query(), (string) $model->id)->first()?->id)->toBe($model->id);
    expect((new SelfHealingModel)->resolveRouteBindingQuery(SelfHealingModel::query(), 'hello-world', 'url')->first()?->id)->toBe($model->id);
    expect((new SelfHealingModel)->resolveRouteBindingQuery(SelfHealingModel::query(), "reference-{$decoy->id}", 'other_field')->first()?->id)->toBe($model->id);
    expect((new SelfHealingModel)->resolveRouteBindingQuery(SelfHealingModel::query(), "reference-{$decoy->id}", 'test_models.other_field')->first()?->id)->toBe($model->id);

    foreach (['noseparator', 'trailing-', 'missing-999'] as $routeKey) {
        expect((new SelfHealingModel)->resolveRouteBindingQuery(SelfHealingModel::query(), $routeKey)->first())->toBeNull();
    }
});

it('uses the primary key when the model has a custom route key field', function (?string $field) {
    $model = new class extends SelfHealingModel
    {
        public function getRouteKeyName(): string
        {
            return 'url';
        }
    };
    $model->fill(['name' => 'Hello World'])->save();

    $resolved = $model->resolveRouteBindingQuery($model->newQuery(), $model->getRouteKey(), $field)->first();

    expect($resolved?->id)->toBe($model->id);
    expect($model->resolveRouteBindingQuery($model->newQuery(), 'hello-world', 'id')->first())->toBeNull();
})->with([null, 'url', 'test_models.url']);

it('preserves query constraints and soft delete scopes in route binding queries', function () {
    $model = SelfHealingModelSoftDeletes::create(['name' => 'Hello World']);
    $routeKey = $model->getRouteKey();

    $resolve = fn ($query) => (new SelfHealingModelSoftDeletes)->resolveRouteBindingQuery($query, $routeKey)->first();

    expect($resolve(SelfHealingModelSoftDeletes::query())?->id)->toBe($model->id);
    expect($resolve(SelfHealingModelSoftDeletes::where('name', 'Another Title')))->toBeNull();

    $model->delete();

    expect($resolve(SelfHealingModelSoftDeletes::query()))->toBeNull();
    expect($resolve(SelfHealingModelSoftDeletes::withTrashed())?->id)->toBe($model->id);
});

it('resolves scoped child routes without allowing access through another parent', function () {
    TestModel::resolveRelationUsing('selfHealingPosts', fn (TestModel $parent) => $parent->hasMany(SelfHealingTranslatableModel::class, 'test_model_id'));

    $parent = TestModel::create(['name' => 'Parent']);
    $otherParent = TestModel::create(['name' => 'Other Parent']);
    $model = SelfHealingTranslatableModel::create([
        'name' => ['en' => 'Hello World'],
        'test_model_id' => $parent->id,
    ]);

    Route::get('/parents/{parent}/posts/{selfHealingPost}', fn (TestModel $parent, SelfHealingTranslatableModel $selfHealingPost) => (string) $selfHealingPost->id)
        ->middleware(SubstituteBindings::class)
        ->scopeBindings();

    $this->get("/parents/{$parent->id}/posts/{$model->getRouteKey()}")->assertOk()->assertContent((string) $model->id);
    $this->get("/parents/{$otherParent->id}/posts/{$model->getRouteKey()}")->assertNotFound();
    $this->get("/parents/{$parent->id}/posts/stale-title-{$model->id}")->assertOk()->assertContent((string) $model->id);
});
