<?php
/**
 * This file implements Comment handling functions.
  *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @todo implement CommentCache based on LinkCache
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author cafelog (team)
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'comments/model/_comment.class.php', 'Comment' );

/**
 * Generic comments/trackbacks/pingbacks counting
 *
 * @todo check this in a multiblog page...
 * @todo This should support visibility: at least in the default front office (_feedback.php), there should only the number of visible comments/trackbacks get used ({@link Item::feedback_link()}).
 *
 * @param integer
 * @param string what to count
 */
function generic_ctp_number( $post_id, $mode = 'comments', $status = 'published' )
{
	global $DB, $debug, $postdata, $cache_ctp_number, $preview;

	if( $preview )
	{ // we are in preview mode, no comments yet!
		return 0;
	}

	/*
	 * Make sure cache is loaded for current display list:
	 */
	if( !isset($cache_ctp_number) )
	{
		global $postIDlist, $postIDarray;

		// if( $debug ) echo "LOADING generic_ctp_number CACHE for posts: $postIDlist<br />";

		if( ! empty( $postIDlist ) )	// This can happen when displaying a featured post of something that's not in the MainList
		{
			foreach( $postIDarray as $tmp_post_id)
			{	// Initializes each post to nocount!
				$cache_ctp_number[$tmp_post_id] = array(
						'comments' => array( 'published' => 0, 'draft' => 0, 'deprecated' => 0, 'trash' => 0, 'total' => 0 ),
						'trackbacks' => array( 'published' => 0, 'draft' => 0, 'deprecated' => 0, 'trash' => 0, 'total' => 0 ),
						'pingbacks' => array( 'published' => 0, 'draft' => 0, 'deprecated' => 0, 'trash' => 0, 'total' => 0 ),
						'feedbacks' => array( 'published' => 0, 'draft' => 0, 'deprecated' => 0, 'trash' => 0, 'total' => 0 )
					);
			}

			$query = 'SELECT comment_post_ID, comment_type, comment_status, COUNT(*) AS type_count
								 FROM T_comments
								 WHERE comment_post_ID IN ('.$postIDlist.')
								 GROUP BY comment_post_ID, comment_type, comment_status';

			foreach( $DB->get_results( $query ) as $row )
			{
				// detail by status, tyep and post:
				$cache_ctp_number[$row->comment_post_ID][$row->comment_type.'s'][$row->comment_status] = $row->type_count;

				// Total for type on post:
				$cache_ctp_number[$row->comment_post_ID][$row->comment_type.'s']['total'] += $row->type_count;

				// Total for status on post:
				$cache_ctp_number[$row->comment_post_ID]['feedbacks'][$row->comment_status] += $row->type_count;

				// Total for post:
				$cache_ctp_number[$row->comment_post_ID]['feedbacks']['total'] += $row->type_count;
			}
		}
	}
	/*	else
	{
		echo "cache set";
	}*/


	if( !isset($cache_ctp_number[$post_id]) )
	{ // this should be extremely rare...
		// echo "CACHE not set for $post_id";

		// Initializes post to nocount!
		$cache_ctp_number[intval($post_id)] = array(
				'comments' => array( 'published' => 0, 'draft' => 0, 'deprecated' => 0, 'trash' => 0, 'total' => 0 ),
				'trackbacks' => array( 'published' => 0, 'draft' => 0, 'deprecated' => 0, 'trash' => 0, 'total' => 0 ),
				'pingbacks' => array( 'published' => 0, 'draft' => 0, 'deprecated' => 0, 'trash' => 0, 'total' => 0 ),
				'feedbacks' => array( 'published' => 0, 'draft' => 0, 'deprecated' => 0, 'trash' => 0, 'total' => 0 )
			);

		$query = 'SELECT comment_post_ID, comment_type, comment_status, COUNT(*) AS type_count
							  FROM T_comments
							 WHERE comment_post_ID = '.intval($post_id).'
							 GROUP BY comment_post_ID, comment_type, comment_status';

		foreach( $DB->get_results( $query ) as $row )
		{
			// detail by status, tyep and post:
			$cache_ctp_number[$row->comment_post_ID][$row->comment_type.'s'][$row->comment_status] = $row->type_count;

			// Total for type on post:
			$cache_ctp_number[$row->comment_post_ID][$row->comment_type.'s']['total'] += $row->type_count;

			// Total for status on post:
			$cache_ctp_number[$row->comment_post_ID]['feedbacks'][$row->comment_status] += $row->type_count;

			// Total for post:
			$cache_ctp_number[$row->comment_post_ID]['feedbacks']['total'] += $row->type_count;
		}
	}

	if( ($mode != 'comments') && ($mode != 'trackbacks') && ($mode != 'pingbacks') )
	{
		$mode = 'feedbacks';
	}

	if( ($status != 'published') && ($status != 'draft') && ($status != 'deprecated') )
	{
		$status = 'total';
	}

	// pre_dump( $cache_ctp_number[$post_id] );

	return $cache_ctp_number[$post_id][$mode][$status];
}


/**
 * Get a Comment by ID. Exits if the requested comment does not exist!
 *
 * @param integer
 * @param boolean
 * @return Comment
 */
function & Comment_get_by_ID( $comment_ID, $halt_on_error = true )
{
	$CommentCache = & get_CommentCache();
	return $CommentCache->get_by_ID( $comment_ID, $halt_on_error );
}


/*
 * last_comments_title(-)
 *
 * @movedTo _obsolete092.php
 */


/***** Comment tags *****/

/**
 * comments_number(-)
 *
 * @deprecated deprecated by {@link Item::feedback_link()}, used in _edit_showposts.php
 */
function comments_number( $zero='#', $one='#', $more='#', $post_ID = NULL )
{
	if( $zero == '#' ) $zero = T_('Leave a comment');
	if( $one == '#' ) $one = T_('1 comment');
	if( $more == '#' ) $more = T_('%d comments');

	// original hack by dodo@regretless.com
	if( empty( $post_ID ) )
	{
		global $id;
		$post_ID = $id;
	}
	// fp>asimo: I'm not sure about this below. It's only in the backoffice where
	// we want to display the total. in the front, we still don't want to count in drafts
	// can you check & confirm?
	// asimo>fp: This function is called only from the backoffice ( _item_list_full.view.php ).
	// There we always have to show all comments.
	$number = generic_ctp_number( $post_ID, 'comments', 'total' );
	if ($number == 0)
	{
		$blah = $zero;
	}
	elseif ($number == 1)
	{
		$blah = $one;
	}
	elseif ($number  > 1)
	{
		$n = $number;
		$more = str_replace('%d', $n, $more);
		$blah = $more;
	}
	echo $blah;
}

/**
 * Get advanced perm for comment moderation on this blog
 *
 * @param int blog ID
 * @return array statuses - current user has permission to moderate comments with these statuses
 */
function get_allowed_statuses( $blog )
{
	global $current_User;
	$statuses = array();

	if( $current_User->check_perm( 'blog_draft_comments', 'edit', false, $blog ) )
	{
		$statuses[] = 'draft';
	}

	if( $current_User->check_perm( 'blog_published_comments', 'edit', false, $blog ) )
	{
		$statuses[] = 'published';
	}

	if( $current_User->check_perm( 'blog_deprecated_comments', 'edit', false, $blog ) )
	{
		$statuses[] = 'deprecated';
	}

	return $statuses;
}

/**
 * Create comment form submit buttons
 *
 * Note: Publsih in only displayed when comment is in draft status
 *
 * @param $Form
 * @param $edited_Comment
 *
 */
function echo_comment_buttons( $Form, $edited_Comment )
{
	global $Blog, $current_User;

	// ---------- SAVE ------------
	$Form->submit( array( 'actionArray[update]', T_('Save!'), 'SaveButton' ) );

	// ---------- PUBLISH ---------
	if( $edited_Comment->status == 'draft'
			&& $current_User->check_perm( 'blog_post!published', 'edit', false, $Blog->ID )	// TODO: if we actually set the primary cat to another blog, we may still get an ugly perm die
			&& $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{
		 $publish_style = 'display: inline';
	}
	else
	{
		$publish_style = 'display: none';
	}
	$Form->submit( array(
		'actionArray[update_publish]',
		/* TRANS: This is the value of an input submit button */ T_('Publish!'),
		'SaveButton',
		'',
		$publish_style
	) );
}


/**
 * JS Behaviour: Output JavaScript code to dynamically show or hide the "Publish!"
 * button depending on the selected comment status.
 *
 * This function is used by the comment edit screen.
 */
function echo_comment_publishbt_js()
{
	global $next_action;
	?>
	<script type="text/javascript">
	jQuery( '#commentform_visibility input[type=radio]' ).click( function()
	{
		var commentpublish_btn = jQuery( '.edit_actions input[name=actionArray[update_publish]]' );

		if( this.value != 'draft' )
		{	// Hide the "Publish NOW !" button:
			commentpublish_btn.css( 'display', 'none' );
		}
		else
		{	// Show the button:
			commentpublish_btn.css( 'display', 'inline' );
		}
	} );
	</script>
	<?php
}


/**
 * Add a javascript ban action icon after the given url
 *
 * @param string url
 * @return string the url with ban icon
 */
function add_jsban( $url )
{
	$url = rawurlencode(get_ban_domain( $url ));
	return '<a id="ban_url" href="javascript:ban_url('.'\''.$url.'\''.');">'.get_icon( 'ban' ).'</a>';
}


/**
 * Add a javascript ban action icon after each url in the given content
 *
 * @param string Comment content
 * @return string the content with a ban icon after each url if the user has spamblacklist permission, the incoming content otherwise
 */
function add_ban_icons( $content )
{
	global $current_User;
	if( ! $current_User->check_perm( 'spamblacklist', 'edit' ) )
	{
		return $content;
	}

	$atags = get_atags( $content );
	$imgtags = get_imgtags( $content );
	$urls = get_urls( $content );
	$result = '';
	$from = 0; // current processing position
	$length = 0; // current url or tag length
	$i = 0; // url counter
	$j = 0; // "a" tag counter
	$k = 0; // "img" tag counter
	while( isset($urls[$i]) )
	{ // there is unprocessed url
		$url = $urls[$i];
		if( validate_url( $url, 'posting', false ) )
		{ // skip not valid urls
			$i++;
			continue;
		}
		while( isset( $imgtags[$k] ) && ( strpos( $content, $imgtags[$k] ) < $from ) )
		{ // skipp already passed img tags
			$k++;
		}

		$pos = strpos( $content, $url, $from );
		$length = evo_strlen($url);
		$i++;

		// check img tags
		if( isset( $imgtags[$k] ) && ( strpos( $imgtags[$k], $url ) !== false )
			&& ( $pos > strpos( $content, $imgtags[$k], $from ) ) )
		{ // current url is inside the img tag, we need to skip this url.
			$result .= substr( $content, $from, $pos + $length - $from );
			$from = $pos + $length;
			$k++;
			continue;
		}

		// check a tags
		if( isset($atags[$j]) )
		{ // there is unprocessed "a" tag
			$tag = $atags[$j];
			if( ( ( $urlpos = strpos( $tag, $url ) ) !== false ) && ( $pos > strpos( $content, $tag, $from ) ) )
			{ // the url is inside the current tag, we have to add ban icon after the tag
				$pos = strpos( $content, $tag, $from );
				$length = strlen($tag);
				while( isset($urls[$i]) && ( ( $urlpos = strpos( $tag, $urls[$i], $urlpos + 1 ) ) !== false ) )
				{ // skip all other urls from this tag
					$i++;
				}
				$j++;
			}
		}
		// add processed part and ban icon to result and set current position
		$result .= substr( $content, $from, $pos + $length - $from );
		$from = $pos + $length;
		$result .= add_jsban( $url );
	}

	// add the end of the content to the result
	$result .= substr( $content, $from, strlen($content) - $from );
	return $result;
}


/**
 * Get opentrash link
 *
 * @param boolean check permission or not. Should be false only if it was already checked.
 * @param boolean show "Open recycle bin" link even if there is no comment with 'trash' status
 * @return Open recycle bin link if user has the corresponding 'blogs' - 'editall' permission, empty string otherwise
 */
function get_opentrash_link( $check_perm = true, $force_show = false )
{
	global $admin_url, $current_User, $DB, $blog;

	$show_recycle_bin = ( !$check_perm || $current_User->check_perm( 'blogs', 'editall' ) );
	if( $show_recycle_bin && ( !$force_show ) )
	{ // get number of trash comments:
		$query = 'SELECT COUNT( comment_ID )
								FROM T_blogs LEFT OUTER JOIN T_categories ON blog_ID = cat_blog_ID
											LEFT OUTER JOIN T_items__item ON cat_ID = post_main_cat_ID
											LEFT OUTER JOIN T_comments ON post_ID = comment_post_ID
							 WHERE comment_status = "trash"';
		if( isset( $blog ) )
		{
			$query .= ' AND blog_ID='.$blog;
		}
		$show_recycle_bin = ( $DB->get_var( $query ) > 0 );
	}

	$result = '<div id="recycle_bin">';
	if( $show_recycle_bin )
	{ // show "Open recycle bin"
		$result .= '<span class="floatright">'.action_icon( T_('Open recycle bin'), 'recycle_full',
						$admin_url.'?ctrl=comments&amp;show_statuses[]=trash', T_('Open recycle bin'), 5, 3 ).'</span> ';
	}
	return $result.'</div>';
}


/**
 * Creates an array for the notification email
 *
 * @param string user email
 * @param string user locale
 * @param string user unsubscribe key
 * @param string notification type ( moderator, creator, blog_subscription, item_subscription )
 * @return array user data
 */
function build_notify_data( $notify_email, $notify_locale, $notify_key, $notify_type, $prefered_name, $login )
{
	return array(
		'email' => $notify_email,
		'locale' => $notify_locale,
		'key' => $notify_key,
		'type' => $notify_type,
		'prefered_name' => $prefered_name,
		'login' => $login
	);
}


/**
 * Display disabled comment form
 *
 * @param string Blog allow comments settings value
 * @param string Item url, where this comment form should be displayed
 */
function echo_disabled_comments( $allow_comments_value, $item_url )
{
	global $Settings;

	switch( $allow_comments_value )
	{
		case 'member':
			$disabled_text = T_( 'You must be a member of this blog to comment.' );
			break;
		case 'registered':
			$disabled_text = T_( 'You must be logged in to leave a comment.' );
			break;
		default:
			// case any or never, in this case comment form is already displayed, or comments are not allowed at all.
			return;
	}

	$is_logged_in = is_logged_in();
	$login_link = ( $is_logged_in ) ? '' : '<a href="'.get_login_url( $item_url ).'">'.T_( 'Log in now!' ).'</a>';

	$register_link = '';
	if( ( !$is_logged_in ) && ( $Settings->get( 'newusers_canregister' ) ) )
	{
		$register_link = '<p>'.sprintf(  T_( 'If you have no account yet, you can <a href="%s">register now</a>... (It only takes a few seconds!)' ), get_user_register_url( '', 'reg to post comment' ) ).'</p>';
	}

	// disabled comment form
	echo '<form class="bComment">';

	echo '<div class="comment_disabled_msg">';
	if( $is_logged_in )
	{
		echo '<p>'.$disabled_text.'</p>';
	}
	else
	{ // not logged in, add login and register links
		echo '<p>'.$disabled_text.' '.$login_link.'</p>';
		echo $register_link;
	}
	echo '</div>';

	echo '<fieldset>';
	echo '<div class="label"><label for="p">'.T_( 'Comment text:' ).'</label></div>';
	echo '<div class="input">';
	echo '<textarea id="p" class="bComment form_text_areainput" rows="5" name="p" cols="40" disabled="true">'.$disabled_text.'</textarea>';
	echo '</div>';
	echo '</fieldset>';
	// margin at the bottom of the form
	echo '<div style="margin-top:10px"></div>';
	echo '</form>';
}


/**
 * Save Comment object into the current Session
 * 
 * @param $Comment
 */
function save_comment_to_session( $Comment )
{
	global $Session;
	$Session->set( 'core.unsaved_Comment', $Comment );
}


/**
 * Get Comment object from the current Session
 * 
 * @return Comment|NULL Comment object if Session core.unsaved_Comment param is set, NULL otherwise 
 */
function get_comment_from_session()
{
	global $Session;
	if( ( $mass_Comment = $Session->get( 'core.unsaved_Comment' ) ) && is_a( $mass_Comment, 'Comment' ) )
	{
		$Session->delete( 'core.unsaved_Comment' );
		return $mass_Comment;
	}
	return NULL;
}


/*
 * $Log$
 * Revision 1.35  2011/10/04 08:39:30  efy-asimo
 * Comment and message forms save/reload content in case of error
 *
 * Revision 1.34  2011/09/23 01:29:05  fplanque
 * small changes
 *
 * Revision 1.33  2011/09/08 23:29:27  fplanque
 * More blockcache/widget fixes around login/register links.
 *
 * Revision 1.32  2011/09/04 22:13:15  fplanque
 * copyright 2011
 *
 * Revision 1.31  2011/09/04 21:32:17  fplanque
 * minor MFB 4-1
 *
 * Revision 1.30  2011/08/25 05:40:57  efy-asimo
 * Allow comments for "Members only" - display disabled comment form
 *
 * Revision 1.29  2011/07/04 12:26:54  efy-asimo
 * Notification emails content - fix
 *
 * Revision 1.28  2011/05/19 17:47:07  efy-asimo
 * register for updates on a specific blog post
 *
 * Revision 1.27  2011/03/16 13:56:05  efy-asimo
 * Update show "Open recycle bin"
 *
 * Revision 1.26  2011/03/16 13:34:53  efy-asimo
 * animate comment delete
 *
 * Revision 1.25  2011/02/25 22:04:09  fplanque
 * minor / UI cleanup
 *
 * Revision 1.24  2011/02/24 07:42:27  efy-asimo
 * Change trashcan to Recycle bin
 *
 * Revision 1.23  2011/02/15 06:13:49  sam2kb
 * strlen replaced with evo_strlen to support utf-8 logins and domain names
 *
 * Revision 1.22  2011/02/14 14:13:24  efy-asimo
 * Comments trash status
 *
 * Revision 1.21  2011/02/10 23:07:21  fplanque
 * minor/doc
 *
 * Revision 1.20  2011/01/23 19:24:36  sam2kb
 * Fixed HTML errors in liks
 *
 * Revision 1.19  2011/01/06 14:31:47  efy-asimo
 * advanced blog permissions:
 *  - add blog_edit_ts permission
 *  - make the display more compact
 *
 * Revision 1.18  2010/10/19 13:31:31  efy-asimo
 * Ajax comment moderation - fix
 *
 * Revision 1.17  2010/10/12 12:38:22  efy-asimo
 * Comment inline antispam - fix
 *
 * Revision 1.16  2010/09/28 11:33:06  efy-asimo
 * add_ban_icons - fix
 *
 * Revision 1.10.2.7  2010/09/28 23:41:31  fplanque
 * minor/doc
 *
 * Revision 1.15  2010/09/23 15:12:14  efy-asimo
 * antispam in comment text feature - add permission check - fix
 *
 * Revision 1.14  2010/09/23 14:21:00  efy-asimo
 * antispam in comment text feature
 *
 * Revision 1.13  2010/09/20 13:06:06  efy-asimo
 * show total comments number on item full view - fix
 *
 * Revision 1.10.2.3  2010/07/03 22:52:19  fplanque
 * no message
 *
 * Revision 1.10.2.2  2010/06/20 20:24:37  fplanque
 * PHP4 compatibility
 *
 * Revision 1.11  2010/06/01 11:33:19  efy-asimo
 * Split blog_comments advanced permission (published, deprecated, draft)
 * Use this new permissions (Antispam tool,when edit/delete comments)
 *
 * Revision 1.10  2010/03/11 10:34:53  efy-asimo
 * Rewrite CommentList to CommentList2 task
 *
 * Revision 1.9  2010/02/28 23:38:40  fplanque
 * minor changes
 *
 * Revision 1.8  2010/02/08 17:52:13  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.7  2010/01/29 23:07:04  efy-asimo
 * Publish Comment button
 *
 * Revision 1.6  2009/09/14 12:46:36  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.5  2009/03/27 02:08:29  sam2kb
 * Minor. Believe it or not, but this little thing produced MYSQL error on php4 because the $postIDlist was always empty.
 *
 * Revision 1.4  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.3  2009/01/21 18:23:26  fplanque
 * Featured posts and Intro posts
 *
 * Revision 1.2  2008/01/21 09:35:27  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:41  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.11  2007/05/09 00:58:55  fplanque
 * massive cleanup of old functions
 *
 * Revision 1.10  2007/04/26 00:11:08  fplanque
 * (c) 2007
 *
 * Revision 1.9  2007/01/26 04:49:17  fplanque
 * cleanup
 *
 * Revision 1.8  2006/08/21 16:07:43  fplanque
 * refactoring
 *
 * Revision 1.7  2006/08/19 02:15:07  fplanque
 * Half kille dthe pingbacks
 * Still supported in DB in case someone wants to write a plugin.
 *
 * Revision 1.6  2006/07/04 17:32:29  fplanque
 * no message
 *
 * Revision 1.5  2006/06/22 21:58:34  fplanque
 * enhanced comment moderation
 *
 * Revision 1.4  2006/05/04 03:08:12  blueyed
 * todo
 *
 * Revision 1.3  2006/04/22 16:30:00  blueyed
 * cleanup
 *
 * Revision 1.2  2006/03/12 23:08:58  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:57  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 */
?>