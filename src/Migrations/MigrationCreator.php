<?php

namespace Agontuk\Schema\Migrations;

use Illuminate\Database\Migrations\MigrationCreator as MigrationCreatorBase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

class MigrationCreator extends MigrationCreatorBase
{
    private $composer;

    /**
     * MigrationCreator constructor.
     *
     * @param Filesystem $files
     * @param Composer   $composer
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct($files);
        $this->composer = $composer;
    }

    /**
     * Get the full path name to the migration.
     *
     * @param  string $name
     * @param  string $path
     *
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path . '/' . $this->getDatePrefix() . '_create_' . $name . '_table' . '.php';
    }

    /**
     * Parse data and build migration files.
     *
     * @param array $tables
     * @param array $columns
     */
    public function parseAndBuildMigration($tables, $columns)
    {
        foreach ($tables as $table) {
            $tableColumns = $columns[$table['id']];
            $columnData = [];

            foreach ($tableColumns as $column) {
                $columnData[] = $this->buildColumnData($column);
            }

            if ($table['timeStamp']) {
                $columnData[] = '$table->timestamps();';
            }

            if ($table['softDelete']) {
                $columnData[] = '$table->softDeletes();';
            }

            $migrationPath = database_path() . DIRECTORY_SEPARATOR . 'migrations';

            // Write the migration out to disk.
            $this->createMigration($table['name'], $migrationPath, $columnData);

            // Make sure that the migrations are registered by the class loaders.
            $this->composer->dumpAutoloads();
        }
    }

    /**
     * Create a new migration at the given path.
     *
     * @param  string $name
     * @param  string $path
     * @param  array  $columnData
     *
     * @return string
     * @throws \Exception
     */
    private function createMigration($name, $path, $columnData)
    {
        $className = 'Create' . $this->getClassName($name) . 'Table';
        $this->ensureMigrationDoesntAlreadyExist($className);

        $path = $this->getPath($name, $path);

        // First we will get the stub file for the migration, which serves as a type
        // of template for the migration. Once we have those we will populate the
        // various place-holders, save the file, and run the post create event.
        $table = $name;
        $create = true;
        $stub = $this->getStub($table, $create);

        $this->files->put($path, $this->populateStubWithData($name, $stub, $table, $columnData));

        // $this->firePostCreateHooks();

        return $path;
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param  string $name
     * @param  string $stub
     * @param  string $table
     * @param  array  $columnData
     *
     * @return string
     */
    private function populateStubWithData($name, $stub, $table, $columnData)
    {
        $stub = str_replace('DummyClass', 'Create' . $this->getClassName($name) . 'Table', $stub);

        // We will replace the table place-holders with
        // the table specified by the developer.
        $stub = str_replace('DummyTable', $table, $stub);

        // We will replace the table place-holders with
        // the table specified by the developer.
        $stub = str_replace('return;', implode(PHP_EOL, $columnData), $stub);

        return $stub;
    }

    /**
     * Parse column data and generate command strings.
     *
     * @param  array $data
     *
     * @return string
     */
    private function buildColumnData($data)
    {
        $columnWithLength = ['char', 'string'];
        $str = '';

        if ($data['autoInc']) {
            // Change integer/bigInteger to increments/bigIncrements
            $type = str_replace('integer', 'increments', $data['type']);
            $type = str_replace('Integer', 'Increments', $type);

            $str .= '$table->' . $type . '(' . $data['name'] . ')';
        } else {
            $str .= '$table->' . $data['type'] . '(' . $data['name'];

            if (in_array($data['type'], $columnWithLength)) {
                if (!!$data['length']) {
                    // Add column length
                    $str .= ', ' . $data['length'];
                }
            }

            $str .= ')';
        }

        if (!!$data['defValue'] && !$data['autoInc']) {
            $str .= '->default(' . $data['defValue'] . ')';
        }

        if ($data['nullable']) {
            $str .= '->nullable()';
        }

        if ($data['unique']) {
            $str .= '->unique()';
        }

        if ($data['index']) {
            $str .= '->index()';
        }

        if ($data['unsigned'] && !$data['autoInc']) {
            $str .= '->unsigned()';
        }

        if (!!$data['comment']) {
            $str .= '->comment(' . $data['comment'] . ')';
        }

        // End of statement
        $str .= ';';

        return $str;
    }
}