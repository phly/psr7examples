<?php
/**
 * Example demonstrating a generator-based stream.
 *
 * @copyright Copyright (c) 2015 Matthew Weier O'Phinney (https://mwop.net)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @codingStandardsIgnoreFile
 */

use Phly\Http\Response;
use Phly\Http\Server;
use Psr7Examples\IteratorStream;

require 'vendor/autoload.php';

$server = Server::createServer(function ($request, $response, $done) {
    $generator = function ($count) {
        while ($count) {
            --$count;
            yield(uniqid() . "<br>\n");
        }
    };

    $output = new IteratorStream($generator(10));

    return (new Response())
        ->withHeader('Content-Type', 'text/html')
        ->withBody($output);
}, $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

$server->listen();