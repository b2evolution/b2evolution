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
	const slow_testcase = true;

	/**
	 * Below are the valid entry points.
	 * These include files that properly initalize the config before doing anything (config proxies), thus making sure no
	 * hazardous variables can be injected with register_globals = On.
	 *
	 * @var array A list of files which do not need a "defined(EVO_MAIN_INIT)" check.
	 */
	var $entry_points = array(
		'~cron/.+\.php~',
		'~xmlsrv/.+\.php~',
		'~conf/_config\.php~',				// okay because it is the config. Special case.
		'~inc/_connect_db\.inc\.php~',		// okay because it starts by loading the config; this is not normalized behaviour though! TODO.
		'~install/index\.php~',
		'~install/phpinfo.php~',
	);

	/**
	 * A list of files which we should ignore
	 * @var array
	 */
	var $ignore_list = array(
		'~/.+\.locale\.php~',
		'~htsrv/.+\.php~',
		'~conf/_overrides_TEST\.php~',
		'~install/_version\.php~',
		'~inc/sessions/views/_stats_useragents\.view\.php~',
		'~tinymce_plugin/tiny_mce/.+\.php~',
	);

	/**
	 * A list of files which need a check for EVO_CONFIG_LOADED rather than EVO_MAIN_INIT.
	 * @var array
	 */
	var $init_files = array(
		'/_*.init.php',
		'/_*.install.php',
		'inc/_init_*.inc.php',
		'conf/_*.php',
		'inc/_blog_main.inc.php',
		'inc/_main.inc.php',
		'inc/locales/_locale.funcs.php',
		'inc/sessions/model/_search_engines.php',
		'inc/widgets/_widgets.funcs.php',
	);

	function __construct()
	{
		parent::__construct( 'Security tests' );
	}


	function testDefinedOrDie()
	{
		global $basepath;

		$search = "if\( \s* ! \s* defined\( \s* 'EVO_MAIN_INIT' \s* \) \s* \) \s* die\( .*? \);";
		$search_init = "if\( \s* ! \s* defined\( \s* 'EVO_CONFIG_LOADED' \s* \) \s* \) \s* die\( .*? \);";
		# $search_both = "if\( \s* ! \s* defined\( \s* 'EVO_MAIN_INIT' \s* ) \s* && \s* ! \s* defined( \s* 'EVO_CONFIG_LOADED' \s* ) \s* ) die\( .*? \);";

		$files = $this->get_files_without_symlinks($basepath);
		$badfiles = array();
		$badfiles_init = array();
		$badfiles_main = array();

		foreach( $files as $filename )
		{
			if( preg_filter( $this->entry_points, 'whatever', $filename ) || preg_filter( $this->ignore_list, 'whatever', $filename ) )
			{ // file is an entry point
				continue;
			}

			$buffer = file_get_contents($filename);

			$pos_phptag = strpos($buffer, '<?php');
			if( $pos_phptag === false )
			{ // not a PHP file
				continue;
			}

			$phpfiles['name'][] = $filename;
			$phpfiles['data'][] = $buffer;
		}

		$arr_init = array();
		foreach( $this->init_files as $filters )
		{
			$arr_init += preg_grep( '~'.str_replace('\*', '.+', quotemeta($filters)).'~isx', $phpfiles['name'] );
		}
		$arr_main = array_diff( $phpfiles['name'], $arr_init );

		foreach( $arr_init as $k=>$filename )
		{
			if( ! preg_match( '~'.$search_init.'~isx', $phpfiles['data'][$k] ) )
			{
				$badfiles_init[] = $filename;
			}
		}

		foreach( $arr_main as $k=>$filename )
		{
			if( ! preg_match( '~'.$search.'~isx', $phpfiles['data'][$k] ) )
			{
				$badfiles_main[] = $filename;
			}
		}

		if( ! empty($badfiles_init) )
		{
			echo '<h2>Files which seem to miss the check for defined(EVO_CONFIG_LOADED)</h2>';
			echo "\n<ul><li>\n";
			echo implode( "\n</li><li>\n", $badfiles_init );
			echo "\n</li></ul>\n";
		}

		if( ! empty($badfiles_main) )
		{
			echo '<h2>Files which seem to miss the check for defined(EVO_MAIN_INIT)</h2>';
			echo "\n<ul><li>\n";
			echo implode( "\n</li><li>\n", $badfiles_main );
			echo "\n</li></ul>\n";
		}

		$this->assertFalse( $badfiles_init + $badfiles_main );
	}
}



if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new SecurityChecksTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
