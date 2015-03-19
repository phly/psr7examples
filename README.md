# PSR-7 Stream Examples

[PSR-7](https://github.com/php-fig/fig-standards/blob/master/proposed/http-message.md)
uses `Psr\Http\Message\StreamableInterface` to represent content for a
message. This has a variety of benefits, as outlined in the specification.
However, for some, the model poses some conceptual challenges:

- What if I want to emit a file on the server, like I might with `fpassthru()`
  or `stream_copy_to_stream($fileHandle, fopen('php://output'))`?
- What if I want to use a callback to produce my output?
- What if I want to use output buffering and/or `echo`/`printf`/etc.
  directly?
- What if I want to iterate over a data structure and iteratively output content?

These patterns are all possible with creative implementations of
`StreamableInterface`.

- The file [copy-stream.php](public/copy-stream.php) demonstrates how you would
  emit a file.
- [CallbackStream](src/CallbackStream.php) and [php-output.php](public/php-output.php)
  demonstrate using a callback to generate and return content.
- [CallbackStream](src/CallbackStream.php) and [php-output.php](public/php-output.php)
  also demonstrate how you might use a callback to allow direct output from your
  code, without first aggregating it.
- [IteratorStream](src/IteratorStream.php) and the files [iterator.php](public/iterator.php)
  and [generator.php](public/generator.php) demonstrate using iterators and
  generators for creating output.

In each, the assumption is that the application will short-circuit on receiving
a response as a return value. Most modern frameworks do this already, and it's a
guiding principle of middleware.

The code in this repository uses [phly/http](https://github.com/phly/http) as
the PSR-7 implementation; any PSR-7 implementation should behave similarly.

## Analyzing the code

### Emitting a file

For those who are accustomed to using `readfile()`, `fpassthru()` or copying a
stream into `php://output` via `stream_copy_to_stream()`, PSR-7 will look and
feel different. Typically, you will not use the aforementioned techniques when
building an application to work with PSR-7, as they bypass the HTTP message
entirely, and delegate it to PHP itself.

The problem with using these built-in PHP methods is that you cannot test your
code as easily, as it now has side-effects. One major reason to adopt PSR-7 is
if you want to be able to test your web-facing code without worrying about side
effects. Adopting frameworks or application architectures that work with HTTP
messages lets you pass in a request, and make assertions on the response.

In the case of emitting a file, this means that you will:

- Create a `Stream` instance, passing it the file location.
- Provide appropriate headers to the response.
- Provide your stream instance to the response.
- Return your response.

Which looks like what we have in [copy-stream.php](public/copy-stream.php):

```php
$image = __DIR__ . '/cuervo.jpg';

return (new Response())
    ->withHeader('Content-Type', 'image/jpeg')
    ->withHeader('Content-Length', (string) filesize($image))
    ->withBody(new Stream($image));
return $response;
```

The assumption is that returning a response will bubble out of your application;
most modern frameworks do this already, as does middleware. As such, you will
typically have minimal additional overhead from the time you create the response
until it's streaming your file back to the client.

### Direct output

Just like the above example, for those accustomed to directly calling `echo`, or
sending data directly to the `php://output` stream, PSR-7 will feel strange.
However, as noted before as well, these are actions that have side effects that
act as a barrier to testing and other quality assurance activities.

There _is_ a way to accomodate these, however, with a little trickery: wrapping any
output-emitting code in a callback, and passing this to a callback-enabled
stream implementation. The [CallbackStream](src/CallbackStream.php) implementation
in this repo is one potential way to accomplish it.

As an example, from [php-output.php](public/php-output.php):

```php
$output = new CallbackStream(function () use ($request) {
    printf("The requested URI was: %s<br>\n", $request->getUri());
    return '';
});
return (new Response())
    ->withHeader('Content-Type', 'text/html')
    ->withBody($output);
```

This has a few benefits over directly emitting output from within your
web-facing code:

- We can ensure our headers are sent before emitting output.
- We can set a non-200 status code if desired.
- We can test the various aspects of the response separately from the output.
- We still get the benefits of the output buffer.

As noted previously, returning a response will generally bubble out of the
application immediately, making this a very viable option for emitting output
directly.

(Note: the callback could also aggregate content and return it as a string if
desired; I wanted to demonstrate specifically how it can be used to work with
output buffering.)

### Iterators and generators

Ruby's Rack specification uses an iterable body for response messages, instead
of a stream. In some situations, such as returning large data sets, this could
be tremendously useful. Can PSR-7 accomplish it?

The answer is, succinctly, yes. The [IteratorStream](src/IteratorStream.php)
implementation in this repo is a rough prototype showing how it may work; usage
would be as in [iterator.php](public/iterator.php):

```php
$output = new IteratorStream(new ArrayObject([
    "Foo!<br>\n",
    "Bar!<br>\n",
    "Baz!<br>\n",
]));
return (new Response())
    ->withHeader('Content-Type', 'text/html')
    ->withBody($output);
```

or, with a generator per [generator.php](public/generator.php):

```php
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
```

This is a nice approach, as you can iteratively generate the data returned; if
you are worried about data overhead from aggregating the data before returning
it, you can always use `print` or `echo` statements instead of aggregation
within the iterator stream implementation.

## Testing it out

You can test it out for yourself:

- Clone this repo
- Run `composer install`
- Run `cd public ; php -S 0:8080` in the directory, and then browse to
  `http://localhost:8080/{filename}`, where `{filename}` is one of:
  - `copy-stream.php`
  - `generator.php`
  - `iterator.php`
  - `php-output.php`

## Improvements

This was a quick repository built to demonstrate that PSR-7 fulfills these
scenarios; however, they are far from comprehensive. Some ideas:

- `IteratorStream` could and likely should allow providing a separator, and
  potentially preamble/postfix for wrapping content.
- `IteratorStream` and `CallbackStream` could be optimized to emit output
  directly instead of aggregating + returning, if you are worried about large
  data sets.
- `CallbackStream` could cache the contents to allow multiple reads (though
  using `detach()` would allow it already).
