<?php

namespace Agontuk\Schema\Controllers;

use Illuminate\Http\Request;

if (class_exists("\\Illuminate\\Routing\\Controller")) {
    class BaseController extends \Illuminate\Routing\Controller {}
} else if (class_exists("Laravel\\Lumen\\Routing\\Controller")) {
    class BaseController extends \Laravel\Lumen\Routing\Controller {}
}

class SchemaController extends BaseController
{
    /**
     * Load the schema designer.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $css = file_get_contents(__DIR__ . '/../resources/schema.css');

        $js = file_get_contents(__DIR__ . '/../resources/jsplumb.min.js') . "\n";
        $js .= file_get_contents(__DIR__ . '/../resources/schema.js');

        return view('schema::index')->with(compact('css', 'js'));
    }

    public function generateMigration(Request $request)
    {
        dd($request->all());
    }
}
