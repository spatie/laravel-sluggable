<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Sluggable\Tests\TestSupport\SelfHealingModel;
use Spatie\Sluggable\Tests\TestSupport\SelfHealingModelSoftDeletes;
use Spatie\Sluggable\Tests\TestSupport\SelfHealingTranslatableModel;
use Spatie\Sluggable\Tests\TestSupport\SelfHealingUlidModel;
use Spatie\Sluggable\Tests\TestSupport\SelfHealingUuidModel;
use Spatie\Sluggable\Tests\TestSupport\TestModel;
use Spatie\Sluggable\Tests\TestSupport\TranslatableUlidModel;
use Spatie\Sluggable\Tests\TestSupport\TranslatableUuidModel;

it('resolves scoped keys automatically without escaping the parent relationship', function (string $modelClass, string $foreignKey) {
    $instance = new $modelClass;

    if (in_array($modelClass, [SelfHealingUuidModel::class, SelfHealingUlidModel::class, TranslatableUuidModel::class, TranslatableUlidModel::class])) {
        Schema::table($instance->getTable(), fn (Blueprint $table) => $table->unsignedInteger('parent_id'));
    }

    TestModel::resolveRelationUsing('children', fn (TestModel $parent) => $parent->hasMany($modelClass, $foreignKey));

    $parent = TestModel::create(['name' => 'Parent']);
    $otherParent = TestModel::create(['name' => 'Other Parent']);
    $child = $modelClass::create([
        'name' => in_array($modelClass, [SelfHealingTranslatableModel::class, TranslatableUuidModel::class, TranslatableUlidModel::class]) ? ['en' => 'Fresh Article'] : 'Fresh Article',
        $foreignKey => $parent->id,
    ]);

    $action = match ($modelClass) {
        SelfHealingModel::class => fn (TestModel $parent, SelfHealingModel $child) => (string) $child->id,
        SelfHealingTranslatableModel::class => fn (TestModel $parent, SelfHealingTranslatableModel $child) => (string) $child->id,
        SelfHealingUuidModel::class => fn (TestModel $parent, SelfHealingUuidModel $child) => (string) $child->id,
        SelfHealingUlidModel::class => fn (TestModel $parent, SelfHealingUlidModel $child) => (string) $child->id,
        TranslatableUuidModel::class => fn (TestModel $parent, TranslatableUuidModel $child) => (string) $child->id,
        TranslatableUlidModel::class => fn (TestModel $parent, TranslatableUlidModel $child) => (string) $child->id,
    };

    Route::get('parents/{parent}/children/{child}', $action)
        ->middleware(SubstituteBindings::class)
        ->scopeBindings();

    $separator = $child->getSlugOptions()->selfHealingSeparator;

    foreach ([$child->getRouteKey(), "stale-title{$separator}{$child->id}", $child->id] as $key) {
        $this->get("/parents/{$parent->id}/children/{$key}")->assertOk()->assertContent((string) $child->id);
        $this->get("/parents/{$otherParent->id}/children/{$key}")->assertNotFound();
    }

    $this->get('/parents/'.$parent->id.'/children/missing-999')->assertNotFound();
})->with([
    'integer' => [SelfHealingModel::class, 'other_field'],
    'translatable' => [SelfHealingTranslatableModel::class, 'test_model_id'],
    'uuid' => [SelfHealingUuidModel::class, 'parent_id'],
    'ulid' => [SelfHealingUlidModel::class, 'parent_id'],
    'translatable uuid' => [TranslatableUuidModel::class, 'parent_id'],
    'translatable ulid' => [TranslatableUlidModel::class, 'parent_id'],
]);

it('preserves query constraints and named non-route columns', function () {
    $model = SelfHealingModel::create(['name' => 'Fresh Article']);

    expect((new SelfHealingModel)->resolveRouteBindingQuery(SelfHealingModel::query(), $model->getRouteKey())->first()?->id)->toBe($model->id);
    expect((new SelfHealingModel)->resolveRouteBindingQuery(SelfHealingModel::where('name', 'Other Article'), $model->getRouteKey())->first())->toBeNull();

    Route::get('posts/{post:url}', fn (SelfHealingModel $post) => $post->name)
        ->middleware(SubstituteBindings::class);

    $this->get("/posts/{$model->url}")->assertOk()->assertContent('Fresh Article');
    $this->get("/posts/{$model->getRouteKey()}")->assertNotFound();
});

it('resolves qualified keys on many-to-many scoped bindings', function () {
    TestModel::resolveRelationUsing('children', fn (TestModel $parent) => $parent->belongsToMany(SelfHealingModel::class, 'category_project', 'category_id', 'project_id'));

    $parent = TestModel::create(['name' => 'Parent']);
    $otherParent = TestModel::create(['name' => 'Other Parent']);
    $child = SelfHealingModel::create(['name' => 'Fresh Article']);
    $parent->children()->attach($child);

    Route::get('parents/{parent}/children/{child}', fn (TestModel $parent, SelfHealingModel $child) => (string) $child->id)
        ->middleware(SubstituteBindings::class)
        ->scopeBindings();

    $this->get("/parents/{$parent->id}/children/{$child->getRouteKey()}")->assertOk()->assertContent((string) $child->id);
    $this->get("/parents/{$otherParent->id}/children/{$child->getRouteKey()}")->assertNotFound();
});

it('preserves scoped soft-delete filtering unless trashed bindings are allowed', function () {
    TestModel::resolveRelationUsing('children', fn (TestModel $parent) => $parent->hasMany(SelfHealingModelSoftDeletes::class, 'other_field'));

    $parent = TestModel::create(['name' => 'Parent']);
    $otherParent = TestModel::create(['name' => 'Other Parent']);
    $child = SelfHealingModelSoftDeletes::create(['name' => 'Fresh Article', 'other_field' => $parent->id]);
    $child->delete();

    foreach (['posts', 'trashed-posts'] as $path) {
        $route = Route::get("{$path}/{parent}/children/{child}", fn (TestModel $parent, SelfHealingModelSoftDeletes $child) => (string) $child->id)
            ->middleware(SubstituteBindings::class)
            ->scopeBindings();

        if ($path === 'trashed-posts') {
            $route->withTrashed();
        }
    }

    $this->get("/posts/{$parent->id}/children/{$child->getRouteKey()}")->assertNotFound();
    $this->get("/trashed-posts/{$parent->id}/children/{$child->getRouteKey()}")->assertOk()->assertContent((string) $child->id);
    $this->get("/trashed-posts/{$otherParent->id}/children/{$child->getRouteKey()}")->assertNotFound();
});

it('keeps named-column bindings and ordinary redirects on combined identifier traits', function (string $modelClass) {
    $model = $modelClass::create([
        'name' => in_array($modelClass, [TranslatableUuidModel::class, TranslatableUlidModel::class]) ? ['en' => 'Fresh Article'] : 'Fresh Article',
    ]);

    $action = match ($modelClass) {
        SelfHealingUuidModel::class => fn (SelfHealingUuidModel $post) => (string) $post->id,
        SelfHealingUlidModel::class => fn (SelfHealingUlidModel $post) => (string) $post->id,
        TranslatableUuidModel::class => fn (TranslatableUuidModel $post) => (string) $post->id,
        TranslatableUlidModel::class => fn (TranslatableUlidModel $post) => (string) $post->id,
    };

    Route::get('posts/{post}', $action)->middleware(SubstituteBindings::class);

    foreach (['id', 'url'] as $field) {
        Route::get("{$field}/{post:{$field}}", $action)->middleware(SubstituteBindings::class);

        $this->get("/{$field}/{$model->{$field}}")->assertOk()->assertContent((string) $model->id);
        $this->get("/{$field}/{$model->getRouteKey()}")->assertNotFound();
        $this->get("/{$field}/invalid")->assertNotFound();
    }

    $this->get("/posts/{$model->getRouteKey()}")->assertOk()->assertContent((string) $model->id);
    $this->get("/posts/stale-title--{$model->id}")->assertStatus(308)->assertRedirect("/posts/{$model->getRouteKey()}");
})->with([SelfHealingUuidModel::class, SelfHealingUlidModel::class, TranslatableUuidModel::class, TranslatableUlidModel::class]);

it('preserves custom query restrictions on named-column bindings including trashed models', function (string $field) {
    $published = RestrictedSelfHealingModel::create(['name' => 'Published Article', 'other_field' => 'published']);
    $draft = RestrictedSelfHealingModel::create(['name' => 'Draft Article', 'other_field' => 'draft']);

    Route::get("posts/{post:{$field}}", fn (RestrictedSelfHealingModel $post) => $post->name)
        ->middleware(SubstituteBindings::class);
    Route::get("trashed-posts/{post:{$field}}", fn (RestrictedSelfHealingModel $post) => $post->name)
        ->middleware(SubstituteBindings::class)
        ->withTrashed();

    $this->get("/posts/{$published->{$field}}")->assertOk()->assertContent('Published Article');
    $this->get("/posts/{$draft->{$field}}")->assertNotFound();
    $this->get("/posts/{$published->getRouteKey()}")->assertNotFound();

    $published->delete();
    $draft->delete();

    $this->get("/posts/{$published->{$field}}")->assertNotFound();
    $this->get("/trashed-posts/{$published->{$field}}")->assertOk()->assertContent('Published Article');
    $this->get("/trashed-posts/{$draft->{$field}}")->assertNotFound();
    $this->get("/trashed-posts/{$published->getRouteKey()}")->assertNotFound();
})->with(['id', 'url']);

it('rejects malformed unique identifiers in ordinary and scoped self-healing keys', function (string $modelClass) {
    $instance = new $modelClass;
    Schema::table($instance->getTable(), fn (Blueprint $table) => $table->unsignedInteger('parent_id'));

    TestModel::resolveRelationUsing('children', fn (TestModel $parent) => $parent->hasMany($modelClass, 'parent_id'));

    $parent = TestModel::create(['name' => 'Parent']);
    $name = in_array($modelClass, [TranslatableUuidModel::class, TranslatableUlidModel::class]) ? ['en' => 'Fresh Article'] : 'Fresh Article';
    $valid = $modelClass::create(['name' => $name, 'parent_id' => $parent->id]);
    $invalid = $modelClass::create(['id' => 'not-a-valid-identifier', 'name' => $name, 'parent_id' => $parent->id]);

    $action = match ($modelClass) {
        SelfHealingUuidModel::class => fn (SelfHealingUuidModel $child) => (string) $child->id,
        SelfHealingUlidModel::class => fn (SelfHealingUlidModel $child) => (string) $child->id,
        TranslatableUuidModel::class => fn (TranslatableUuidModel $child) => (string) $child->id,
        TranslatableUlidModel::class => fn (TranslatableUlidModel $child) => (string) $child->id,
    };

    $scopedAction = match ($modelClass) {
        SelfHealingUuidModel::class => fn (TestModel $parent, SelfHealingUuidModel $child) => (string) $child->id,
        SelfHealingUlidModel::class => fn (TestModel $parent, SelfHealingUlidModel $child) => (string) $child->id,
        TranslatableUuidModel::class => fn (TestModel $parent, TranslatableUuidModel $child) => (string) $child->id,
        TranslatableUlidModel::class => fn (TestModel $parent, TranslatableUlidModel $child) => (string) $child->id,
    };

    Route::get('posts/{child}', $action)->middleware(SubstituteBindings::class);
    Route::get('parents/{parent}/children/{child}', $scopedAction)
        ->middleware(SubstituteBindings::class)
        ->scopeBindings();

    $this->get("/posts/{$valid->getRouteKey()}")->assertOk()->assertContent((string) $valid->id);
    $this->get("/parents/{$parent->id}/children/{$valid->getRouteKey()}")->assertOk()->assertContent((string) $valid->id);
    $this->get("/posts/{$invalid->getRouteKey()}")->assertNotFound();
    $this->get("/parents/{$parent->id}/children/{$invalid->getRouteKey()}")->assertNotFound();
})->with([SelfHealingUuidModel::class, SelfHealingUlidModel::class, TranslatableUuidModel::class, TranslatableUlidModel::class]);

class RestrictedSelfHealingModel extends SelfHealingModelSoftDeletes
{
    public function resolveRouteBindingQuery($query, $value, $field = null): Builder|Relation
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)->where('other_field', 'published');
    }
}
