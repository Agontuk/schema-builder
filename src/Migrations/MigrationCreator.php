<?php

namespace Agontuk\Schema\Migrations;

use Illuminate\Database\Migrations\MigrationCreator as MigrationCreatorBase;
use Illuminate\Filesystem\Filesystem;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;

class MigrationCreator extends MigrationCreatorBase
{
    /**
     * @var Flysystem
     */
    private $flysystem;

    /**
     * MigrationCreator constructor.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
        $this->flysystem = new Flysystem(new ZipArchiveAdapter(storage_path('migrations.zip')));
    }

    /**
     * Get the full path name to the migration.
     *
     * @param  string $name
     * @param  string $path
     *
     * @return string
     */
    protected function getPath($name, $path = '')
    {
        if ($path) {
            return $path . '/' . $this->getDatePrefix() . '_create_' . $name . '_table' . '.php';
        }

        return $this->getDatePrefix() . '_create_' . $name . '_table' . '.php';
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function getStubPath()
    {
        return __DIR__.'/stubs';
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

            // Write the migration out to disk.
            $this->createMigration($table['name'], $columnData);
        }

        // All migrations pushed, close the archive.
        $this->flysystem->getAdapter()->getArchive()->close();
    }

    /**
     * Create a new migration at the given path.
     *
     * @param  string $name
     * @param  array  $columnData
     *
     * @return string
     * @throws \Exception
     */
    private function createMigration($name, $columnData)
    {
        $className = 'Create' . $this->getClassName($name) . 'Table';
        $this->ensureMigrationDoesntAlreadyExist($className);

        $path = $this->getPath($name);

        // First we will get the stub file for the migration, which serves as a type
        // of template for the migration. Once we have those we will populate the
        // various place-holders, save the file, and run the post create event.
        $table = $name;
        $create = true;
        $stub = $this->getStub($table, $create);

        $contents = $this->populateStubWithData($name, $stub, $table, $columnData);
        $this->flysystem->put($path, $contents);

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

            $str .= '$table->' . $type . '(\'' . $data['name'] . '\')';
        } else {
            $str .= '$table->' . $data['type'] . '(\'' . $data['name'];

            if (in_array($data['type'], $columnWithLength)) {
                if (!!$data['length']) {
                    // Add column length
                    $str .= ', ' . $data['length'];
                }
            }

            $str .= '\')';
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
            $str .= '->comment(\'' . $data['comment'] . '\')';
        }

        // End of statement
        $str .= ';';

        return $str;
    }
}