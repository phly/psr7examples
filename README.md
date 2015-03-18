# PSR-7 Stream Examples

PSR-7 uses `Psr\Http\Message\StreamableInterface` to represent content for a
message. This has a variety of benefits, as outlined in the specification.
However, for some, the model poses some conceptual challenges:

- What if I want to emit a file on the server, like I might with `fpassthru()`
  or `stream_copy_to_stream($fileHandle, fopen('php://output'))`?
- What if I want to use a callback to produce my output?
- What if I don't want to use output buffering and/or `echo`/`printf`/etc.
  directly?
- What if I want to iterate over a data structure and iteratively output content?

These patterns are all possible with creative implementations of
`StreamableInterface`.

- The file `copy-stream.php` demonstrates how you would emit a file.
- `CallbackStream` and `php-output.php` demonstrate using a callback to generate
  and return content.
- `CallbackStream` and `php-output.php` also demonstrate how you might use a
  callback to allow direct output from your code, without first aggregating it.
- `IteratorStream` and the files `iterator.php` and `generator.php` demonstrate
  using iterators and generators for creating output.

In each, the assumption is that the application will short-circuit on receiving
a response as a return value. Most modern frameworks do this already, and it's a
guiding principle of middleware.

## Testing it out

- Clone this gist
- Run `composer install`
- Run `php -S 0:8080` in the directory, and then browse to
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
- `CallbackStream` could cache the contents to allow multiple reads (though
  using `detach()` would allow it already).