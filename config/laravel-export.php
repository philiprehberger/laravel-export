<?php

declare(strict_types=1);

return [

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

    /*
    |--------------------------------------------------------------------------
    | XML Options
    |--------------------------------------------------------------------------
    |
    | Configuration options for the XML exporter.
    |
    */
    'xml' => [
        'root_element' => 'items',
        'item_element' => 'item',
    ],

];
