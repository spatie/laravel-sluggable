<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Spatie\Sluggable\Attributes\Sluggable;
use Spatie\Sluggable\Exceptions\SelfHealingRequiresTrait;
use Spatie\Sluggable\Support\SluggableAttributeResolver;
use Spatie\Sluggable\Tests\TestSupport\AttributeModel;
use Spatie\Sluggable\Tests\TestSupport\AttributeModelCustomSeparator;
use Spatie\Sluggable\Tests\TestSupport\AttributeModelLanguage;
use Spatie\Sluggable\Tests\TestSupport\AttributeModelMaxLength;
use Spatie\Sluggable\Tests\TestSupport\AttributeModelMultipleFields;
use Spatie\Sluggable\Tests\TestSupport\AttributeModelNoCreate;
use Spatie\Sluggable\Tests\TestSupport\AttributeModelNotUnique;
use Spatie\Sluggable\Tests\TestSupport\AttributeModelNoUpdate;
use Spatie\Sluggable\Tests\TestSupport\AttributeModelPreventOverwrite;
use Spatie\Sluggable\Tests\TestSupport\AttributeModelSelfHealingCustomSeparator;
use Spatie\Sluggable\Tests\TestSupport\AttributeModelWithTraitSelfHealing;
use Spatie\Sluggable\Tests\TestSupport\TestModel;

afterEach(function () {
    SluggableAttributeResolver::flushCache();
});

it('generates a slug on create for a model using only the Sluggable attribute', function () {
    $model = AttributeModel::create(['name' => 'Hello World']);

    expect($model->url)->toBe('hello-world');
});

it('regenerates the slug when the source field changes on update', function () {
    $model = AttributeModel::create(['name' => 'Hello World']);
    $model->name = 'Different Title';
    $model->save();

    expect($model->url)->toBe('different-title');
});

it('makes slugs unique by default when the attribute is used', function () {
    AttributeModel::create(['name' => 'Hello World']);
    $second = AttributeModel::create(['name' => 'Hello World']);

    expect($second->url)->toBe('hello-world-1');
});

it('allows duplicates when the unique flag is off', function () {
    AttributeModelNotUnique::create(['name' => 'Hello World']);
    $second = AttributeModelNotUnique::create(['name' => 'Hello World']);

    expect($second->url)->toBe('hello-world');
});

it('can build a slug from multiple fields via the attribute', function () {
    $model = AttributeModelMultipleFields::create([
        'name' => 'Hello',
        'other_field' => 'World',
    ]);

    expect($model->url)->toBe('hello-world');
});

it('respects preventOverwrite when set on the attribute', function () {
    $model = AttributeModelPreventOverwrite::create([
        'name' => 'Will Not Override',
        'url' => 'manual-slug',
    ]);

    expect($model->url)->toBe('manual-slug');
});

it('does not generate a slug on create when onCreate is false', function () {
    $model = AttributeModelNoCreate::create(['name' => 'Hello World']);

    expect($model->url)->toBeNull();
});

it('still generates a slug on update when onCreate is false', function () {
    $model = AttributeModelNoCreate::create(['name' => 'Hello World']);
    $model->name = 'Different Title';
    $model->save();

    expect($model->url)->toBe('different-title');
});

it('resolves an attribute-only model through Laravel implicit route binding on the slug field', function () {
    $model = AttributeModel::create(['name' => 'Hello World']);

    Route::get('/posts/{post:url}', fn (AttributeModel $post) => $post->name)
        ->middleware(SubstituteBindings::class);

    $response = $this->get("/posts/{$model->url}");

    $response->assertStatus(200);
    $response->assertSee('Hello World');
});

it('uses a custom separator when configured on the attribute', function () {
    $model = AttributeModelCustomSeparator::create(['name' => 'Hello World']);

    expect($model->url)->toBe('hello_world');
});

it('uses the configured language when transliterating non-ASCII characters', function () {
    $model = AttributeModelLanguage::create(['name' => 'Größe']);

    expect($model->url)->toBe('groesse');
});

it('truncates the slug to the configured maxLength', function () {
    $model = AttributeModelMaxLength::create(['name' => 'Hello World']);

    expect($model->url)->toBe('hello');
});

it('does not regenerate the slug on update when onUpdate is false', function () {
    $model = AttributeModelNoUpdate::create(['name' => 'Hello World']);

    expect($model->url)->toBe('hello-world');

    $model->name = 'Different Title';
    $model->save();

    expect($model->fresh()->url)->toBe('hello-world');
});

it('honours the selfHealingSeparator argument on the attribute', function () {
    $model = AttributeModelSelfHealingCustomSeparator::create(['name' => 'Hello World']);

    expect($model->getRouteKey())->toBe("hello-world--{$model->id}");
});

it('does not run the attribute listener for models that also use the HasSlug trait', function () {
    $model = TestModel::create(['name' => 'Trait Only']);

    expect($model->url)->toBe('trait-only');
});

it('combines the attribute and the HasSlug trait for self-healing URLs', function () {
    $model = AttributeModelWithTraitSelfHealing::create(['name' => 'Fresh Title']);

    expect($model->getRouteKey())->toBe("fresh-title-{$model->id}");
});

it('caches attribute resolution per class', function () {
    $first = SluggableAttributeResolver::resolve(AttributeModel::class);
    $second = SluggableAttributeResolver::resolve(AttributeModel::class);

    expect($first)->toBe($second);
});

it('returns null when the model has no Sluggable attribute', function () {
    expect(SluggableAttributeResolver::resolve(TestModel::class))->toBeNull();
});

it('throws when selfHealing is set on the attribute but the trait is missing', function () {
    $class = new #[Sluggable(from: 'name', selfHealing: true)] class extends Model {};

    SluggableAttributeResolver::resolve($class::class);
})->throws(SelfHealingRequiresTrait::class);
