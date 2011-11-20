<?php

class GameQ_Protocols_Bf3_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Provide test data and expected results for test_process_status_pre_R9.
	 */
	public static function provider_process_status_pre_R9()
	{
		return array(
			array(
				array('OK','BigBrotherBot #2', '0', '16', 'ConquestLarge0', 'MP_012', '0', '2', '0', '0', '', 'true', 'true', 'false', '5148', '455'),
				array(
					'dedicated' => 'true',
					'mod' => 'false',
					'hostname' => 'BigBrotherBot #2',
					'numplayers' => '0',
					'maxplayers' => '16',
					'gametype' => 'ConquestLarge0',
					'map' => 'MP_012',
					'roundsplayed' => '0',
					'roundstotal' => '2',
					'targetscore' => '0',
					'online' => '',
					'ranked' => 'true',
					'punkbuster' => 'true',
					'password' => 'false',
					'uptime' => '5148',
					'roundtime' => '455'
				)
			),

			array(
				array('OK','BigBrotherBot #2', '0', '16', 'ConquestLarge0', 'MP_012', '0', '2', '1', '47', '0', '', 'true', 'true', 'false', '5148', '455'),
				array(
					'dedicated' => 'true',
					'mod' => 'false',
					'hostname' => 'BigBrotherBot #2',
					'numplayers' => '0',
					'maxplayers' => '16',
					'gametype' => 'ConquestLarge0',
					'map' => 'MP_012',
					'roundsplayed' => '0',
					'roundstotal' => '2',
					'targetscore' => '0',
					'online' => '',
					'ranked' => 'true',
					'punkbuster' => 'true',
					'password' => 'false',
					'uptime' => '5148',
					'roundtime' => '455',
					'teams' => array(
						array('id' => 1, 'tickets' => '47'),
					)
				)
			),

			array(
				array('OK','BigBrotherBot #2', '0', '16', 'ConquestLarge0', 'MP_012', '0', '2', '2', '47', '54', '0', '', 'true', 'true', 'false', '5148', '455'),
				array(
					'dedicated' => 'true',
					'mod' => 'false',
					'hostname' => 'BigBrotherBot #2',
					'numplayers' => '0',
					'maxplayers' => '16',
					'gametype' => 'ConquestLarge0',
					'map' => 'MP_012',
					'roundsplayed' => '0',
					'roundstotal' => '2',
					'targetscore' => '0',
					'online' => '',
					'ranked' => 'true',
					'punkbuster' => 'true',
					'password' => 'false',
					'uptime' => '5148',
					'roundtime' => '455',
					'teams' => array(
						array('id' => 1, 'tickets' => '47'),
						array('id' => 2, 'tickets' => '54'),
					)
				)
			),
			
			array(
				array('OK','BigBrotherBot #2', '0', '16', 'ConquestLarge0', 'MP_012', '0', '2', '3', '47', '54', '87', '0', '', 'true', 'true', 'false', '5148', '455'),
				array(
					'dedicated' => 'true',
					'mod' => 'false',
					'hostname' => 'BigBrotherBot #2',
					'numplayers' => '0',
					'maxplayers' => '16',
					'gametype' => 'ConquestLarge0',
					'map' => 'MP_012',
					'roundsplayed' => '0',
					'roundstotal' => '2',
					'targetscore' => '0',
					'online' => '',
					'ranked' => 'true',
					'punkbuster' => 'true',
					'password' => 'false',
					'uptime' => '5148',
					'roundtime' => '455',
					'teams' => array(
						array('id' => 1, 'tickets' => '47'),
						array('id' => 2, 'tickets' => '54'),
						array('id' => 3, 'tickets' => '87'),
					)
				)
			),
		
			array(
				array('OK','BigBrotherBot #2', '0', '16', 'ConquestLarge0', 'MP_012', '0', '2', '4', '47', '54', '87', '21', '0', '', 'true', 'true', 'false', '5148', '455'),
				array(
					'dedicated' => 'true',
					'mod' => 'false',
					'hostname' => 'BigBrotherBot #2',
					'numplayers' => '0',
					'maxplayers' => '16',
					'gametype' => 'ConquestLarge0',
					'map' => 'MP_012',
					'roundsplayed' => '0',
					'roundstotal' => '2',
					'targetscore' => '0',
					'online' => '',
					'ranked' => 'true',
					'punkbuster' => 'true',
					'password' => 'false',
					'uptime' => '5148',
					'roundtime' => '455',
					'teams' => array(
						array('id' => 1, 'tickets' => '47'),
						array('id' => 2, 'tickets' => '54'),
						array('id' => 3, 'tickets' => '87'),
						array('id' => 4, 'tickets' => '21'),
					)
				)
			),

			array(
				array('OK', 'i3D.net - BigBrotherBot #3 (FR)', '0', '16', 'SquadDeathMatch0', 'MP_011', '0', '1', '4', '0', '0', '0', '0', '50', '', 'true', 'true', 'false', '494866', '5'),
				array(
					'dedicated' => 'true',
					'mod' => 'false',
					'hostname' => 'i3D.net - BigBrotherBot #3 (FR)',
					'numplayers' => '0',
					'maxplayers' => '16',
					'gametype' => 'SquadDeathMatch0',
					'map' => 'MP_011',
					'roundsplayed' => '0',
					'roundstotal' => '1',
					'targetscore' => '50',
					'online' => '',
					'ranked' => 'true',
					'punkbuster' => 'true',
					'password' => 'false',
					'uptime' => '494866',
					'roundtime' => '5',
					'teams' => array(
						array('id' => 1, 'tickets' => '0'),
						array('id' => 2, 'tickets' => '0'),
						array('id' => 3, 'tickets' => '0'),
						array('id' => 4, 'tickets' => '0'),
					)
				)
			),

			
			
		
		);
	}


	/**
	 * get a ReflectionMethod object for given method name of class GameQ_Protocols_Bf3
	 * and makes it acccessible.
	 * This allows to invode protected or private methods.
	 *
	 * @param string $name method name
	 * @throws ReflectionException if class or method does not exists
	 * @return RelectionMethod
	 */
	protected static function getMethod($name)
	{
		$class = new ReflectionClass('GameQ_Protocols_Bf3');
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method;
	}



	/**
	 * Run tests with expected behavior of BF3 servers before release R9.
	 * @dataProvider provider_process_status_pre_R9
	 */
	public function test_process_status_pre_R9($words, $expected)
	{
		$decode_status_response_method = self::getMethod('decode_status_response');
		$bf3 = new GameQ_Protocols_Bf3();
		$gameq_result = $decode_status_response_method->invoke(NULL, $words);
		$this->assertEquals($expected, $gameq_result->fetch());
	}



}