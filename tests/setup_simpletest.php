<?php
if( ! defined('EVODIR') )
{
	require dirname(__FILE__).'/config.php';
}
require EVODIR.'blogs/inc/_main.inc.php';
load_funcs('_core/_param.funcs.php');

param('action', 'string', '');
?>


<html>
<head>
<title>Setup simpletest for b2evolution</title>
</head>

<body>
	<h1>Setup simpletest for b2evolution</h1>
	<p>This will setup the simpletest framework for b2evolution, by unzipping
	the <a href="../simpletest.tar.bz2">simpletest.tar.bz2 snapshot</a> shipped with b2evolution.</p>

<?php
if( $action == 'unzip_simpletest' )
{
	echo '<h1>Unzipping...</h1>';

	$target_dir = dirname(__FILE__);
	$source_file = dirname(__FILE__).'/simpletest.tar.bz2';

	# Test if "tar" exists
	exec( 'tar --version', $output, $return );
	if( $return !== 0 )
	{
		echo '<p><strong>ERROR: The "tar" binary is not installed on your system. Aborting.</strong></p>';
	}
	elseif( ! is_dir($target_dir) || ! is_writable($target_dir) )
	{
		echo '<p><strong>ERROR: Target directory is not writable (for me).</strong></p>';
		echo '<p>Target directory: '.htmlspecialchars($target_dir).'</p>';
		break;
	}
	elseif( ! is_dir($target_dir) || ! is_writable($target_dir) )
	{
		echo '<p><strong>ERROR: Target directory is not writable (for me).</strong></p>';
		echo '<p>Target directory: '.htmlspecialchars($target_dir).'</p>';
		break;
	}
	else
	{
		$command = 'tar xjv -C '.escapeshellarg($target_dir).' -f '.escapeshellarg($source_file).' 2>&1';
		exec( $command, $output, $return );
		if( $return !== 0 )
		{ // Error
			echo '<p><strong>An error occured (exit code '.$return.')!</strong></p>';
			echo '<p><small>Command: '.htmlspecialchars($command).'</small></p>';
		}
		else
		{ // Success
			echo '<p class="success">The simpletest framework has been extracted successfully.</p>';
			echo '<p><a href="index.php">Now go testing...</a></p>';
		}
		echo '<p><strong>Command output:</strong></p>';
		echo '<ul style="font-size:smaller"><li>'.implode('</li><li>', $output).'</li></ul>';
	}
}
else
{ // no action:
?>
	<a href="?action=unzip_simpletest">OK, do it for me.</a>
<?php
}
?>
</body>

</html>
