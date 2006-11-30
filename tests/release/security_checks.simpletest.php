<?php
/**
 * Tests for the {@link AbstractSettings} class.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../config.simpletest.php' );


/**
 * @package tests
 */
class SecurityChecksTestCase extends EvoUnitTestCase
{
	/**
	 * @var array A list of files, relative to $basepath, which do not need an "defined(EVO_MAIN_INIT)" check.
	 */
	var $entry_points = array(
		'inc/_main.inc.php',
		'inc/_blog_main.inc.php',
		'inc/_connect_db.inc.php',
		'inc/MODEL/settings/_locale.funcs.php',
		'inc/_misc/ext/_xmlrpc.php',
		'inc/_misc/ext/_xmlrpcs.php',
		'conf/_formatting.php',
		'conf/_config_TEST.php',
		'conf/_upgrade.php',
		'conf/_admin.php',
		'conf/_locales.php',
		'conf/_advanced.php',
		'conf/_basic_config.php',
		'conf/_overrides_TEST.php',
		'conf/_application.php',
		'conf/_icons.php',
		'conf/_config.php',
		'conf/_stats.php',
		'cron/cron_exec.php',
		'cron/mms.php',
  	'cron/getmail.php',
  	'htsrv/comment_post.php',
  	'htsrv/trackback.php',
  	'htsrv/call_plugin.php',
  	'htsrv/async.php',
  	'htsrv/login.php',
  	'htsrv/viewfile.php',
  	'htsrv/subs_update.php',
  	'htsrv/message_send.php',
  	'htsrv/profile_update.php',
  	'htsrv/getfile.php',
  	'htsrv/register.php',
  	'xmlsrv/atom.php',
  	'xmlsrv/rdf.php',
  	'xmlsrv/rss.comments.php',
  	'xmlsrv/rss2.php',
  	'xmlsrv/xmlrpc.php',
  	'xmlsrv/rss.php',
  	'xmlsrv/rss2.comments.php',
  	'xmlsrv/rdf.comments.php',
  	'xmlsrv/atom.comments.php',
  	'install/phpinfo.php',
  	'install/index.php',
	);

	function SecurityChecksTestCase()
	{
		$this->EvoUnitTestCase( 'Release tests' );
	}


	/**
	 * Get all files below a path, excluding symlinks.
	 * @return array
	 */
	function get_files_without_symlinks( $path, $pattern = '~\.(php|inc|html?)$~' )
	{
		$r = array();
		foreach( get_filenames( $path, false, true, true, false ) as $dir )
		{
			if( is_link($dir) )
			{
				continue;
			}

			// files:
			$files = get_filenames( $dir, true, false, true, false );
			if( $pattern )
			{
				foreach( $files as $k => $v )
				{
					if( ! preg_match( $pattern, $v ) )
					{ // Not a PHP file (include HTM(L), because it gets parsed sometimes, too)
						unset($files[$k]);
					}
				}
			}
			$r = array_merge( $r, $files );

			// subdirs:
			foreach( get_filenames( $dir, false, true, true, false ) as $subdir )
			{
				$r = array_merge( $r, $this->get_files_without_symlinks($subdir));
			}
		}
		return $r;
	}


	function testDefinedOrDie()
	{
		global $basepath;

		$search = "if\( \s* ! \s* defined\( \s* 'EVO_MAIN_INIT' \s* \) \s* \) \s* die\( .*? \);";

		$files = $this->get_files_without_symlinks($basepath);
		$badfiles = array();
		foreach( $files as $filename )
		{
			if( in_array( substr($filename, strlen($basepath)), $this->entry_points) )
			{ // file is an entry point
				continue;
			}

			$buffer = file_get_contents($filename);

			$pos_phptag = strpos($buffer, '<?php');
			if( $pos_phptag === false )
			{ // not a PHP file
				continue;
			}

			if( ! preg_match( '~^<\?php \s* /\* .*? \*/ \s* '.$search.'~isx', substr($buffer, $pos_phptag) ) )
			{
				$badfiles[] = $filename;
			}
		}

		if( ! empty($badfiles) )
		{
			echo '<h1>Files which seem to miss the check for defined(EVO_MAIN_INIT)</h1>';
			var_export( $badfiles );
		}
		$this->assertFalse( $badfiles );
	}
}



if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new SecurityChecksTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
