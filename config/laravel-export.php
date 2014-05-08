<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Export Format
    |--------------------------------------------------------------------------
    |
    | The default format to use when no format is specified. Must match
    | a registered format name (e.g. 'csv', 'json').
    |
    */
    'default_format' => 'csv',

    /*
    |--------------------------------------------------------------------------
    | CSV Options
    |--------------------------------------------------------------------------
    |
    | Configuration options for the CSV exporter.
    |
    */
    'csv' => [
        'delimiter' => ',',
        'enclosure' => '"',
        'include_bom' => true,
        'include_headers' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON Options
    |--------------------------------------------------------------------------
    |
    | Configuration options for the JSON exporter.
    |
    */
    'json' => [
        'pretty_print' => true,
        'include_metadata' => false,
    ],

];
