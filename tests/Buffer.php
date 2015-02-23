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
 */

namespace GameQ\Tests;

/**
 * Buffer test class
 *
 * @package GameQ\Tests
 */
class Buffer extends \PHPUnit_Framework_TestCase
{
    /**
     * Build a mock Buffer
     *
     * @param string $data
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function buildBuffer($data)
    {

        return $this->getMock('GameQ\Buffer', null, [ $data ]);
    }

    /**
     * Data provider for all of the integer testing. Loads external files since the file format has to be in
     * ascii format for the tests to work correctly
     *
     * @return array
     */
    public function integerDataProvider()
    {

        //echo PHP_INT_MAX; exit;

        // Make the base path for the data to test since it has to be in ascii form
        $basePath = sprintf('%s/Providers/Buffer', __DIR__);

        // Build the result array
        $dataSet = [
            [ 'readInt8', sprintf('%s/8bit_1.txt', $basePath), 214 ],
            [ 'readInt8', sprintf('%s/8bit_2.txt', $basePath), 14 ],
            [ 'readInt16', sprintf('%s/16bitunsigned_1.txt', $basePath), 54844 ],
            [ 'readInt16', sprintf('%s/16bitunsigned_2.txt', $basePath), 1474 ],
            [ 'readInt16Signed', sprintf('%s/16bitsigned_1.txt', $basePath), 24574 ],
            [ 'readInt16Signed', sprintf('%s/16bitsigned_2.txt', $basePath), -5478 ],
            [ 'readInt32', sprintf('%s/32bitunsigned_1.txt', $basePath), 3248547147 ],
            [ 'readInt32', sprintf('%s/32bitunsigned_2.txt', $basePath), 1247612474 ],
            [ 'readInt32Signed', sprintf('%s/32bitsigned_1.txt', $basePath), 1247965816 ],
            [ 'readInt32Signed', sprintf('%s/32bitsigned_2.txt', $basePath), -1547872147 ],
        ];

        // We are on 64-bit os
        if (PHP_INT_SIZE == 8) {
            // Add 64-bit tests
            $dataSet[] = [ 'readInt64', sprintf('%s/64bitunsigned_1.txt', $basePath), 90094348778156039 ];
            $dataSet[] = [ 'readInt64', sprintf('%s/64bitunsigned_2.txt', $basePath), 240 ];
        }

        return $dataSet;
    }

    /**
     * Test general methods for the Buffer class
     */
    public function testGeneral()
    {

        $data = "Some Kind of buffer";

        $buffer = $this->buildBuffer($data);

        // Test buffer and string are equal
        $this->assertEquals($data, $buffer->getData(), 'Test string and buffer are not the same');

        // Test length is set correctly
        $this->assertEquals(strlen($data), $buffer->getLength(), 'Test string and buffer length do not match');
    }

    /**
     * Test various buffer reads
     *
     * @depends testGeneral
     */
    public function testRead()
    {

        $data = "Buffer of data";

        $buffer = $this->buildBuffer($data);

        // Test look ahead default
        $this->assertEquals(substr($data, 0, 1), $buffer->lookAhead());

        // Test longer look ahead
        $this->assertEquals(substr($data, 0, 4), $buffer->lookAhead(4));

        // Test default is one character
        $this->assertEquals(substr($data, 0, 1), $buffer->read());

        // Test multiple character read
        $this->assertEquals(substr($data, 1, 5), $buffer->read(5));

        // Read last character out of the buffer
        $this->assertEquals(substr($data, -1, 1), $buffer->readLast());

        // Get the remainder of the buffer
        $this->assertEquals(substr($data, 6, -1), $buffer->getBuffer());
    }

    /**
     * Test for index positions
     *
     * @depends testRead
     */
    public function testPosition()
    {

        $data = "Some like My Strings...";

        $buffer = $this->buildBuffer($data);

        // Test basic index position
        $this->assertEquals(0, $buffer->getPosition());

        // Jump in the index
        $buffer->jumpto(8);

        // Make sure the index is correct returned
        $this->assertEquals(8, $buffer->getPosition());
    }

    /**
     * Test for proper read exception
     *
     * @depends                  testRead
     *
     * @expectedException Exception
     * @expectedExceptionMessage Unable to read length=6 from buffer.  Bad protocol format or return?
     */
    public function testReadException()
    {

        $buffer = $this->buildBuffer("12345");

        // Try to read a longer length than the buffer has in it
        $buffer->read(6);
    }

    /**
     * Test reading some strings
     *
     * @depends testRead
     */
    public function testReadString()
    {

        $data = "This is string 1\x00This is string 2\x00";

        $buffer = $this->buildBuffer($data);

        // Read first
        $this->assertEquals('This is string 1', $buffer->readString());

        // Read again
        $this->assertEquals('This is string 2', $buffer->readString());

        // Reset the index
        $buffer->jumpto(0);

        // Test the read using non-default, this should return the whole string
        $this->assertEquals($data, $buffer->readString("\xFF"));
    }

    /**
     * Test number reads reads
     *
     * @depends      testRead
     * @dataProvider integerDataProvider
     *
     * @param $method
     * @param $file
     * @param $expected
     */
    public function testNumberReads($method, $file, $expected)
    {

        // Make the buffer
        $buffer = $this->buildBuffer(file_get_contents($file));

        // Run the test
        $this->assertEquals($expected, call_user_func_array([ $buffer, $method ], [ ]));

        unset($buffer);
    }
}
