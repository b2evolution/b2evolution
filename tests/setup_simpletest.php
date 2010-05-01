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
	<p>This will setup the simpletest framework for b2evolution, by unzipping the simpletest snapshot
	<small>(<a href="http://evocms.cvs.sourceforge.net/viewvc/*checkout*/evocms/b2evolution/tests/simpletest.tar.bz2?revision=1.1">link to the CVS HEAD (most current) version</a>)</small> shipped with b2evolution.</p>

	<p><small>This snapshot is provided for your convenience and gets packaged from <a href="http://github.com/blueyed/simpletest">http://github.com/blueyed/simpletest</a> (basically upstream trunk with useful/required patches)</small></p>

<?php
if( $action == 'unzip_simpletest' )
{
	echo '<h1>Unzipping...</h1>';

	$target_dir = dirname(__FILE__);
	$source_file = 'simpletest.tar.bz2';

	# Test if "tar" exists
	exec( 'tar --version', $output, $return );
	if( $return !== 0 )
	{
		echo '<p><strong>ERROR: The "tar" binary is not installed on your system. Aborting.</strong></p>';
	}
	elseif( ! is_dir($target_dir) || ! is_writable($target_dir) )
	{
		printf('<p><strong>ERROR: Target directory (%s) is not writable (for me).</strong></p>', htmlspecialchars($target_dir));
	}
	else
	{
		// Change to target dir (instead of "-C") and use filename without path for tar in cygwin
		chdir($target_dir);
		$command = 'tar xjv -f '.escapeshellarg($source_file).' 2>&1';
		exec( $command, $output, $return );
		if( $return !== 0 )
		{ // Error
			echo '<p><strong>An error occured (exit code '.$return.')!</strong></p>';
			echo '<p><small>Command: '.htmlspecialchars($command).'</small></p>';
		}
		else
		{ // Success
			echo '<p class="success">The simpletest framework has been extracted successfully.</p>';
			echo '<p><a href="../">Now go testing...</a></p>'; # link to current dir (index.php is not present in e.g. evocore/blogs/)
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
