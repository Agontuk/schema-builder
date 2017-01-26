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
     * @var array
     */
    private $foreignKeyData = [];

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
     * Get foreign key data for all tables.
     *
     * @return array
     */
    public function getForeignKeyData()
    {
        return $this->foreignKeyData;
    }

    /**
     * Set foreign key data.
     *
     * @param  array $data
     *
     * @return $this
     */
    public function setForeignKeyData(array $data)
    {
        $this->foreignKeyData = array_merge_recursive($this->foreignKeyData, $data);

        return $this;
    }

    /**
     * Parse data and build migration files.
     *
     * @param array $schema
     */
    public function parseAndBuildMigration($schema)
    {
        $tables = $schema['tables'];
        $columns = $schema['columns'];

        foreach ($tables as $table) {
            $tableColumns = $columns[$table['id']];
            $columnData = [];

            foreach ($tableColumns as $column) {
                $columnData[] = str_repeat(' ', 12) . $this->buildColumnData($column);

                // Handle foreign key relations
                if (!!$column['foreignKey']['references']['id']) {
                    $this->parseForeignKeyData($table['name'], $column);
                }
            }

            if ($table['timeStamp']) {
                $columnData[] = str_repeat(' ', 12) . '$table->timestamps();';
            }

            if ($table['softDelete']) {
                $columnData[] = str_repeat(' ', 12) . '$table->softDeletes();';
            }

            // Write the migration out to disk.
            $this->createMigration($table['name'], $columnData);
        }

        if (count($foreignKeyData = $this->getForeignKeyData())) {
            // Write foreign key migration out to disk.
            $data = $this->buildForeignKeyData($foreignKeyData);
            $this->createForeignKeyMigration($data['up'], $data['down']);
        }

        // Write the schema into a json file.
        $this->flysystem->put('schema.json', json_encode($schema, JSON_PRETTY_PRINT));

        // All migrations pushed, close the archive.
        $this->flysystem->getAdapter()->getArchive()->close();
    }

    /**
     * Create a new migration & push it to the zip archive.
     *
     * @param  string $name
     * @param  array  $columnData
     *
     * @return string
     */
    private function createMigration($name, $columnData)
    {
        $path = $this->getDatePrefix() . '_create_' . $name . '_table' . '.php';

        // First we will get the stub file for the migration, which serves as a type
        // of template for the migration. Once we have those we will populate the
        // various place-holders, save the file, and push it to zip archive.
        $stub = $this->files->get(__DIR__ . '/stubs/create.stub');

        $contents = $this->populateStubWithData($name, $stub, $columnData);
        $this->flysystem->put($path, $contents);

        return $path;
    }

    /**
     * Create a new foreign key migration & push it to the zip archive.
     *
     * @param  array $upData
     * @param  array $downData
     *
     * @return string
     */
    private function createForeignKeyMigration($upData, $downData)
    {
        $path = $this->getDatePrefix() . '_create_foreign_keys_table.php';

        // First we will get the stub file for the migration, which serves as a type
        // of template for the migration. Once we have those we will populate the
        // various place-holders, save the file, and push it to zip archive.
        $stub = $this->files->get(__DIR__ . '/stubs/foreignKey.stub');

        $contents = str_replace('return 1;', implode(PHP_EOL, $upData), $stub);
        $contents = str_replace('return 2;', implode(PHP_EOL, $downData), $contents);
        $this->flysystem->put($path, $contents);

        return $path;
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param  string $name
     * @param  string $stub
     * @param  array  $columnData
     *
     * @return string
     */
    private function populateStubWithData($name, $stub, $columnData)
    {
        $stub = str_replace('DummyClass', 'Create' . $this->getClassName($name) . 'Table', $stub);

        // We will replace the table place-holders with
        // the table specified by the developer.
        $stub = str_replace('DummyTable', $name, $stub);

        // We will replace the column place-holders with
        // the columns specified by the developer.
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

            $str .= sprintf('$table->%s(\'%s\')', $type, $data['name']);
        } else {
            $str .= sprintf('$table->%s(\'%s\'', $data['type'], $data['name']);

            if (in_array($data['type'], $columnWithLength) && !!$data['length']) {
                // Add column length
                $str .= ', ' . $data['length'];
            }

            $str .= ')';
        }

        // Default value check (only integer for now)
        // TODO: check if default value is integer or string
        !!$data['defValue'] && !$data['autoInc'] ? $str .= sprintf('->default(%s)', $data['defValue']) : null;

        // Nullable check
        $data['nullable'] ? $str .= '->nullable()' : null;

        // Unique check
        $data['unique'] ? $str .= '->unique()' : null;

        // Index check
        $data['index'] ? $str .= '->index()' : null;

        // Unsigned check
        $data['unsigned'] && !$data['autoInc'] ? $str .= '->unsigned()' : null;

        // Comment check
        !!$data['comment'] ? $str .= sprintf('->comment(\'%s\')', $data['comment']) : null;

        // End of statement
        $str .= ';';

        return $str;
    }

    /**
     * @param  string $table
     * @param  array  $column
     *
     * @return bool
     */
    private function parseForeignKeyData($table, $column)
    {
        $this->setForeignKeyData([
            $table => [
                $column['name'] => [
                    'table'  => $column['foreignKey']['on']['name'],
                    'column' => $column['foreignKey']['references']['name']
                ]
            ]
        ]);

        return true;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function buildForeignKeyData($data) {
        $upData = [];
        $downData = [];

        foreach ($data as $table => $columns) {
            $upData[] = str_repeat(' ', 8) .
                sprintf('Schema::table(\'%s\', function (Blueprint $table) {', $table);

            $downData[] = str_repeat(' ', 8) .
                sprintf('Schema::table(\'%s\', function (Blueprint $table) {', $table);

            foreach ($columns as $column => $relation) {
                $upData[] = str_repeat(' ', 12) . sprintf('$table->foreign(\'%s\')->references(\'%s\')->on(\'%s\');',
                        $column, $relation['column'], $relation['table']);

                $index = sprintf('%s_%s_foreign', $table, $column);
                $downData[] = str_repeat(' ', 12) . sprintf('$table->dropForeign(\'%s\');', $index);
            }

            $upData[] = str_repeat(' ', 8) . '});';
            $downData[] = str_repeat(' ', 8) . '});';

            // For new line
            $upData[] = '';
            $downData[] = '';
        }

        return [
            'up'   => $upData,
            'down' => $downData
        ];
    }
}