<?php
/**
 * Tests for security in the b2evo package.
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
	 * Below are the valid entry points.
	 * These include files that properly initalize the config before doing anything (config proxies), thus making sure no
	 * hazardous variables can be injected with register_globals = On.
	 *
	 * @var array A list of files, relative to $basepath, which do not need a "defined(EVO_MAIN_INIT)" check.
	 */
	var $entry_points = array(
		'conf/_config.php',						// okay because it is the config. Special case.
		'cron/cron_exec.php',
		'cron/getmail.php',
		'cron/mms.php',
		'htsrv/async.php',
		'htsrv/call_plugin.php',
		'htsrv/comment_post.php',
		'htsrv/getfile.php',
		'htsrv/login.php',
		'htsrv/message_send.php',
		'htsrv/profile_update.php',
		'htsrv/register.php',
		'htsrv/subs_update.php',
		'htsrv/trackback.php',
		'htsrv/viewfile.php',
		'inc/_connect_db.inc.php',		// okay because it starts by loading the config; this is not normalized behaviour though! TODO.
		'install/index.php',
		'install/phpinfo.php',
		'xmlsrv/atom.comments.php',
		'xmlsrv/atom.php',
		'xmlsrv/rdf.comments.php',
		'xmlsrv/rdf.php',
		'xmlsrv/rss.comments.php',
		'xmlsrv/rss.php',
		'xmlsrv/rss2.comments.php',
		'xmlsrv/rss2.php',
		'xmlsrv/xmlrpc.php',
	);

	/**
	 * A list of files which need a check for EVO_CONFIG_LOADED rather than EVO_MAIN_INIT.
	 * @var array
	 */
	var $init_files = array(
		'conf/_admin.php',
		'conf/_advanced.php',
		'conf/_application.php',
		'conf/_basic_config.php',
		'conf/_config_TEST.php',
		'conf/_formatting.php',
		'conf/_icons.php',
		'conf/_locales.php',
		'conf/_overrides_TEST.php',
		'conf/_stats.php',
		'conf/_upgrade.php',
		'inc/_blog_main.inc.php',
		'inc/_main.inc.php',
	);

	function SecurityChecksTestCase()
	{
		$this->EvoUnitTestCase( 'Security tests' );
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
			$r = array_merge( $r, $this->get_files_without_symlinks($dir));
		}
		return $r;
	}


	function testDefinedOrDie()
	{
		global $basepath;

		$search = "if\( \s* ! \s* defined\( \s* 'EVO_MAIN_INIT' \s* \) \s* \) \s* die\( .*? \);";
		$search_init = "if\( \s* ! \s* defined\( \s* 'EVO_CONFIG_LOADED' \s* \) \s* \) \s* die\( .*? \);";
		# $search_both = "if\( \s* ! \s* defined\( \s* 'EVO_MAIN_INIT' \s* ) \s* && \s* ! \s* defined( \s* 'EVO_CONFIG_LOADED' \s* ) \s* ) die\( .*? \);";

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

			if( in_array( substr($filename, strlen($basepath)), $this->init_files) )
			{ // file is an init file:
				if( ! preg_match( '~^<\?php \s* /\* .*? \*/ \s* '.$search_init.'~isx', substr($buffer, $pos_phptag) ) )
				{
					$badfiles[] = $filename;
				}
			}
			else
			{
				if( ! preg_match( '~^<\?php \s* /\* .*? \*/ \s* '.$search.'~isx', substr($buffer, $pos_phptag) ) )
				{
					$badfiles[] = $filename;
				}
			}
		}

		if( ! empty($badfiles) )
		{
			echo '<h2>Files which seem to miss the check for defined(EVO_MAIN_INIT/EVO_CONFIG_LOADED)</h2>';
			echo "\n<ul><li>\n";
			echo implode( "\n</li><li>\n", $badfiles );
			echo "\n</li></ul>\n";
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
