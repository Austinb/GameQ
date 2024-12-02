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

namespace GameQ\Tests\Filters;

use GameQ\Tests\TestBase;

/**
 * Class for testing Filters Base
 *
 * @package GameQ\Tests\Filters
 */
class Base extends TestBase
{
    /**
     * Load up the provider data for the specific filter type
     *
     * @return array
     */
    public function loadData()
    {
        // Explode the class that called to avoid strict error
        $class = explode('\\', get_called_class());

        // Determine the folder to grab the provider files and results from
        $providersLookup = sprintf('%s/Providers/%s/', __DIR__, array_pop($class));

        // Init the return array
        $providers = [ ];

        // Grab all of the test files for this filter
        $files = new \DirectoryIterator($providersLookup);

        // Iterate over the files in this path
        foreach ($files as $fileinfo) {
            // Skip if we can't read or is dotfile
            if (!$fileinfo->isReadable() || !$fileinfo->isFile()) {
                continue;
            }

            // Split the filename
            list($protocol, $index) = explode('_', $fileinfo->getFilename());

            unset($index);

            // Get the data
            if (($data = json_decode(file_get_contents($fileinfo->getRealPath()), true)) === null
                && json_last_error() !== JSON_ERROR_NONE
            ) {
                // Skip
                continue;
            }

            // Add the provider
            $providers[] = [
                $protocol,
                $data['raw'],
                $data['filtered']
            ];
        }

        // Clear some memory
        unset($files, $fileinfo, $providersLookup);

        return $providers;
    }

    // Real Base tests here

    /**
     * Test options setting on construct
     */
    public function testOptions()
    {
        $options = [
            'option1' => 'value1',
            'option2' => 'value2',
        ];

        $mock = $this->getMockForAbstractClass('\GameQ\Filters\Base', [ $options ]);

        $this->assertEquals($options, $mock->getOptions());
    }
}
