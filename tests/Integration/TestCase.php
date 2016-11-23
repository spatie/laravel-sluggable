<?php

namespace Edofre\Sluggable\Test\Integration;

use File;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @var \Edofre\Sluggable\Test\Integration\TestModel
     */
    protected $testModel;

    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
    }

    /**
     * @param  $app
     */
    protected function setUpDatabase(Application $app)
    {
        file_put_contents($this->getTempDirectory() . '/database.sqlite', null);

        $app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('other_field')->nullable();
            $table->string('url')->nullable();
        });
    }

    public function getTempDirectory()
    {
        return __DIR__ . '/temp';
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->initializeDirectory($this->getTempDirectory());

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => $this->getTempDirectory() . '/database.sqlite',
            'prefix'   => '',
        ]);
    }

    protected function initializeDirectory($directory)
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        File::makeDirectory($directory);
    }
}
