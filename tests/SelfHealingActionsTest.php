<?php

use Spatie\Sluggable\Actions\BuildSelfHealingRouteKeyAction;
use Spatie\Sluggable\Actions\ExtractIdentifierFromSelfHealingRouteKeyAction;
use Spatie\Sluggable\Exceptions\InvalidConfig;
use Spatie\Sluggable\Support\Config;

it('builds a self-healing route key by joining slug and identifier with the separator', function () {
    $key = (new BuildSelfHealingRouteKeyAction())->execute('hello-world', 5, '-');

    expect($key)->toBe('hello-world-5');
});

it('builds a self-healing route key with a custom separator', function () {
    $key = (new BuildSelfHealingRouteKeyAction())->execute('hello-world', 5, '--');

    expect($key)->toBe('hello-world--5');
});

it('falls back to the identifier when the slug is empty', function () {
    $key = (new BuildSelfHealingRouteKeyAction())->execute('', 42, '-');

    expect($key)->toBe('42');
});

it('builds a self-healing route key for a string identifier', function () {
    $key = (new BuildSelfHealingRouteKeyAction())->execute('my-post', '550e8400', '-');

    expect($key)->toBe('my-post-550e8400');
});

it('extracts the identifier using the last occurrence of the separator', function () {
    $parts = (new ExtractIdentifierFromSelfHealingRouteKeyAction())->execute('hello-world-5', '-');

    expect($parts)->toBe(['slug' => 'hello-world', 'identifier' => '5']);
});

it('extracts the identifier with a multi-character separator', function () {
    $parts = (new ExtractIdentifierFromSelfHealingRouteKeyAction())->execute('hello-world--5', '--');

    expect($parts)->toBe(['slug' => 'hello-world', 'identifier' => '5']);
});

it('returns a null identifier when the separator is not present', function () {
    $parts = (new ExtractIdentifierFromSelfHealingRouteKeyAction())->execute('plainvalue', '-');

    expect($parts)->toBe(['slug' => 'plainvalue', 'identifier' => null]);
});

it('returns a null identifier when the value ends with the separator', function () {
    $parts = (new ExtractIdentifierFromSelfHealingRouteKeyAction())->execute('hello-world-', '-');

    expect($parts)->toBe(['slug' => 'hello-world-', 'identifier' => null]);
});

it('resolves the default action class through the Config helper', function () {
    $action = Config::getAction('build_self_healing_route_key', BuildSelfHealingRouteKeyAction::class);

    expect($action)->toBeInstanceOf(BuildSelfHealingRouteKeyAction::class);
});

it('throws when an action key is not configured', function () {
    config()->set('sluggable.actions.missing_key', null);

    Config::getAction('missing_key', BuildSelfHealingRouteKeyAction::class);
})->throws(InvalidConfig::class, 'No action class is configured for key `missing_key`');

it('throws when the configured action class does not exist', function () {
    config()->set('sluggable.actions.build_self_healing_route_key', 'App\\Actions\\DoesNotExist');

    Config::getAction('build_self_healing_route_key', BuildSelfHealingRouteKeyAction::class);
})->throws(InvalidConfig::class, 'does not exist');

it('throws when the configured action class is of the wrong type', function () {
    config()->set('sluggable.actions.build_self_healing_route_key', self::class);

    Config::getAction('build_self_healing_route_key', BuildSelfHealingRouteKeyAction::class);
})->throws(InvalidConfig::class, 'must be or extend');
