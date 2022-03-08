<?php

namespace Spatie\Sluggable\Tests;

use File;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->initializeDirectory($this->getTempDirectory());

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $this->getTempDirectory() . '/database.sqlite',
            'prefix' => '',
        ]);
    }

    /**
     * @param Application $app
     */
    protected function setUpDatabase(Application $app)
    {
        file_put_contents($this->getTempDirectory() . '/database.sqlite', null);

        Schema::create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('other_field')->nullable();
            $table->string('url')->nullable();
        });

        Schema::create('test_model_soft_deletes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('other_field')->nullable();
            $table->string('url')->nullable();
            $table->softDeletes();
        });

        Schema::create('translatable_models', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name')->nullable();
            $table->text('other_field')->nullable();
            $table->text('non_translatable_field')->nullable();
            $table->text('slug')->nullable();
            $table->foreignId('test_model_id')->nullable()->index();
        });

        Schema::create('translatable_model_soft_deletes', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name')->nullable();
            $table->text('other_field')->nullable();
            $table->text('non_translatable_field')->nullable();
            $table->text('slug')->nullable();
            $table->softDeletes();
        });

        Schema::create('scopeable_models', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name')->nullable();
            $table->text('slug')->nullable();
            $table->unsignedInteger('scope_id')->nullable();
        });
    }

    protected function initializeDirectory(string $directory)
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        File::makeDirectory($directory);
    }

    protected function getTempDirectory(): string
    {
        return __DIR__ . '/temp';
    }
}
