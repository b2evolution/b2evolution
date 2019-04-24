<?php
if( ! defined('EVODIR') )
{
	require dirname(__FILE__).'/config.php';
}
require EVODIR.'inc/_main.inc.php';
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
	<small>(<a href="https://github.com/simpletest/simpletest/releases/latest">link to the GITHUB (most current) version</a>)</small> shipped with b2evolution.</p>

	<p><small>This snapshot is provided for your convenience and gets packaged from <a href="https://github.com/simpletest/simpletest">https://github.com/simpletest/simpletest</a> (basically upstream trunk with useful/required patches)</small></p>

<?php
if( $action == 'unzip_simpletest' )
{
	echo '<h1>Unzipping...</h1>';

	$target_dir = dirname(__FILE__);
	$source_file = 'simpletest.tar.gz';

	# Test if "tar" exists
	exec( 'tar --version', $output, $return );
	if( $return !== 0 )
	{
		echo '<p><strong>ERROR: The "tar" binary is not installed on your system.</strong></p>';
	}
	else
	{
		$commands[] = 'tar xjv -f '.escapeshellarg($source_file).' 2>&1';
		$commands[] = 'mv simpletest-1.1.7/* simpletest';
		$commands[] = 'rm -R simpletest-1.1.7';
	}
	
	if( empty($commands) && @file_exists( 'C:\Program Files\7-Zip\7z.exe' ) )
	{	// 7-Zip is installed, let's use it
		echo '<p><strong>GOOD NEWS! "7-Zip" is found, we\'ll use it instead.</strong></p>';
		
		$source_bz = $source_file;
		$source_tar = str_replace( '.bz2', '', $source_file );

		// Extract the temporary .tar file
		$commands[] = '"C:\Program Files\7-Zip\7z.exe" e '.escapeshellarg($target_dir.'/'.$source_bz).' -y -o'.escapeshellarg($target_dir);

		// Extract the files
		$commands[] = '"C:\Program Files\7-Zip\7z.exe" e '.escapeshellarg($target_dir.'/'.$source_tar).' -y -o'.escapeshellarg($target_dir).'/simpletest';
	}
	
	if( ! is_dir($target_dir) || ! is_writable($target_dir) )
	{
		printf('<p><strong>ERROR: Target directory (%s) is not writable (for me).</strong></p>', htmlspecialchars($target_dir));
	}
	elseif( ! empty($commands) )
	{
		// Change to target dir (instead of "-C") and use filename without path for tar in cygwin
		chdir($target_dir);
		foreach( $commands as $cmd )
		{
			exec( $cmd, $output, $return );
		}

		if( !empty($source_tar) )
		{	// Get rid of the temporary .tar file
			unlink($target_dir.'/'.$source_tar);
		}

		if( $return !== 0 )
		{ // Error
			echo '<p><strong>An error occured (exit code '.$return.')!</strong></p>';
			echo '<p><small>Command: '.implode('<br />', $commands).'</small></p>';
		}
		else
		{ // Success
			echo '<p class="success">The simpletest framework has been extracted successfully.<br />Target directory: '.$target_dir.'/simpletest</p>';
			echo '<p><a href="../">Now go testing...</a></p>'; # link to current dir (index.php is not present in e.g. evocore/blogs/)
		}
		echo '<p><strong>Command output:</strong></p>';
		echo '<ul style="font-size:smaller"><li>'.implode('</li><li>', $output).'</li></ul>';
	}
	else
	{
		echo '<p>You will need to manually extract simpletest files and folders from <b>simpletest.tar.bz2</b> into /test/simpletest directory.</p>';
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