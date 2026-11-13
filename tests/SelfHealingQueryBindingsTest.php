<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Sluggable\Tests\TestSupport\QueryBindingModel;
use Spatie\Sluggable\Tests\TestSupport\QueryBindingModelSoftDeletes;
use Spatie\Sluggable\Tests\TestSupport\QueryBindingTranslatableModel;
use Spatie\Sluggable\Tests\TestSupport\QueryBindingUlidModel;
use Spatie\Sluggable\Tests\TestSupport\QueryBindingUuidModel;
use Spatie\Sluggable\Tests\TestSupport\TestModel;

it('resolves opted-in scoped keys without escaping the parent relationship', function (string $modelClass, string $foreignKey) {
    $instance = new $modelClass;

    if (in_array($modelClass, [QueryBindingUuidModel::class, QueryBindingUlidModel::class])) {
        Schema::table($instance->getTable(), fn (Blueprint $table) => $table->unsignedInteger('parent_id'));
    }

    TestModel::resolveRelationUsing('children', fn (TestModel $parent) => $parent->hasMany($modelClass, $foreignKey));

    $parent = TestModel::create(['name' => 'Parent']);
    $otherParent = TestModel::create(['name' => 'Other Parent']);
    $child = $modelClass::create([
        'name' => $modelClass === QueryBindingTranslatableModel::class ? ['en' => 'Fresh Article'] : 'Fresh Article',
        $foreignKey => $parent->id,
    ]);

    $action = match ($modelClass) {
        QueryBindingModel::class => fn (TestModel $parent, QueryBindingModel $child) => (string) $child->id,
        QueryBindingTranslatableModel::class => fn (TestModel $parent, QueryBindingTranslatableModel $child) => (string) $child->id,
        QueryBindingUuidModel::class => fn (TestModel $parent, QueryBindingUuidModel $child) => (string) $child->id,
        QueryBindingUlidModel::class => fn (TestModel $parent, QueryBindingUlidModel $child) => (string) $child->id,
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
    'integer' => [QueryBindingModel::class, 'other_field'],
    'translatable' => [QueryBindingTranslatableModel::class, 'test_model_id'],
    'uuid' => [QueryBindingUuidModel::class, 'parent_id'],
    'ulid' => [QueryBindingUlidModel::class, 'parent_id'],
]);

it('preserves query constraints and named non-route columns', function () {
    $model = QueryBindingModel::create(['name' => 'Fresh Article']);

    expect((new QueryBindingModel)->resolveRouteBindingQuery(QueryBindingModel::query(), $model->getRouteKey())->first()?->id)->toBe($model->id);
    expect((new QueryBindingModel)->resolveRouteBindingQuery(QueryBindingModel::where('name', 'Other Article'), $model->getRouteKey())->first())->toBeNull();

    Route::get('posts/{post:url}', fn (QueryBindingModel $post) => $post->name)
        ->middleware(SubstituteBindings::class);

    $this->get("/posts/{$model->url}")->assertOk()->assertContent('Fresh Article');
    $this->get("/posts/{$model->getRouteKey()}")->assertNotFound();
});

it('resolves qualified keys on many-to-many scoped bindings', function () {
    TestModel::resolveRelationUsing('children', fn (TestModel $parent) => $parent->belongsToMany(QueryBindingModel::class, 'category_project', 'category_id', 'project_id'));

    $parent = TestModel::create(['name' => 'Parent']);
    $otherParent = TestModel::create(['name' => 'Other Parent']);
    $child = QueryBindingModel::create(['name' => 'Fresh Article']);
    $parent->children()->attach($child);

    Route::get('parents/{parent}/children/{child}', fn (TestModel $parent, QueryBindingModel $child) => (string) $child->id)
        ->middleware(SubstituteBindings::class)
        ->scopeBindings();

    $this->get("/parents/{$parent->id}/children/{$child->getRouteKey()}")->assertOk()->assertContent((string) $child->id);
    $this->get("/parents/{$otherParent->id}/children/{$child->getRouteKey()}")->assertNotFound();
});

it('preserves scoped soft-delete filtering unless trashed bindings are allowed', function () {
    TestModel::resolveRelationUsing('children', fn (TestModel $parent) => $parent->hasMany(QueryBindingModelSoftDeletes::class, 'other_field'));

    $parent = TestModel::create(['name' => 'Parent']);
    $otherParent = TestModel::create(['name' => 'Other Parent']);
    $child = QueryBindingModelSoftDeletes::create(['name' => 'Fresh Article', 'other_field' => $parent->id]);
    $child->delete();

    foreach (['posts', 'trashed-posts'] as $path) {
        $route = Route::get("{$path}/{parent}/children/{child}", fn (TestModel $parent, QueryBindingModelSoftDeletes $child) => (string) $child->id)
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
