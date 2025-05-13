<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports "GD Library", "Imagick" and "libvips" to process
    | images internally. You may choose one of them according to your PHP
    | configuration. By default PHP's "GD Library" implementation is used.
    |
    | Supported: "gd", "imagick", "libvips"
    |
    */
    'driver' => env('IMAGE_DRIVER', 'gd'),
];
