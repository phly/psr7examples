<?php
/**
 * @copyright Copyright (c) 2015 Matthew Weier O'Phinney (https://mwop.net)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

namespace Psr7Examples;

use Psr\Http\Message\StreamableInterface;

/**
 * Callback-based stream implementation.
 *
 * Wraps a callback, and invokes it in order to stream it.
 *
 * Only one invocation is allowed; multiple invocations will return an empty
 * string for the second and subsequent calls.
 */
class CallbackStream implements StreamableInterface
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * Whether or not the callback has been previously invoked.
     *
     * @var bool
     */
    private $called = false;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->output();
    }
    
    /**
     * Execute the callback with output buffering.
     *
     * @return null|string Null on second and subsequent calls.
     */
    public function output()
    {
        if ($this->called) {
            return;
        }
        
        $this->called = true;

        ob_start();
        call_user_func($this->callback);
        return ob_get_clean();
    }

    /**
     * @return void
     */
    public function close()
    {
    }

    /**
     * @return null|callable
     */
    public function detach()
    {
        $callback = $this->callback;
        $this->callback = null;
        return $callback;
    }

    /**
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
    }

    /**
     * @return int|bool Position of the file pointer or false on error.
     */
    public function tell()
    {
        return 0;
    }

    /**
     * @return bool
     */
    public function eof()
    {
        return $this->called;
    }

    /**
     * @return bool
     */
    public function isSeekable()
    {
        return false;
    }

    /**
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        return false;
    }

    /**
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function rewind()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return false;
    }

    /**
     * @param string $string The string that is to be written.
     * @return int|bool Returns the number of bytes written to the stream on
     *     success or FALSE on failure.
     */
    public function write($string)
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string|false Returns the data read from the stream, false if
     *     unable to read or if an error occurs.
     */
    public function read($length)
    {
        return $this->output();
    }

    /**
     * @return string
     */
    public function getContents()
    {
        return $this->output();
    }

    /**
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        if ($key === null) {
            return array();
        }
        return null;
    }
}
