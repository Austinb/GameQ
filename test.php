<?php
error_reporting(E_ALL);

echo '<pre>';

set_time_limit(300);

require realpath(dirname(__FILE__)) . '/GameQ/Autoloader.php';


$gq = new GameQ();
/*$gq->addServer(array(
        'id' => 1,
		'type' => 'source',
        'host' => '69.162.109.181:27015'

));*/

$gq->addServer(array(
        //'id' => 2,
        'type' => 'source',
        'host' => '64.74.97.72:27017'

));

$results = $gq->process(); // Returns an array of results

print_r($results);

exit;