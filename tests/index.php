<?php
/**
 * This file implements a simple menu to call the simpletest tests.
 *
 * @package tests
 */

$action = isset($_GET['action']) ? $_GET['action'] : '';

require dirname(__FILE__).'/config.php';

if( empty($action) )
{ // display menu:
	require_once $inc_path.'MODEL/files/_file.funcs.php';
	?>

	<html>

	<head>
		<title>b2evolution simpletest framework</title>
		<meta name="robots" content="noindex, nofollow" />
	</head>

	<body>
		<h1>b2evolution simpletest framework</h1>

		<h2>evoCore tests</h2>
		<ul>
		<li><a href="index.php?action=core.all"><strong>All evoCore tests</strong></a></li>
		<?php
		foreach( get_filenames( dirname(__FILE__).'/blogs', true, false, $flat = true ) as $filename )
		{
			if( substr($filename, -15) != '.simpletest.php' )
				continue;

			$rel_path = substr($filename, strlen(dirname(__FILE__))+1);

			echo '<li><a href="'.$rel_path.'">'.$rel_path.'</a>';
		}
		?>
		</ul>

		<h2>Install tests</h2>
		<ul>
		<li><a href="install/"><strong>All install tests</strong></a></li>
		<?php
		foreach( get_filenames( dirname(__FILE__).'/install', true, false, $flat = true ) as $filename )
		{
			if( substr($filename, -15) != '.simpletest.php' )
				continue;

			$rel_path = substr($filename, strlen(dirname(__FILE__))+1);

			echo '<li><a href="'.$rel_path.'">'.$rel_path.'</a>';
		}
		?>
		</ul>

	</body>

	</html>

	<?php

	exit;
}


// ACTIONS:

switch( $action )
{
	case 'core.all':
		/**
		 * Include the All-Tests-Suite
		 */
		require( dirname(__FILE__).'/alltests.simpletest.php' );
		break;
}

?>
