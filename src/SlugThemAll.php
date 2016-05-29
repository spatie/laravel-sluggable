<?php

namespace Spatie\Sluggable;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migrator;

class SlugThemAll extends Command
{
    /** @var string */
    protected $signature = 'slug-them:all';

    /** @var string */
    protected $description = "Slug'em all, them models";

    /** @var \App\MigrationCreator */
    protected $creator;

    /** @var \Illuminate\Database\Migrations\Migrator */
    protected $migrator;

    /** @var \Illuminate\Support\Composer */
    protected $composer;

    public function __construct(MigrationCreator $creator, Migrator $migrator, Composer $composer)
    {
        parent::__construct();
        $this->creator = $creator;
        $this->migrator = $migrator;
        $this->composer = $composer;
    }

    /**
     * @todo - add option for paths/namespaces (filename) to check for the models
     * @todo - add option for auto generating slugs for all the migrated tables
     */
    public function handle()
    {
        $migrations = $this->collectClasses()
                           ->map([$this, 'filterSluggable'])->filter()
                           ->map([$this, 'rejectExistingColumns'])->filter()
                           ->each(function ($migration) {
                               $this->line(
                                   "<info>Adding</info> {$migration['column']} "
                                  ."<info>column to</info> {$migration['table']} <info>table</info>"
                               );
                           });

        if ($migrations->isEmpty()) {
            $this->info('Nothing to migrate');
            return;
        }

        $file = $this->create($migrations);
        $this->line("<info>Migration created: </info>{$file}");

        $this->composer->dumpAutoloads();

        $this->migrator->runMigrationList([$file]);
        $this->line("<info>Migrated: </info>{$file}");
    }

    /**
     * Find all them classes in app/ & app/Models.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function collectClasses()
    {
        return collect(
            array_merge(glob(app_path('*.php')), glob(app_path('Models'.DIRECTORY_SEPARATOR.'*.php')))
        )->map(function ($file) {
            $file = str_replace([base_path(), '.php'], '', $file);
            return str_replace(
                'app\\',
                app()->getNamespace(),
                str_replace(DIRECTORY_SEPARATOR, '\\', $file)
            );
        });
    }

    /**
     * Filter only HasSlug trait holders.
     *
     * @param  string $class
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public  function filterSluggable($class)
    {
        try {
            $instance = new $class;
            if ($instance instanceof Model && in_array(HasSlug::class, class_uses($instance))) {
                return $instance;
            }
        } catch (Throwable $e) {
            //
        }
    }

    /**
     * Get SlugOptions and learn what the slug column name, aye?
     * Then do the all-fired job to check column's existence.
     *
     * @param  \Illuminate\Database\Eloquent\Model $sluggable
     * @return array|null
     */
    public function rejectExistingColumns($sluggable)
    {
        $table = $sluggable->getTable();
        $column = $sluggable->getSlugOptions()->slugField;
        $length = $sluggable->getSlugOptions()->maximumLength;
        $schema = $sluggable->getConnection()->getSchemaBuilder()->getColumnListing($table);

        if (!in_array($column, $schema)) {
            return compact('table', 'column', 'length');
        }
    }

    /**
     * Create the migration file.
     *
     * @param  \Illuminate\Support\Collection $columns
     * @return string
     */
    protected function create($columns)
    {
        $path = $this->creator->create(
            'add_slug_columns_'.time(),
            database_path(DIRECTORY_SEPARATOR.'migrations'),
            $columns
        );

        require($path);

        return pathinfo($path, PATHINFO_FILENAME);
    }
}
