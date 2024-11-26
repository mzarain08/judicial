<?php


/**
 * Dump the arguments and end the script.
 *
 * @return void
 */
if (!function_exists('dd')) {
    function dd()
    {
        array_map(function($x) {
            dump($x);
        }, func_get_args());
        die;
    }
}