<?php
// Read ini file
$ini = parse_ini_file('GameQ/games.ini', true);
foreach ($ini as $key => &$entry) $entry['id'] = $key;
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
        </style>
    </head>
    <body>
    <h1>GameQ - Supported Games (<?php echo count($ini); ?>)</h1>
    <table>
    <thead>
        <tr>
            <td>Game name</td>
            <td>Identifier</td>
            <td>Default port</td>
        </tr>
    </thead>
	<tbody>
<?php
function namecmp($a, $b) {

    $a = $a['name'];
    $b = $b['name'];

    if ($a == $b) return 0;
    
    return ($a < $b) ? -1 : 1;
}
    
usort($ini, 'namecmp');
    
foreach ($ini as $key => $entry) {
    $cls = empty($cls) ? ' class="uneven"' : '';
    printf("<tr%s><td>%s</td><td>%s</td><td>%s</td></tr>\n", $cls, htmlentities ($entry['name']), $entry['id'], $entry['port']);
}
?>
    </tbody>
    </table>
</body>
</html>
