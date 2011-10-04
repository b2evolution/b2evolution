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
		$comment_id = param( 'comment_id', 'integer', 0 );
		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $blog_ID );

		if( $recipient_id > 0 )
		{ // Get identity link for existed users
			$RecipientCache = & get_UserCache();
			$Recipient = $RecipientCache->get_by_ID( $recipient_id );
			$recipient_link = $Recipient->get_identity_link( array( 'link_text' => 'text' ) );
		}
		else if( $comment_id > 0 )
		{ // Anonymous Users
			$gender_class = '';
			if( check_setting( 'gender_colored' ) )
			{ // Set a gender class if the setting is ON
				$gender_class = ' nogender';
			}
			$recipient_link = '<span class="user anonymous'.$gender_class.'" rel="bubbletip_comment_'.$comment_id.'">'.$recipient_name.'</span>';
		}

		require $skins_path.'_contact_msg.form.php';
		break;

	case 'get_user_bubbletip':
		// Get contents of a user bubbletip
		// Displays avatar & name
		$user_ID = param( 'userid', 'integer', 0 );
		$comment_ID = param( 'commentid', 'integer', 0 );

		if( strpos( $_SERVER["HTTP_REFERER"], "/admin.php" ) !== FALSE )
		{ // If ajax is requested from admin page we should to set a variable $is_admin_page = true if user has permissions
			// Check global permission:
			if( empty($current_User) || ! $current_User->check_perm( 'admin', 'restricted' ) )
			{ // No permission to access admin...
				require $adminskins_path.'_access_denied.main.php';
			}
			else
			{ // Set this page as admin page
				$is_admin_page = true;
			}
		}

		if( $blog_ID > 0 )
		{ // Get Blog if ID is set
			$BlogCache = & get_BlogCache();
			$Blog = $BlogCache->get_by_ID( $blog_ID );
		}

		if( $user_ID > 0 )
		{ // Print info of the registred users
			$UserCache = & get_UserCache();
			$User = & $UserCache->get_by_ID( $user_ID );

			if( is_logged_in() )
			{ // Set avatar for logged users
				$avatar_size = 'fit-160x160';
			}
			else
			{ // Set avatar with blur effect for NOT logged users
				$avatar_size = 'fit-160x160-blur-13';
			}
			// Display user avatar with login
			echo '<div class="center">';
			echo get_avatar_imgtag( $User->login, true, true, $avatar_size, 'avatar_above_login' );
			echo '</div>';
		}
		else if( $comment_ID > 0 )
		{ // Print info for an anonymous user who posted a comment
			$CommentCache = & get_CommentCache();
			$Comment = $CommentCache->get_by_ID( $comment_ID );

			echo '<div class="bubbletip_anon">';

			echo $Comment->get_avatar( 'fit-160x160', 'bCommentAvatar floatcenter');
			echo '<div>'.$Comment->get_author_name_anonymous().'</div>';
			echo '<div>'.T_('This user is not registered on this site.').'</div>';
			echo $Comment->get_author_url_link( '', '<div>', '</div>');

			if( isset( $Blog ) )
			{ // Link to send message
				echo '<div>';
				$Comment->msgform_link( $Blog->get('msgformurl'), '', '', get_icon( 'email', 'imgtag' ).' '.T_('Send a message') );
				echo '</div>';
			}
			echo '</div>';
		}

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
			else if( ! is_logged_in() || $type != 'helpful' )
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
 * Revision 1.18  2011/10/04 09:16:31  efy-yurybakh
 * blur effect
 *
 * Revision 1.17  2011/10/03 17:13:04  efy-yurybakh
 * review fp>yura comments
 *
 * Revision 1.16  2011/10/03 14:45:16  efy-yurybakh
 * Add User::get_identity_link() everywhere
 *
 * Revision 1.15  2011/10/03 10:07:05  efy-yurybakh
 * bubbletips & identity_links cleanup
 *
 * Revision 1.14  2011/10/03 07:02:21  efy-yurybakh
 * bubbletips & identity_links cleanup
 *
 * Revision 1.13  2011/10/03 01:15:37  fplanque
 * doc
 *
 * Revision 1.12  2011/09/30 07:38:58  efy-yurybakh
 * bubbletip for anonymous comments
 *
 * Revision 1.11  2011/09/29 16:42:18  efy-yurybakh
 * colored login
 *
 * Revision 1.10  2011/09/29 08:39:00  efy-yurybakh
 * - user_identity_link
 * - lightbox
 *
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