<?php

if ($isLumen) {
    // Load views for schema builder
    $app->get('schema', 'SchemaController@index');

    // Generate database migration files
    $app->post('api/v1/migration', 'SchemaController@generateMigration');
} else {
    // Load views for schema builder
    Route::get('schema', 'SchemaController@index');

    // Generate database migration files
    Route::post('api/v1/migration', 'SchemaController@generateMigration');
}