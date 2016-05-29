<?php

namespace Spatie\Sluggable;

class MigrationCreator extends \Illuminate\Database\Migrations\MigrationCreator
{
    /**
     * Get the migration stub file.
     *
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function getStub($t, $c)
    {
        return $this->files->get(__DIR__.'/migration.stub');
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param  string  $name
     * @param  string  $stub
     * @param  \Illuminate\Support\Collection|array  $migrations
     * @return string
     */
    protected function populateStub($name, $stub, $migrations)
    {
        $stub = str_replace('DummyClass', $this->getClassName($name), $stub);

        $up = collect($migrations)->map(function ($migration) {
            return <<<UP
        Schema::table('{$migration['table']}', function (Blueprint \$table) {
            \$table->string('{$migration['column']}', {$migration['length']})->nullable();
        });
UP;
        })->implode(PHP_EOL);

        $down = collect($migrations)->map(function ($migration) {
            return <<<DOWN
        Schema::table('{$migration['table']}', function (Blueprint \$table) {
            \$table->dropColumn('{$migration['column']}');
        });
DOWN;
        })->implode(PHP_EOL);

        return str_replace(['up_placeholder', 'down_placeholder'], [$up, $down], $stub);
    }
}
