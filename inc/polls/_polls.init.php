<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package polls
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Minimum PHP version required for messaging module to function properly
 */
$required_php_version[ 'polls' ] = '5.0';

/**
 * Minimum MYSQL version required for messaging module to function properly
 */
$required_mysql_version[ 'polls' ] = '5.0.3';

/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases'] = array_merge( $db_config['aliases'], array(
		'T_polls__question' => $tableprefix.'polls__question',
		'T_polls__option'   => $tableprefix.'polls__option',
		'T_polls__answer'   => $tableprefix.'polls__answer',
	) );


/**
 * polls_Module definition
 */
class polls_Module extends Module
{
	/**
	 * Do the initializations. Called from in _main.inc.php.
	 * This is typically where classes matching DB tables for this module are registered/loaded.
	 *
	 * Note: this should only load/register things that are going to be needed application wide,
	 * for example: for constructing menus.
	 * Anything that is needed only in a specific controller should be loaded only there.
	 * Anything that is needed only in a specific view should be loaded only there.
	 */
	function init()
	{
		$this->check_required_php_version( 'polls' );
	}


	/**
	 * Handle messaging module htsrv actions
	 */
	function handle_htsrv_action()
	{
		global $Session, $Messages;

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'polls' );

		if( ! is_logged_in() )
		{	// User must be logged in
			debug_die( 'User must be logged in to proceed with polls voting!' );
		}

		// Load classes:
		load_class( 'polls/model/_poll.class.php', 'Poll' );
		load_class( 'polls/model/_poll_option.class.php', 'PollOption' );

		$action = param_action();

		switch( $action )
		{
			case 'vote':
				// Vote on poll:
				$poll_ID = param( 'poll_ID', 'integer', true );
				$poll_option_ID = param( 'poll_answer', 'integer', 0 );

				if( empty( $poll_option_ID ) )
				{	// The poll option must be selected:
					$Messages->add( T_('Please select an answer for the poll.'), 'error' );
					break;
				}

				// Check if the requested poll is correct:
				$PollCache = & get_PollCache();
				$Poll = & $PollCache->get_by_ID( $poll_ID, false, false );
				if( ! $Poll )
				{	// The requested poll doesn't exist in DB:
					$Messages->add( T_('Wrong poll request!'), 'error' );
					break;
				}

				// Check if the requested poll option is correct:
				$PollOptionCache = & get_PollOptionCache();
				$PollOption = & $PollOptionCache->get_by_ID( $poll_option_ID, false, false );
				if( ! $PollOption || $PollOption->pqst_ID != $Poll->ID )
				{	// The requested poll option doesn't exist in DB:
					$Messages->add( T_('Wrong poll request!'), 'error' );
					break;
				}

				// Vote on the poll by current User:
				if( $PollOption->vote() )
				{	// Successful voting:
					$Messages->add( T_('You have been voted on the poll.'), 'success' );
				}
				break;
		}
	}
}

$polls_Module = new polls_Module();

?>