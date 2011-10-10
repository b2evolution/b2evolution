<?php
/**
 * This file updates the current user's item subscriptions!
 *
 * @todo fp> move this to spmething like action.php or call_module.php
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo
 *
 * @version $Id$
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

global $DB, $Session, $Messages;

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( 'itemsubs' );

// Get params
$item_ID = param( 'p', 'integer', true );
param( 'notify', 'integer', 0 );
param( 'type', 'string', '' );

/**
 * Basic security checks:
 */
if( ! is_logged_in() )
{ // must be logged in!
	bad_request_die( T_('You are not logged in.') );
}

if( $demo_mode && ($current_User->ID == 1 || $current_User->login == 'demouser') )
{ // don't allow subscribe in demo mode
	bad_request_die( 'Demo mode: you can\'t edit the admin/demouser profile!<br />[<a href="javascript:history.go(-1)">'
				. T_('Back to blog') . '</a>]' );
}

// Set item subscription type
$isub_type = 'isub_comments';
if( $type == 'attend' )
{
	if( !isset( $GLOBALS[ 'events_Module' ] ) )
	{
		// fp>asimo: can we move the whole thing to htsrv/action.php instead?
		// action.php would expect a param called module and then it would call the
		// approriate module with the method handle_htsrv_action()
		bad_request_die( 'Event attending is not supported!' );
	}
	$isub_type = 'isub_attend';
}

if( ( $notify < 0 ) || ( $notify > 1 ) )
{ // Invalid notify param. It should be 0 for unsubscribe and 1 for subscribe.
	$Messages->add( 'Invalid params!', 'error' );
}

switch( $isub_type )
{
	case 'isub_comments':
		if( ! is_email( $current_User->get( 'email' ) ) )
		{ // user doesn't have a valid email address
			$Messages->add( T_( 'Your email address is invalid. Please set your email address first.' ), 'error' );
		}

		if( $Messages->has_errors() )
		{ // errors detected
			header_redirect();
			// already exited here
		}

		if( set_user_isubscription( $current_User->ID, $item_ID, $notify ) )
		{
			if( $notify == 0 )
			{
				$Messages->add( T_( 'You have successfully unsubscribed.' ), 'success' );
			}
			else
			{
				$Messages->add( T_( 'You have successfully subscribed to notifications.' ), 'success' );
			}
		}
		else
		{
			$Messages->add( T_( 'Could not subscribe to notifications.' ), 'error' );
		}
		break;

	case 'isub_attend':
		// Events are supported

		if( $notify == 1 )
		{ // Register type can be 'attend' or 'waitlist', it must be set
			$reg_type = param( 'reg_type', 'string', true );
		}
		else
		{
			$reg_type = 'attend';
		}

		$new_attendant = param( 'new_attendee', 'string', NULL );
		if( !empty( $new_attendant ) )
		{
			$current_User->check_perm( 'users', 'edit', true );
			$UserCache = & get_UserCache();
			$new_attendant_User = $UserCache->get_by_login( $new_attendant );
			if( $new_attendant_User )
			{
				$user_ID = $new_attendant_User->ID;
			}
			else
			{
				$Messages->add( sprintf( T_( 'User %s not found.' ), $new_attendant ), 'error' );
			}
		}
		elseif( $new_attendant !== NULL )
		{
			$Messages->add( T_( 'Please select a user.' ), 'error' );
		}
		else
		{
			$user_ID = param( 'u', 'integer', NULL );
		}

		if( $Messages->has_errors() )
		{ // errors detected
			header_redirect();
			// already exited here
		}

		if( empty( $user_ID ) )
		{ // user ID is empty, current User subscribed/unsubscribed
			$user_ID = $current_User->ID;
			$success_unsub_message = T_( 'You have successfully unsubscribed.' );
			if( $reg_type == 'attend' )
			{
				$success_sub_message = T_( 'You have successfully subscribed to attend this event.' );
			}
			else
			{
				$success_sub_message = T_( 'You have successfully subscribed on the waiting list.' );
			}
		}
		else
		{
			$success_unsub_message = T_( 'User was successfully unsubscribed.' );
			$success_sub_message = T_( 'User was successfully subscribed to attend this event.' );
		}

		if( set_user_attendee( $user_ID, $item_ID, $notify, $reg_type ) )
		{
			if( $notify == 0 )
			{
				$Messages->add( $success_unsub_message, 'success' );
			}
			else
			{
				$Messages->add( $success_sub_message, 'success' );
			}
		}
		else
		{
			$Messages->add( T_( 'Could not subscribe to attend this event.' ), 'error' );
		}
		break;

	default:
		bad_request_die( 'Invalid subsription type' );
}

header_redirect();

/*
 * $Log$
 * Revision 1.11  2011/10/10 19:48:31  fplanque
 * i18n & login display cleaup
 *
 * Revision 1.10  2011/10/02 02:51:11  fplanque
 * no message
 *
 * Revision 1.9  2011/09/30 08:22:18  efy-asimo
 * Events update
 *
 * Revision 1.8  2011/09/29 15:26:56  efy-asimo
 * Admin add/remove attendee feature.
 *
 * Revision 1.7  2011/09/10 02:09:08  fplanque
 * doc
 *
 * Revision 1.6  2011/09/10 00:57:23  fplanque
 * doc
 *
 * Revision 1.5  2011/09/08 05:22:40  efy-asimo
 * Remove item attending and add item settings
 *
 * Revision 1.4  2011/09/06 00:54:38  fplanque
 * i18n update
 *
 * Revision 1.3  2011/09/04 22:13:13  fplanque
 * copyright 2011
 *
 * Revision 1.2  2011/05/25 14:59:33  efy-asimo
 * Post attending
 *
 * Revision 1.1  2011/05/19 17:47:07  efy-asimo
 * register for updates on a specific blog post
 *
 */
?>