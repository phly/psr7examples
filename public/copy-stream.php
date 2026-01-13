<?php
/**
 * Example demonstrating returning a file as a stream.
 *
 * Essentially, point your Phly\Http\Stream at a file, pass it to a response,
 * and return the response; if you can provide proper headers, do it.
 *
 * @copyright Copyright (c) 2015 Matthew Weier O'Phinney (https://mwop.net)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @codingStandardsIgnoreFile
 */

use Phly\Http\Response;
use Phly\Http\Server;
use Phly\Http\Stream;

require __DIR__ . '/../vendor/autoload.php';

$server = Server::createServer(function ($request, $response, $done) {
    // Cuervo was our original Basset Hound; this was her in her natural habitat.
    $image = __DIR__ . '/cuervo.jpg';

    return (new Response())
        ->withHeader('Content-Type', 'image/jpeg')
        ->withHeader('Content-Length', (string) filesize($image))
        ->withBody(new Stream($image));
}, $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

$server->listen();
