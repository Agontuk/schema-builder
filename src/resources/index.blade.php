<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ $csrfToken }}">
        <title>Schema Builder</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lobster|Roboto" />
        <style>
            {!! $css !!}
        </style>
    <body>
        <div id="root"></div>
        <script>
            var schema = {
                packageMode: true
            };
            {!! $js !!}
        </script>
    </body>
</html>
