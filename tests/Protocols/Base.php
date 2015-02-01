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

namespace GameQ\Tests\Protocols;

abstract class Base extends \PHPUnit_Framework_TestCase
{

    /**
     * Shared provider to give protocols the data to test with
     *
     * @return array
     */
    public function loadData()
    {

        // Determine the folder to grab the provider files and results from
        $providersLookup = sprintf('%s/Providers/%s/', __DIR__, array_pop(explode('\\', get_called_class())));

        // Init the return array
        $providers = [ ];

        // Do a glob lookup just for the responses
        $files = new \GlobIterator($providersLookup . '*_response.txt');

        // Iterate over the list of response files that exists
        foreach ($files as $fileinfo) {
            if (!$fileinfo->isReadable() || !$fileinfo->isFile()) {
                continue;
            }

            list($index, $type) = explode('_', $fileinfo->getFilename());

            // Append this data to the providers return
            $providers[] = [
                explode(PHP_EOL . '||' . PHP_EOL, file_get_contents($fileinfo->getRealPath())),
                json_decode(file_get_contents(sprintf('%s%d_result.json', $providersLookup, $index)), true)
            ];
        }

        // Clear some memory
        unset($files, $fileinfo, $providersLookup);

        return $providers;
    }
}
