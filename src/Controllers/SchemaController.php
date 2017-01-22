<?php

namespace Agontuk\Schema\Controllers;

use Agontuk\Schema\MigrationCreator;
use Exception;
use Illuminate\Http\Request;

if (class_exists("\\Illuminate\\Routing\\Controller")) {
    class BaseController extends \Illuminate\Routing\Controller {}
} else if (class_exists("Laravel\\Lumen\\Routing\\Controller")) {
    class BaseController extends \Laravel\Lumen\Routing\Controller {}
}

class SchemaController extends BaseController
{
    private $creator;

    /**
     * SchemaController constructor.
     * @param MigrationCreator $creator
     */
    function __construct(MigrationCreator $creator)
    {
        $this->creator = $creator;
    }

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

    /**
     * Generate migration files based on the data.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function generateMigration(Request $request)
    {
        try {
            $this->creator->parseAndBuildMigration($request);
        } catch(Exception $e) {
            dd($e);
        }
    }
}
