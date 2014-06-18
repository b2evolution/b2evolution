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
class ItemFuncsTestCase extends EvoMockDbUnitTestCase
{
	var $mocked_DB_methods = array('get_results');

	function __construct()
	{
		parent::__construct( 'Item functions test' );
	}


	/**
	 * Test {@link urltitle_validate()}
	 */
	function test_urltitle_validate()
	{
		global $current_locale;

		if( ! can_convert_charsets( 'ISO-8859-1', 'UTF-8' ) )
		{
			echo 'Skipping tests (cannot convert charsets)...<br />', "\n";
			return;
		}

		$this->MockDB->returns('get_results', array());

		// Make sure all locales are loaded
		locales_load_available_defs();

		$saved_current_locale = $current_locale;

		// For ISO-8859-1:
		$this->change_global('evo_charset', 'ISO-8859-1'); // this will trigger "ä" => "ae".. (since iconv appears to handle "ä" in latin1 different to "ä" in utf8 - for locale "en-US")
		foreach( array(
					//     arg1               arg2                    expected result
					array( '  ', ' :: çà c\'est "VRAIMENT" tôa! ', 'ca-c-est-vraiment-toa' ),
					array( '  ', ' :: çà c\'est_tôa! ', 'ca-c-est_toa' ),
					array( '  ', ' :: çà * c\'est_tôa! * *', 'ca-c-est_toa' ),
					array( '', 'La différence entre abbr...-452', 'la-difference-entre-abbr-452' ),
					array( '', 'La différence entre abbr_452', 'la-difference-entre-abbr-452' ),
					array( '', 'La subtile différence abbr..._452', 'la-subtile-difference-abbr-452' ),
					array( '', 'Äöüùé', 'aeoeueue' ),
				) as $test )
		{
			$this->assertEqual( urltitle_validate( $test[0], convert_charset( $test[1], 'ISO-8859-1', 'UTF-8' ) ), $test[2] );
		}

		// For UTF-8:
		$this->change_global('evo_charset', 'UTF-8');
		foreach( array(
					/* The last element of each array is the expected result, all other elements are
					 * arguments for urltitle_validate().
					 */
					array( '', 'Äöüùé', 0, false, 'post_urltitle', 'post_ID', 'T_items__item', 'de-DE', 'aeoeueue' ),
					array( '', 'Založte si svůj vlastní blog za pomoci TextPattern', 'zalozte-si-svuj-vlastni-blog' ),			// Max 5 words
					array( '', 'Zalo&#382;te si sv&#367;j vlastní blog za pomoci TextPattern', 'zalozte-si-svuj-vlastni-blog' ),	// Maximum 5 words
					array( '', 'русский текст   в ссылках', 'russkij-tekst-v-ssylkax', 'ru-RU' ),
					array( '', 'Äöüùé', 'aouue' ),
				) as $test )
		{
			if( isset($test[4]) )
			{	// Special case for de-DE locale

				// This test is broken
				//$this->assertEqual( call_user_func_array( 'urltitle_validate', array_slice( $test, 0, -1 ) ), array_pop( $test ) );
			}
			else
			{
				if( isset($test[3]) )
				{	// Check transliteration for specified locale
					$current_locale = $test[3];
				}
				$this->assertEqual( urltitle_validate( $test[0], $test[1]), $test[2] );

				$current_locale = $saved_current_locale; // restore
			}
		}
	}


	function test_urltitle_validate_use_current()
	{
		$this->MockDB->returnsAt(0, 'get_results', array(
			array('post_urltitle' => 'foo', 'post_ID' => 1),
			array('post_urltitle' => 'foo-1', 'post_ID' => 1),
		));
		$this->assertEqual(
			urltitle_validate('', 'foo', 1), 'foo'
		);
	}


	function test_urltitle_validate_use_current_numbered()
	{
		$this->MockDB->returns('get_results', array(
			array('post_urltitle' => 'foo', 'post_ID' => 1),
			array('post_urltitle' => 'foo-1', 'post_ID' => 1),
		));

		$this->assertEqual(urltitle_validate('', 'foo-5'), 'foo-5');

		$this->assertEqual(
			urltitle_validate('', 'foo-1', 1), 'foo-1'
		);
	}


	function test_urltitle_validate_use_highest_number()
	{
		$this->MockDB->returns('get_results', array(
			array('post_urltitle' => 'foo', 'post_ID' => 1),
			array('post_urltitle' => 'foo-1', 'post_ID' => 2),
			array('post_urltitle' => 'foo-2', 'post_ID' => 1),
		));

		$this->assertEqual(
			urltitle_validate('', 'foo-1', 1), 'foo-2'
		);
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