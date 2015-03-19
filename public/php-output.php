<?php
/**
 * Example demonstrating an callback-based stream.
 *
 * Of particular interest in this file is that the callback actually emits
 * output; the CallbackStream implementation only allows invoking the
 * callback once, so this generally works, as you can ensure that
 * (a) the content is not double-emitted, and (b) by wrapping the callback
 * in the response, you get your headers emitted first.
 *
 * @copyright Copyright (c) 2015 Matthew Weier O'Phinney (https://mwop.net)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @codingStandardsIgnoreFile
 */

use Phly\Http\Response;
use Phly\Http\Server;
use Psr7Examples\CallbackStream;

require __DIR__ . '/../vendor/autoload.php';

$server = Server::createServer(function ($request, $response, $done) {
    $output = new CallbackStream(function () use ($request) {
        printf("The requested URI was: %s<br>\n", $request->getUri());
        return '';
    });
    return (new Response())
        ->withHeader('Content-Type', 'text/html')
        ->withBody($output);
}, $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

$server->listen();
