<?php
/**
 * This file is part of GameQ.
 *
 * GameQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GameQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 */

namespace GameQ;

use GameQ\Exception\Protocol as Exception;

/**
 * Class Buffer
 *
 * Read specific byte sequences from a provided string or Buffer
 *
 * @package GameQ
 *
 * @author  Austin Bischoff <austin@codebeard.com>
 * @author  Aidan Lister <aidan@php.net>
 * @author  Tom Buskens <t.buskens@deviation.nl>
 */
class Buffer
{

    /**
     * The original data
     *
     * @type string
     */
    private $data;

    /**
     * The original data
     *
     * @type int
     */
    private $length;

    /**
     * Position of pointer
     *
     * @type int
     */
    private $index = 0;

    /**
     * Constructor
     *
     * @param $data
     */
    public function __construct($data)
    {

        $this->data = $data;
        $this->length = strlen($data);
    }

    /**
     * Return all the data
     *
     * @return  string    The data
     */
    public function getData()
    {

        return $this->data;
    }

    /**
     * Return data currently in the buffer
     *
     * @return  string    The data currently in the buffer
     */
    public function getBuffer()
    {

        return substr($this->data, $this->index);
    }

    /**
     * Returns the number of bytes in the buffer
     *
     * @return  int  Length of the buffer
     */
    public function getLength()
    {

        return max($this->length - $this->index, 0);
    }

    /**
     * Read from the buffer
     *
     * @param int $length
     *
     * @return string
     * @throws \GameQ\Exception\Protocol
     */
    public function read($length = 1)
    {

        if (($length + $this->index) > $this->length) {
            throw new Exception("Unable to read length={$length} from buffer.  Bad protocol format or return?");
        }

        $string = substr($this->data, $this->index, $length);
        $this->index += $length;

        return $string;
    }

    /**
     * Read the last character from the buffer
     *
     * Unlike the other read functions, this function actually removes
     * the character from the buffer.
     *
     * @return string
     */
    public function readLast()
    {

        $len = strlen($this->data);
        $string = $this->data{strlen($this->data) - 1};
        $this->data = substr($this->data, 0, $len - 1);
        $this->length -= 1;

        return $string;
    }

    /**
     * Look at the buffer, but don't remove
     *
     * @param int $length
     *
     * @return string
     */
    public function lookAhead($length = 1)
    {

        return substr($this->data, $this->index, $length);
    }

    /**
     * Skip forward in the buffer
     *
     * @param int $length
     */
    public function skip($length = 1)
    {

        $this->index += $length;
    }

    /**
     * Jump to a specific position in the buffer,
     * will not jump past end of buffer
     *
     * @param $index
     */
    public function jumpto($index)
    {

        $this->index = min($index, $this->length - 1);
    }

    /**
     * Get the current pointer position
     *
     * @return int
     */
    public function getPosition()
    {

        return $this->index;
    }

    /**
     * Read from buffer until delimiter is reached
     *
     * If not found, return everything
     *
     * @param string $delim
     *
     * @return string
     * @throws \GameQ\Exception\Protocol
     */
    public function readString($delim = "\x00")
    {

        // Get position of delimiter
        $len = strpos($this->data, $delim, min($this->index, $this->length));

        // If it is not found then return whole buffer
        if ($len === false) {
            return $this->read(strlen($this->data) - $this->index);
        }

        // Read the string and remove the delimiter
        $string = $this->read($len - $this->index);
        ++$this->index;

        return $string;
    }

    /**
     * Reads a pascal string from the buffer
     *
     * @param int  $offset      Number of bits to cut off the end
     * @param bool $read_offset True if the data after the offset is to be read
     *
     * @return string
     * @throws \GameQ\Exception\Protocol
     */
    public function readPascalString($offset = 0, $read_offset = false)
    {

        // Get the proper offset
        $len = $this->readInt8();
        $offset = max($len - $offset, 0);

        // Read the data
        if ($read_offset) {
            return $this->read($offset);
        } else {
            return substr($this->read($len), 0, $offset);
        }
    }

    /**
     * Read from buffer until any of the delimiters is reached
     *
     * If not found, return everything
     *
     * @param      $delims
     * @param null $delimfound
     *
     * @return string
     * @throws \GameQ\Exception\Protocol
     *
     * @todo: Check to see if this is even used anymore
     */
    public function readStringMulti($delims, &$delimfound = null)
    {

        // Get position of delimiters
        $pos = [ ];
        foreach ($delims as $delim) {
            if ($p = strpos($this->data, $delim, min($this->index, $this->length))) {
                $pos[] = $p;
            }
        }

        // If none are found then return whole buffer
        if (empty($pos)) {
            return $this->read(strlen($this->data) - $this->index);
        }

        // Read the string and remove the delimiter
        sort($pos);
        $string = $this->read($pos[0] - $this->index);
        $delimfound = $this->read();

        return $string;
    }

    /**
     * Read an 8-bit unsigned integer
     *
     * @return int
     * @throws \GameQ\Exception\Protocol
     */
    public function readInt8()
    {

        $int = unpack('Cint', $this->read(1));

        return $int['int'];
    }

    /**
     * Read a 16-bit unsigned integer
     *
     * @return int
     * @throws \GameQ\Exception\Protocol
     */
    public function readInt16()
    {

        $int = unpack('Sint', $this->read(2));

        return $int['int'];
    }

    /**
     * Read a 16-bit signed integer
     *
     * @return int
     * @throws \GameQ\Exception\Protocol
     */
    public function readInt16Signed()
    {

        $int = unpack('sint', $this->read(2));

        return $int['int'];
    }

    /**
     * Read a 32-bit unsigned integer
     *
     * @return int
     * @throws \GameQ\Exception\Protocol
     */
    public function readInt32()
    {

        $int = unpack('Lint', $this->read(4));

        return $int['int'];
    }

    /**
     * Read a 32-bit signed integer
     *
     * @return int
     * @throws \GameQ\Exception\Protocol
     */
    public function readInt32Signed()
    {

        $int = unpack('lint', $this->read(4));

        return $int['int'];
    }

    /**
     * Read a 64-bit unsigned integer
     *
     * @return int
     * @throws \GameQ\Exception\Protocol
     */
    public function readInt64()
    {

        // We have the pack "q" code available. See: http://php.net/manual/en/function.pack.php
        if (version_compare(PHP_VERSION, '5.6.3') >= 0) {
            $int64 = unpack('qint', $this->read(8));

            $int = $int64['int'];

            unset($int64);
        } else {
            // We have to do the number via bitwise
            $low = $this->readInt32();
            $high = $this->readInt32();

            $int = ($high << 32) | $low;

            unset($low, $high);
        }

        return $int;
    }

    /**
     * Read a 32-bit float
     *
     * @return float
     * @throws \GameQ\Exception\Protocol
     */
    public function readFloat32()
    {

        $float = unpack('ffloat', $this->read(4));

        return $float['float'];
    }
}
