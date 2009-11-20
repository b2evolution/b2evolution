<?php
/**
 * Tests for item functions.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


load_funcs('items/model/_item.funcs.php');


/**
 * @package tests
 */
class ItemFuncsTestCase extends EvoUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'Item functions test' );
	}


	/**
	 * Test {@link urltitle_validate()}
	 */
	function test_urltitle_validate()
	{
		global $evo_charset;

		if( ! can_convert_charsets( 'ISO-8859-1', 'UTF-8' ) )
		{
			echo 'Skipping tests (cannot convert charsets)...<br />', "\n";
			return;
		}

		$old_evo_charset = $evo_charset;

		// For ISO-8859-1:
		$evo_charset = 'ISO-8859-1';
		foreach( array(
					//     arg1               arg2                    expected result
					array( '  ', ' :: çà c\'est "VRAIMENT" tôa! ', 'ca-c-est-vraiment-toa' ),
					array( '  ', ' :: çà c\'est_tôa! ', 'ca-c-est_toa' ),
					array( '  ', ' :: çà * c\'est_tôa! * *', 'ca-c-est_toa' ),
					array( '', 'La différence entre acronym et abbr...-452', 'la-difference-entre-acronym-et-abbr-452' ),
					array( '', 'La différence entre acronym et abbr...-452', 'la-difference-entre-acronym-et-abbr-452' ),
					array( '', 'La différence entre acronym et abbr_452', 'la-difference-entre-acronym-et-abbr-452' ),
					array( '', 'La subtile différence entre acronym et abbr..._452', 'la-subtile-difference-entre-acronym-et-abbr-452' ),
					array( '', 'La subtile différence entre acronym et abbr..._452', 'la-subtile-difference-entre-acronym-et-abbr-452' ),
					array( '', 'Äöüùé', 'aeoeueue' ),
				) as $test )
		{
			$this->assertEqual( urltitle_validate( $test[0], convert_charset( $test[1], 'ISO-8859-1', 'UTF-8' ) ), $test[2] );
		}

		// For UTF-8:
		$evo_charset = 'UTF-8';
		foreach( array(
					/* The last element of each array is the expected result, all other elements are
					 * arguments for urltitle_validate().
					 */
					// We need to use a locale that contains German umlauts (de-DE) for this test:
					array( '', 'Äöüùé', 0, false, 'post_urltitle', 'post_ID', 'T_items__item', 'de-DE', 'aeoeueue' ),
					array( '', 'Založte si svůj vlastní blog za pomoci TextPattern', 'zalozte-si-svuj-vlastni-blog-za-pomoci-textpattern' ),
					array( '', 'Zalo&#382;te si sv&#367;j vlastní blog za pomoci TextPattern', 'zalozte-si-svuj-vlastni-blog-za-pomoci-textpattern' ),
				) as $test )
		{
			$this->assertEqual( call_user_func_array( 'urltitle_validate', array_slice( $test, 0, -1 ) ), array_pop( $test ) );
		}

		$evo_charset = $old_evo_charset;
	}


	/**
	 * Test {@link bpost_count_words()}.
	 */
	function test_bpost_count_words()
	{
		global $evo_charset;

		if( ! can_convert_charsets( 'ISO-8859-1', 'UTF-8' ) )
		{
			echo 'Skipping tests (cannot convert charsets)...<br />', "\n";
			return;
		}

		$old_evo_charset = $evo_charset;
		$evo_charset = 'ISO-8859-1';

		$this->assertEqual( bpost_count_words( convert_charset( 'eine gleichung wie 1 + 2 = 9 /', 'ISO-8859-1', 'UTF-8' ) ), 3 );
		$this->assertEqual( bpost_count_words( convert_charset( 'mixed with the 3 ümläuts: äää ööö üüü ÄÄÄ ÖÖÖ	ÜÜÜ', 'ISO-8859-1', 'UTF-8' ) ), 10 );

		$evo_charset = 'UTF-8';
		$this->assertEqual( bpost_count_words( 'möre (again 3) ümläüts... öö üü ää ÄÄ ÖÖ ÜÜ' ), 9 );
		$this->assertEqual( bpost_count_words( 'russian: Расширенные возможности - это удобный' ), 5 );
		$this->assertEqual( bpost_count_words( 'A versão foi apelidade de Tilqi, porque era aniversário dele. numbers: 42' ), 11 );
		$this->assertEqual( bpost_count_words( 'HTML tags -> <a href="http://b2evolution.net" target="_blank">visit b2evo!</a>. Some other chars: "\' \' " <<< < >>> > ``` -- versão удобный überladen' ), 10 ); 

		$evo_charset = $old_evo_charset;
	}
}

if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new ItemFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
