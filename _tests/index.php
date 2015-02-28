<?php
/**
 * This file implements a simple menu to call the simpletest tests.
 *
 * @package tests
 */

$action = isset($_GET['action']) ? $_GET['action'] : '';

if( empty($action) && isset($argv[0]) )
{ // CLI
	if( isset($argv[1]) )
		$action = $argv[1];
	else
		$action = 'all';
}

require dirname(__FILE__).'/config.php';

if( empty($action) )
{ // display menu:
	load_funcs('files/model/_file.funcs.php');
	?>

	<html>

	<head>
		<title>b2evolution simpletest framework</title>
		<meta name="robots" content="noindex, nofollow" />
	</head>

	<body>
		<h1>b2evolution simpletest framework</h1>

		<p style="color:red">SECURITY WARNING: This folder is for developers only and should NOT be part of the distribution and should NOT be uploaded to production servers.</p>

		<a href="index.php?action=all"><strong>All tests</strong></a>

		<h2>evoCore tests</h2>
		<ul>
		<li><a href="blogs/"><strong>All evoCore tests</strong></a></li>
		<?php
		$filenames = get_filenames( dirname(__FILE__).'/blogs', array('inc_dirs'=>false) );
		sort($filenames);
		foreach( $filenames as $filename )
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
		$filenames = get_filenames( dirname(__FILE__).'/install', array('inc_dirs'=>false) );
		sort($filenames);
		foreach( $filenames as $filename )
		{
			if( substr($filename, -15) != '.simpletest.php' )
				continue;

			$rel_path = substr($filename, strlen(dirname(__FILE__))+1);

			echo '<li><a href="'.$rel_path.'">'.$rel_path.'</a>';
		}
		?>
		</ul>


		<h2>General tests</h2>
		<ul>
		<li><a href="general/"><strong>All general tests</strong></a></li>
		<?php
		$filenames = get_filenames( dirname(__FILE__).'/general', array('inc_dirs'=>false) );
		sort($filenames);
		foreach( $filenames as $filename )
		{
			if( substr($filename, -15) != '.simpletest.php' )
				continue;

			$rel_path = substr($filename, strlen(dirname(__FILE__))+1);

			echo '<li><a href="'.$rel_path.'">'.$rel_path.'</a>';
		}
		?>
		</ul>

	<p><strong>NOTE: Some tests will create temporary test tables in your database (<?php echo htmlspecialchars($db_config['name'])?>); using the prefix: "<?php echo htmlspecialchars($evo_tests_tableprefix) ?>".</p>

	</body>

	</html>

	<?php

	exit;
}


// ACTIONS:

require_once( dirname(__FILE__).'/config.simpletest.php' );

/**
 * Our GroupTest
 */
$test = new EvoGroupTest( 'evo Tests Suite');


switch( $action )
{
	case 'all':
		$test->loadAllTests( dirname(__FILE__).'/blogs/' );
		$test->loadAllTests( dirname(__FILE__).'/general/' );
		$test->loadAllTests( dirname(__FILE__).'/install' );
		break;
}

$test->run( new EvoHtmlReporter(), new EvoTextReporter() );

?>