<?php
/**
 * This file is part of GameQ.
 *
 * GameQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GameQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * $Id: Exceptions.php,v 1.4 2008/06/26 12:43:25 tombuskens Exp $  
 */
 
 
/*
 * @author    Tom Buskens    <t.buskens@deviation.nl>
 * @version   $Revision: 1.4 $
 */ 
class GameQ_ParsingException extends Exception
{
    private $packet;
    protected $format = 'Could not parse packet for server "%s"';

    function __construct($packet = null)
    {
        $this->packet = $packet;
        parent::__construct('');
    }

    public function getPacket()
    {
        return $packet;
    }
}
?>
