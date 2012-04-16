<?php
// Include the main class file
require '../GameQ.php';

// Define the protocols path
$protocols_path = GAMEQ_BASE."gameq/protocols/";

// Grab the dir with all the classes available
$dir = dir($protocols_path);

$protocols = array();

// Now lets loop the directories
while (false !== ($entry = $dir->read()))
{
	if(!is_file($protocols_path.$entry))
	{
		continue;
	}

	// Figure out the class name
	$class_name = 'GameQ_Protocols_'.ucfirst(pathinfo($entry, PATHINFO_FILENAME));

	// Lets get some info on the class
	$reflection = new ReflectionClass($class_name);

	// Check to make sure we can actually load the class
	if(!$reflection->IsInstantiable())
	{
		continue;
	}

	// Load up the class so we can get info
	$class = new $class_name;

	// Add it to the list
	$protocols[$class->name()] = array(
		'name' => $class->name_long(),
		'port' => $class->port(),
		'state' => $class->state(),
	);

	// Unset the class
	unset($class);
}

// Close the directory
unset($dir);

ksort($protocols);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <title>GameQ - Supported Games</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <style type="text/css">
            * {
                font-size: 9pt;
                font-family: Verdana, sans-serif;
            }
            h1 {
                font-size: 12pt;
            }
            table {
                border: 1px solid #000;
                background-color: #DDD;
                border-spacing:1px 1px;
            }
            table thead {
                font-weight: bold;
                background-color: #CCC;
            }
            table tr.uneven td {
                background-color:#FFF;
            }
            table td {
                padding: 5px 8px;
            }
            table tbody {
                background-color: #F9F9F9;
            }
            green {
            	font-color: #000000;
            }
        </style>
    </head>
    <body>
    <h1>GameQ - Supported Games (<?php echo count($protocols); ?>)</h1>
    <table>
    <thead>
        <tr>
            <td>Game Name</td>
            <td>GameQ Identifier</td>
            <td>Default port</td>
            <td>State</td>
        </tr>
    </thead>
	<tbody>
<?php

foreach ($protocols AS $gameq => $info)
{


	switch($info['state'])
	{
		case 1:
			$state = 'Testing';
			break;

		case 2:
			$state = 'Beta';
			break;

		case 3:
			$state = 'Stable';
			break;

		case 4:
			$state = 'Deprecated';
			break;
	}

    $cls = empty($cls) ? ' class="uneven"' : '';
    printf("<tr%s><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n", $cls,
    	htmlentities($info['name']),
    	$gameq,
    	$info['port'],
    	$state
    	);
}
?>
    </tbody>
    </table>
</body>
</html>
