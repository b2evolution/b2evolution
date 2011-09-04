<?php
/**
 * This file process threads, messages and contacts actions sent out from front office Messages.
 * Logged in users required to process these actions.
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
 * @version $Id$
 */


/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

if( !is_logged_in() )
{
	debug_die( 'User must be logged in to proceed messaging updates!' );
}

header( 'Content-Type: text/html; charset='.$io_charset );

load_class( 'messaging/model/_thread.class.php', 'Thread' );
load_class( 'messaging/model/_message.class.php', 'Message' );
load_class( '_core/ui/_uiwidget.class.php', 'Widget' );

$action = param_action( '', true );

$disp = param( 'disp', 'string', NULL );
if( empty( $disp ) )
{
	$disp = 'threads';
}

// set where to redirect
$redirect_to = param( 'redirect_to', 'string', NULL );
if( empty( $redirect_to ) )
{
	if( isset( $Blog ) )
	{
		$redirect_to = $Blog->gen_baseurl().'?disp='.$disp;
	}
	else
	{
		$redirect_to = $baseurl.'?disp='.$disp;
	}
}

if( ( $disp != 'contacts' ) && ( param( 'thrd_ID', 'integer', '', true ) ) )
{// Load thread from cache:
	$ThreadCache = & get_ThreadCache();
	if( ($edited_Thread = & $ThreadCache->get_by_ID( $thrd_ID, false )) === false )
	{	unset( $edited_Thread );
		forget_param( 'thrd_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Thread') ), 'error' );
		$action = 'nil';
	}
}

switch( $disp )
{
	// threads action
	case 'threads':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'thread' );

		if( $action == 'create' )
		{
			if( !create_new_thread() )
			{ // unsuccessful new thread creation
				$redirect_to .= '&action=new';
			}
		}
		elseif( $action == 'delete' )
		{
			// TODO: Confirm message in if statement
			$msg = sprintf( T_('Thread &laquo;%s&raquo; deleted.'), $edited_Thread->dget('title') );
			$edited_Thread->dbdelete( true );
			unset( $edited_Thread );
			forget_param( 'thrd_ID' );
			$Messages->add( $msg, 'success' );
		}
		break;

	// contacts action
	case 'contacts':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'contact' );

		$user_ID = param( 'user_ID', 'string', true );

		if( ( $action != 'block' ) && ( $action != 'unblock' ) )
		{ // only block or unblock is valid
			debug_die( "Invalid action param" );
		}
		set_contact_blocked( $user_ID, ( ( $action == 'block' ) ? 1 : 0 ) );
		break;

	// messages action
	case 'messages':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'message' );

		if( $action == 'create' )
		{ // create new message
			$non_blocked_contacts = $edited_Thread->load_contacts();
			create_new_message( $thrd_ID, !empty( $non_blocked_contacts ) );
		}
		elseif( $action == 'delete' )
		{
			// Check permission:
			$current_User->check_perm( 'perm_messaging', 'delete', true );

			$msg_ID = param( 'msg_ID', 'integer', true );
			$MessageCache = & get_MessageCache();
			if( ($edited_Message = & $MessageCache->get_by_ID( $msg_ID, false )) === false )
			{
				$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Message') ), 'error' );
				break;
			}
			// delete message
			$edited_Message->dbdelete();
			unset( $edited_Message );
			$Messages->add( T_('Message deleted.'), 'success' );
		}
		break;
}

header_redirect( $redirect_to ); // Will save $Messages into Session

/*
 * $Log$
 * Revision 1.2  2011/09/04 22:13:13  fplanque
 * copyright 2011
 *
 * Revision 1.1  2011/08/11 09:05:08  efy-asimo
 * Messaging in front office
 *
 */
?>