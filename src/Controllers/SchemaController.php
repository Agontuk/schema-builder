<?php

namespace Agontuk\Schema\Controllers;

use Agontuk\Schema\Migrations\MigrationCreator;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as LaravelController;
use Laravel\Lumen\Routing\Controller as LumenController;

if (class_exists("\\Illuminate\\Routing\\Controller")) {
    class BaseController extends LaravelController {}
} else if (class_exists("Laravel\\Lumen\\Routing\\Controller")) {
    class BaseController extends LumenController {}
}

class SchemaController extends BaseController
{
    /**
     * @var MigrationCreator
     */
    private $creator;

    /**
     * SchemaController constructor.
     *
     * @param MigrationCreator $creator
     */
    public function __construct(MigrationCreator $creator)
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
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function generateMigration(Request $request)
    {
        try {
            $schema = json_decode($request->get('schema'), true);

            $this->creator->parseAndBuildMigration($schema);

            return response()->download(storage_path('migrations.zip'))->deleteFileAfterSend(true);
        } catch (Exception $e) {
            return response()->json([
                'error'  => [
                    'message' => $e->getMessage()
                ],
                'status' => 200
            ]);
        }
    }
}
