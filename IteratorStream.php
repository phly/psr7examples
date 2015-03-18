<?php
/**
 * @copyright Copyright (c) 2015 Matthew Weier O'Phinney (https://mwop.net)
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

namespace Psr7Examples;

use Countable;
use IteratorAggregate;
use Traversable;
use Psr\Http\Message\StreamableInterface;

/**
 * Iterator-based stream implementation.
 *
 * Wraps an iterator to allow seeking, reading, and casting to string.
 *
 * Keys are ignored, and content is concatenated without separators.
 */
class IteratorStream implements StreamableInterface
{
    /**
     * @var Traversable
     */
    private $iterator;

    /**
     * Current position in iterator
     *
     * @var int
     */
    private $position = 0;

    /**
     * Construct a stream instance using an iterator.
     *
     * If the iterator is an IteratorAggregate, pulls the inner iterator
     * and composes that instead, to ensure we have access to the various
     * iterator capabilities.
     *
     * @param Traversable $iterator
     */
    public function __construct(Traversable $iterator)
    {
        if ($iterator instanceof IteratorAggregate) {
            $iterator = $iterator->getIterator();
        }
        $this->iterator = $iterator;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $this->iterator->rewind();

        return $this->getContents();
    }

    /**
     * No-op.
     *
     * @return void
     */
    public function close()
    {
    }

    /**
     * @return null|Traversable
     */
    public function detach()
    {
        $iterator = $this->iterator;
        $this->iterator = null;
        return $iterator;
    }

    /**
     * @return int|null Returns the size of the iterator, or null if unknown.
     */
    public function getSize()
    {
        if ($this->iterator instanceof Countable) {
            return count($this->iterator);
        }

        return null;
    }

    /**
     * @return int|bool Position of the iterator or false on error.
     */
    public function tell()
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function eof()
    {
        if ($this->iterator instanceof Countable) {
            return ($this->position === count($this->iterator));
        }

        return (! $this->iterator->valid());
    }

    /**
     * @return bool
     */
    public function isSeekable()
    {
        return true;
    }

    /**
     * @param int $offset Stream offset
     * @param int $whence Ignored.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (! is_int($offset) && ! is_numeric($offset)) {
            return false;
        }
        $offset = (int) $offset;

        if ($offset < 0) {
            return false;
        }

        $key = $this->iterator->key();
        if (! is_int($key) && ! is_numeric($key)) {
            $key = 0;
            $this->iterator->rewind();
        }

        if ($key >= $offset) {
            $key = 0;
            $this->iterator->rewind();
        }

        while ($this->iterator->valid() && $key < $offset) {
            $this->iterator->next();
            ++$key;
        }

        $this->position = $key;
        return true;
    }

    /**
     * @see seek()
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function rewind()
    {
        $this->iterator->rewind();
        $this->position = 0;
        return true;
    }

    /**
     * @return bool Always returns false
     */
    public function isWritable()
    {
        return false;
    }

    /**
     * Non-writable
     *
     * @param string $string The string that is to be written.
     * @return int|bool Always returns false
     */
    public function write($string)
    {
        return false;
    }

    /**
     * @return bool Always returns true
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * @param int $length Read up to $length items from the object and return
     *     them. Fewer than $length items may be returned if underlying iterator
     *     has fewer items.
     * @return string|false Returns the data read from the iterator, false if
     *     unable to read or if an error occurs.
     */
    public function read($length)
    {
        $index    = 0;
        $contents = '';

        while ($this->iterator->valid() && $index < $length) {
            $contents .= $this->iterator->current();
            $this->iterator->next();
            ++$this->position;
            ++$index;
        }

        return $contents;
    }

    /**
     * @return string
     */
    public function getContents()
    {
        $contents = '';
        while ($this->iterator->valid()) {
            $contents .= $this->iterator->current();
            $this->iterator->next();
            ++$this->position;
        }
        return $contents;
    }

    /**
     * @param string $key Specific metadata to retrieve.
     * @return array|null Returns an empty array if no key is provided, and
     *     null otherwise.
     */
    public function getMetadata($key = null)
    {
        return ($key === null) ? array() : null;
    }
}