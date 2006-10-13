<?php
/**
 * Tests for {@link UserCache}.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


global $inc_path;

require_once( $inc_path.'MODEL/users/_usercache.class.php' );


/**
 * @package tests
 */
class UserCacheTestCase extends MockDbUnitTestCase
{
	var $mocked_DB_methods = array( 'get_row' );

	function UserCacheTestCase()
	{
		$this->MockDbUnitTestCase( 'User cache tests' );
	}


	/**
	 * Test, if a user cached by login gets removed on clear()
	 */
	function test_get_by_login_and_remove()
	{
		$UserCache = new UserCache();

		$this->MockDB->expectCallCount( 'get_row', 2 );
		$this->MockDB->expectArguments( 'get_row', array( new WantedPatternExpectation('/SELECT \*\s+FROM T_users.*login/is'), '*', '*', '*' ), 'DB select ok.' );
		$UserCache->get_by_login( 'login' );

		$UserCache->clear();
		$UserCache->get_by_login( 'login' );
	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new UserCacheTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
