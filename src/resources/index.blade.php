<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Schema Builder</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lobster" />
        <style>
            {!! $css !!}
        </style>
    <body>
        <div id="root"></div>
        <script>
            var schema = {
                packageMode: true,
                apiEndpoint: '/api/v1/migration'
            };
            {!! $js !!}
        </script>
    </body>
</html>
