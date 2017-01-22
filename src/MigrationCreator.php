<?php

namespace Agontuk\Schema;

class MigrationCreator
{
    public function parseAndBuildMigration($data)
    {
        $tables = json_decode($data->get('tables'), true);

        foreach ($tables as $table) {
            // Logic here
        }
    }
}