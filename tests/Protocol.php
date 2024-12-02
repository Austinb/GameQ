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
 * Protocol test class
 *
 * @package GameQ\Tests
 */
class Protocol extends TestBase
{
    /**
     * Holds stub on setup
     *
     * @var \GameQ\Protocol
     */
    protected $stub;

    /**
     * Some dummy options
     *
     * @var array
     */
    protected $options = [
        'key1' => 'var1',
    ];

    /**
     * Setup to create our stub
     *
     * @before
     */
    public function customSetUp()
    {
        $this->stub = $this->getMockForAbstractClass('\GameQ\Protocol', [ $this->options ]);
    }

    /**
     * Random assortment of general tests for completeness
     */
    public function testGeneral()
    {
        $name = 'Test name';
        $nameLong = 'Test name bigger, longer';
        $portDiff = 5454;

        $reflection = new \ReflectionClass($this->stub);
        $reflection_property_name = $reflection->getProperty('name');
        $reflection_property_name->setAccessible(true);

        $reflection_property_name->setValue($this->stub, $name);

        $this->assertEquals($name, $this->stub->name());

        $reflection_property_namelong = $reflection->getProperty('name_long');
        $reflection_property_namelong->setAccessible(true);

        $reflection_property_namelong->setValue($this->stub, $nameLong);

        $this->assertEquals($nameLong, $this->stub->nameLong());

        // Test transport
        $this->assertEquals(\GameQ\Protocol::TRANSPORT_UDP, $this->stub->transport());

        // Test transport setting
        $this->stub->transport(\GameQ\Protocol::TRANSPORT_TCP);
        $this->assertEquals(\GameQ\Protocol::TRANSPORT_TCP, $this->stub->transport());

        // Test protocol state
        $this->assertEquals(\GameQ\Protocol::STATE_STABLE, $this->stub->state());

        // Test port diff
        $reflection_property_portdiff = $reflection->getProperty('port_diff');
        $reflection_property_portdiff->setAccessible(true);

        $reflection_property_portdiff->setValue($this->stub, $portDiff);

        $this->assertEquals($portDiff, $this->stub->portDiff());
    }

    /**
     * Test packet setter/getter and some other packet methods
     */
    public function testPackets()
    {
        $packets = [
            \GameQ\Protocol::PACKET_CHALLENGE => 'Do you even lift?',
            \GameQ\Protocol::PACKET_RULES     => 'There are no rules!!',
        ];

        $reflection = new \ReflectionClass($this->stub);
        $reflection_property = $reflection->getProperty('packets');
        $reflection_property->setAccessible(true);

        $reflection_property->setValue($this->stub, $packets);

        // Test all return
        $this->assertEquals($packets, $this->stub->getPacket());

        // Test multiple selected
        $this->assertEquals($packets, $this->stub->getPacket([
            \GameQ\Protocol::PACKET_CHALLENGE,
            \GameQ\Protocol::PACKET_RULES,
        ]));

        // Test single selected
        $this->assertEquals(
            $packets[\GameQ\Protocol::PACKET_CHALLENGE],
            $this->stub->getPacket(\GameQ\Protocol::PACKET_CHALLENGE)
        );

        // Drop challenge and test for !challenge
        unset($packets[\GameQ\Protocol::PACKET_CHALLENGE]);

        $reflection_property->setValue($this->stub, $packets);

        $this->assertEquals($packets, $this->stub->getPacket('!challenge'));

        // test hasChallenge
        $this->assertFalse($this->stub->hasChallenge());
    }

    /**
     * Test options methods
     */
    public function testOptions()
    {
        // Check the options getter
        $this->assertEquals($this->options, $this->stub->options());

        // Set new options and then check
        $new_options = [
            'key2' => 'value2',
        ];

        $this->stub->options($new_options);

        $this->assertEquals($new_options, $this->stub->options());
    }
}
