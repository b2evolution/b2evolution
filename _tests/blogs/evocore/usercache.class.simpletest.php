<?php
/**
 * Tests for {@link UserCache}.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


load_class( 'users/model/_usercache.class.php', 'UserCache' );


/**
 * @package tests
 */
class UserCacheTestCase extends EvoMockDbUnitTestCase
{
	var $mocked_DB_methods = array( 'get_row' );

	function __construct()
	{
		parent::__construct( 'User cache tests' );
	}


	/**
	 * Test, if a user cached by login gets removed on clear()
	 */
	function test_get_by_login_and_remove()
	{
		$UserCache = new UserCache();

		$this->MockDB->expectCallCount( 'get_row', 2 );
		$this->MockDB->expect( 'get_row', array( new PatternExpectation('/SELECT \*\s+FROM T_users.*login/is'), '*', '*', '*' ), 'DB select ok.' );
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
