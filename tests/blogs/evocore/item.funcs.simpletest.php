<?php
/**
 * Tests for item functions.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


global $inc_path;

require_once( $inc_path.'MODEL/items/_item.funcs.php' );


/**
 * @package tests
 */
class ItemFuncsTestCase extends EvoUnitTestCase
{
	function ItemFuncsTestCase()
	{
		$this->EvoUnitTestCase( 'Item functions test' );
	}


	/**
	 * Test {@link urltitle_validate()}
	 */
	function test_urltitle_validate()
	{
		global $evo_charset;

		$old_evo_charset = $evo_charset;
		$evo_charset = 'ISO-8859-1';
		$this->assertEqual( urltitle_validate( '  ', " :: çà c'est \"VRAIMENT\" tôa! " ), 'ca-c-est-vraiment-toa' );
		$this->assertEqual( urltitle_validate( '  ', " :: çà c'est_tôa! " ), 'ca-c-est_toa' );
		$this->assertEqual( urltitle_validate( '  ', " :: çà * c'est_tôa! * *" ), 'ca-c-est_toa' );
		$this->assertEqual( urltitle_validate( '', 'La différence entre acronym et abbr...-452' ), 'la-difference-entre-acronym-et-abbr-452' );
		$this->assertEqual( urltitle_validate( '', 'La différence entre acronym et abbr..._452' ), 'la-difference-entre-acronym-et-abbr-452' );
		$this->assertEqual( urltitle_validate( '', 'La différence entre acronym et abbr_452' ), 'la-difference-entre-acronym-et-abbr-452' );
		// Test length cropping
		$this->assertEqual( urltitle_validate( '', 'La subtile différence entre acronym et abbr..._452' ), 'la-subtile-difference-entre-acronym-et-a-452' );
		$this->assertEqual( urltitle_validate( '', 'La subtile différence entre acronym et abbr...-452' ), 'la-subtile-difference-entre-acronym-et-a-452' );

		if( ! can_convert_charsets('ISO-8859-1', 'UTF-8') || ! can_convert_charsets('UTF-8', 'ISO-8859-1') )
		{
			echo "Skipping tests (cannot convert charsets)...<br />\n";
			return;
		}

		$this->assertEqual( urltitle_validate('', 'Äöüùé'), 'aeoeueue' );

		$evo_charset = 'UTF-8';
		$this->assertEqual( urltitle_validate('', convert_charset('Äöüùé', 'UTF-8', 'ISO-8859-1')), 'aeoeueue' );

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
