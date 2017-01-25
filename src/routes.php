<?php

if ($isLumen) {
    // Load views for schema builder
    $app->get('schema', 'SchemaController@index');

    // Generate database migration files
    $app->post('schema', 'SchemaController@generateMigration');
} else {
    // Load views for schema builder
    Route::get('schema', 'SchemaController@index');

    // Generate database migration files
    Route::post('schema', 'SchemaController@generateMigration');
}