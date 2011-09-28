<?php
/**
 * This is the handler for ANONYMOUS (non logged in) asynchronous 'AJAX' calls.
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
 * @package evocore
 * 
 * @version $Id$
 */


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

load_funcs( '../inc/skins/_skin.funcs.php' );

global $skins_path;
param( 'action', 'string', '' );
$item_ID = param( 'p', 'integer' );
$blog_ID = param( 'blog', 'integer' );

// Make sure the async responses are never cached:
header_nocache();
header_content_type( 'text/html', $io_charset );

// Do not append Debuglog to response!
$debug = false;

$params = param( 'params', 'array', array() );
switch( $action )
{
	case 'get_comment_form':
		// display comment form
		$ItemCache = & get_ItemCache();
		$Item = $ItemCache->get_by_ID( $item_ID );
		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $blog_ID );
		$disp = param( 'disp', 'string', '' );
		$skin = '';
		if( !empty( $Blog->skin_ID ) )
		{ // check if Blog skin has specific comment form
			$SkinCache = & get_SkinCache();
			$Skin = & $SkinCache->get_by_ID( $Blog->skin_ID );
			$skin = $Skin->folder.'/';
			if( ! file_exists( $skins_path.$skin.'_item_comment_form.inc.php' ) )
			{
				$skin = '';
			}
		}

		require $skins_path.$skin.'_item_comment_form.inc.php';
		break;

	case 'get_msg_form':
		// display send message form
		$recipient_id = param( 'recipient_id', 'integer', 0 );
		$recipient_name = param( 'recipient_name', 'string', '' );
		$subject = param( 'subject', 'string', '' );
		$email_author = param( 'email_author', 'string', '' );
		$email_author_address = param( 'email_author_address', 'string', '' );
		$allow_msgform = param( 'allow_msgform', 'string', '' );
		$redirect_to = param( 'redirect_to', 'string', '' );
		$post_id = NULL;
		$comment_id = NULL;
		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $blog_ID );

		require $skins_path.'_contact_msg.form.php';
		break;

	case "get_user_bubbletip":
		$user_login = param( 'userid', 'string' );

		echo '<div class="center">'.get_avatar_imgtag( $user_login, true, true, 'fit-160x160', 'avatar_above_login' ).'</div>';

		exit(0);

	case 'set_comment_vote':
		// Used for quick vote of comments
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ), false );
		if( $edited_Comment !== false )
		{ // The comment still exists
			$type = param( 'type', 'string' );
			if( $type == 'spam' )
			{ // Check permission for spam voting
				$current_User->check_perm( 'blog_vote_spam_comments', 'edit', true, param( 'blogid', 'integer' ) );
			}
			else if( ! is_logged_in() || $type != 'useful' )
			{ // Restrict not logged users here
				exit(0);
			}

			$edited_Comment->set_vote( $type, param( 'vote', 'string' ) );
			$edited_Comment->dbupdate();
			
			$edited_Comment->{'vote_'.$type}( '', '', '&amp;', true, true );
		}

		exit(0);
}

exit();

/*
 * $Log$
 * Revision 1.9  2011/09/28 16:15:56  efy-yurybakh
 * "comment was helpful" votes
 *
 * Revision 1.8  2011/09/27 12:53:52  efy-yurybakh
 * bubbletip fix
 *
 * Revision 1.7  2011/09/27 06:08:14  efy-yurybakh
 * Add User::get_identity_link() everywhere
 *
 * Revision 1.6  2011/09/26 19:46:01  efy-yurybakh
 * jQuery bubble tips
 *
 * Revision 1.5  2011/09/16 05:50:39  sam2kb
 * Added missing PHP closing tag ?>
 *
 * Revision 1.4  2011/09/04 22:13:12  fplanque
 * copyright 2011
 *
 * Revision 1.3  2011/09/04 21:32:17  fplanque
 * minor MFB 4-1
 *
 * Revision 1.2  2011/07/01 12:18:44  efy-asimo
 * Use ajax to display comment and contact forms - fix basic and glossyblue skins
 *
 * Revision 1.1  2011/06/29 13:14:01  efy-asimo
 * Use ajax to display comment and contact forms
 *
 */
?>