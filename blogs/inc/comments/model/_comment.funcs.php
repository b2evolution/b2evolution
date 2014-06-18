<?php
/**
 * This file implements Comment handling functions.
  *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
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
 * @version $Id: _comment.funcs.php 6539 2014-04-23 14:32:54Z yura $
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
 * @param mixed string or array to count comments with this/these status(es)
 * @param boolean set true to count expired comments, leave on false otherwise
 */
function generic_ctp_number( $post_id, $mode = 'comments', $status = 'published', $count_expired = false, $filter_by_perm = true )
{
	global $DB, $debug, $postdata, $cache_ctp_number, $preview, $servertimenow, $blog;

	if( $preview )
	{ // we are in preview mode, no comments yet!
		return 0;
	}

	$show_statuses = is_admin_page() ? get_visibility_statuses( 'keys', array( 'trash', 'redirected' ) ) : get_inskin_statuses( $blog, 'comment' );
	$filter_index = $filter_by_perm ? 0 : 1;
	if( !isset($cache_ctp_number) || !isset($cache_ctp_number[$filter_index][$post_id]) )
	{ // we need a query to count comments
		$count_SQL = new SQL();
		$count_SQL->SELECT( 'comment_item_ID, comment_type, comment_status, COUNT(*) AS type_count' );
		$count_SQL->FROM( 'T_comments' );
		$count_SQL->GROUP_BY( 'comment_item_ID, comment_type, comment_status' );
		if( !empty( $blog ) )
		{
			$count_SQL->WHERE( statuses_where_clause( $show_statuses, 'comment_', $blog, 'blog_comment!', $filter_by_perm ) );
		}

		if( !$count_expired )
		{
			$count_SQL->FROM_add( 'LEFT JOIN T_items__item_settings as expiry_setting ON comment_item_ID = iset_item_ID AND iset_name = "post_expiry_delay"' );
			$count_SQL->WHERE_and( 'expiry_setting.iset_value IS NULL OR expiry_setting.iset_value = "" OR TIMESTAMPDIFF(SECOND, comment_date, '.$DB->quote( date2mysql( $servertimenow ) ).') < expiry_setting.iset_value' );
		}
	}

	// init statuses count array
	$statuses_array = array( 'published' => 0, 'community' => 0, 'protected' => 0, 'private' => 0, 'review' => 0, 'draft' => 0, 'deprecated' => 0, 'trash' => 0, 'total' => 0 );

	/*
	 * Make sure cache is loaded for current display list:
	 */
	if( !isset($cache_ctp_number) || !isset($cache_ctp_number[$filter_index]) )
	{
		global $postIDlist, $postIDarray;

		// if( $debug ) echo "LOADING generic_ctp_number CACHE for posts: $postIDlist<br />";

		if( ! empty( $postIDlist ) )	// This can happen when displaying a featured post of something that's not in the MainList
		{
			foreach( $postIDarray as $tmp_post_id)
			{	// Initializes each post to nocount!
				$cache_ctp_number[$filter_index][$tmp_post_id] = array(
						'comments'   => $statuses_array,
						'trackbacks' => $statuses_array,
						'pingbacks'  => $statuses_array,
						'feedbacks'  => $statuses_array
					);
			}

			$countall_SQL = $count_SQL;
			$countall_SQL->WHERE_and( 'comment_item_ID IN ('.$postIDlist.')' );

			foreach( $DB->get_results( $countall_SQL->get() ) as $row )
			{
				// detail by status, tyep and post:
				$cache_ctp_number[$filter_index][$row->comment_item_ID][$row->comment_type.'s'][$row->comment_status] = $row->type_count;

				// Total for type on post:
				$cache_ctp_number[$filter_index][$row->comment_item_ID][$row->comment_type.'s']['total'] += $row->type_count;

				// Total for status on post:
				$cache_ctp_number[$filter_index][$row->comment_item_ID]['feedbacks'][$row->comment_status] += $row->type_count;

				// Total for post:
				$cache_ctp_number[$filter_index][$row->comment_item_ID]['feedbacks']['total'] += $row->type_count;
			}
		}
	}
	/*	else
	{
		echo "cache set";
	}*/


	if( !isset($cache_ctp_number[$filter_index][$post_id]) )
	{ // this should be extremely rare...
		// echo "CACHE not set for $post_id";

		// Initializes post to nocount!
		$cache_ctp_number[$filter_index][intval($post_id)] = array(
				'comments' => $statuses_array,
				'trackbacks' => $statuses_array,
				'pingbacks' => $statuses_array,
				'feedbacks' => $statuses_array
			);

		$count_SQL->WHERE_and( 'comment_item_ID = '.intval($post_id) );

		foreach( $DB->get_results( $count_SQL->get() ) as $row )
		{
			// detail by status, type and post:
			$cache_ctp_number[$filter_index][$row->comment_item_ID][$row->comment_type.'s'][$row->comment_status] = $row->type_count;

			// Total for type on post:
			$cache_ctp_number[$filter_index][$row->comment_item_ID][$row->comment_type.'s']['total'] += $row->type_count;

			// Total for status on post:
			$cache_ctp_number[$filter_index][$row->comment_item_ID]['feedbacks'][$row->comment_status] += $row->type_count;

			// Total for post:
			$cache_ctp_number[$filter_index][$row->comment_item_ID]['feedbacks']['total'] += $row->type_count;
		}
	}

	if( ($mode != 'comments') && ($mode != 'trackbacks') && ($mode != 'pingbacks') )
	{
		$mode = 'feedbacks';
	}

	if( is_array( $status ) )
	{ // $status is an array and probably contains more then one visibility status
		$result = 0;
		foreach( $status as $one_status )
		{
			if( isset( $cache_ctp_number[$filter_index][$post_id][$mode][$one_status] ) )
			{
				$result = $result + $cache_ctp_number[$filter_index][$post_id][$mode][$one_status];
			}
		}
	}
	elseif( isset( $cache_ctp_number[$filter_index][$post_id][$mode][$status] ) )
	{ // $status is a string with one visibility status
		$result = $cache_ctp_number[$filter_index][$post_id][$mode][$status];
	}
	else
	{ // $status is not recognized return total feedback number
		$result = $cache_ctp_number[$filter_index][$post_id][$mode]['total'];
	}

	// pre_dump( $cache_ctp_number[$filter_index][$post_id] );

	return $result;
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

	if( empty( $post_ID ) )
	{
		global $id;
		$post_ID = $id;
	}

	// attila> This function is called only from the backoffice ( _item_list_full.view.php ).
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
	global $Blog, $current_User, $highest_publish_status;

	// ---------- SAVE ------------
	$Form->submit( array( 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) );

	// ---------- PUBLISH ---------
	list( $highest_publish_status, $publish_text ) = get_highest_publish_status( 'comment', $Blog->ID );
	$current_status_value = get_status_permvalue( $edited_Comment->status );
	$highest_status_value = get_status_permvalue( $highest_publish_status );
	$Form->hidden( 'publish_status', $highest_publish_status );
	if( ( $current_status_value < $highest_status_value ) && ( $highest_publish_status != 'draft' )
		&& $current_User->check_perm( 'comment!'.$highest_publish_status, 'edit', false, $edited_Comment ) )
	{ // User may publish this comment with a "more public" status
		 $publish_style = 'display: inline';
	}
	else
	{
		$publish_style = 'display: none';
	}
	$Form->submit( array(
		'actionArray[update_publish]',
		$publish_text,
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
	global $next_action, $highest_publish_status;
	?>
	<script type="text/javascript">
	jQuery( '#commentform_visibility input[type=radio]' ).click( function()
	{
		var commentpublish_btn = jQuery( '.edit_actions input[name="actionArray[update_publish]"]' );
		var public_status = '<?php echo $highest_publish_status; ?>';

		if( this.value == public_status || public_status == 'draft' )
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
	global $admin_url;

	$url = rawurlencode( get_ban_domain( $url ) );
	$ban_url = $admin_url.'?ctrl=antispam&amp;action=ban&amp;keyword='.$url.'&amp;'.url_crumb('antispam');

	return '<a id="ban_url" href="'.$ban_url.'" onclick="ban_url(\''.$url.'\'); return false;">'.get_icon( 'ban' ).'</a>';
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
		$SQL = new SQL( 'Get number of trash comments' );
		$SQL->SELECT( 'COUNT( comment_ID )' );
		$SQL->FROM( 'T_comments' );
		$SQL->FROM_add( 'INNER JOIN T_items__item ON comment_item_ID = post_ID' );
		$SQL->FROM_add( 'INNER JOIN T_categories ON post_main_cat_ID = cat_ID' );
		$SQL->WHERE( 'comment_status = "trash"' );
		if( isset( $blog ) )
		{
			$SQL->WHERE_and( 'cat_blog_ID = '.$DB->quote( $blog ) );
		}
		$show_recycle_bin = ( $DB->get_var( $SQL->get() ) > 0 );
	}

	$result = '<div id="recycle_bin">';
	if( $show_recycle_bin )
	{ // show "Open recycle bin"
		global $CommentList;
		$comment_list_param_prefix = 'cmnt_fullview_';
		if( !empty( $CommentList->param_prefix ) )
		{
			$comment_list_param_prefix = $CommentList->param_prefix;
		}
		$result .= '<span class="floatright">'.action_icon( T_('Open recycle bin'), 'recycle_full',
						$admin_url.'?ctrl=comments&amp;blog='.$blog.'&amp;'.$comment_list_param_prefix.'show_statuses[]=trash', T_('Open recycle bin'), 5, 3 ).'</span> ';
	}
	return $result.'</div>';
}


/**
 * Display disabled comment form
 *
 * @param string Blog allow comments settings value
 * @param string Item url, where this comment form should be displayed
 * @param array Skin params
 */
function echo_disabled_comments( $allow_comments_value, $item_url, $params = array() )
{
	global $Settings, $current_User;

	$params = array_merge( array(
			'comments_disabled_text_member'     => T_( 'You must be a member of this blog to comment.' ),
			'comments_disabled_text_registered' => T_( 'You must be logged in to leave a comment.' ),
			'comments_disabled_text_validated'  => T_( 'You must activate your account before you can leave a comment.' ),
			'form_comment_text'                 => T_('Comment text'),
		), $params );

	if( empty( $params['form_params'] ) )
	{
		$params['form_params'] = array();
	}

	$params['form_params'] = array_merge( array(
			'formstart'      => '',
			'formend'        => '',
			'fieldset_begin' => '<fieldset>',
			'fieldset_end'   => '</fieldset>',
			'fieldstart'     => '',
			'fieldend'       => '',
			'labelstart'     => '<div class="label"><label for="p">',
			'labelend'       => '</label></div>',
			'inputstart'     => '<div class="input">',
			'inputend'       => '</div>',
		), $params['form_params'] );

	switch( $allow_comments_value )
	{
		case 'member':
			$disabled_text = $params['comments_disabled_text_member'];
			break;

		case 'registered':
			$disabled_text = $params['comments_disabled_text_registered'];
			break;

		default:
			// case any or never, in this case comment form is already displayed, or comments are not allowed at all.
			return;
	}

	$login_link = '';
	$activateinfo_link = '';
	$is_logged_in = is_logged_in();
	if( !$is_logged_in )
	{ // user is not logged in
		$login_link = '<a href="'.get_login_url( 'cannot comment', $item_url ).'">'.T_( 'Log in now!' ).'</a>';
	}
	elseif( $current_User->check_status( 'can_be_validated' ) )
	{ // logged in but the account is not activated
		$disabled_text = $params['comments_disabled_text_validated'];
		$activateinfo_link = '<a href="'.get_activate_info_url( $item_url ).'">'.T_( 'More info &raquo;' ).'</a>';
	}
	// else -> user is logged in and account was activated

	$register_link = '';
	if( ( !$is_logged_in ) && ( $Settings->get( 'newusers_canregister' ) ) && ( $Settings->get( 'registration_is_public' ) ) )
	{
		$register_link = '<p>'.sprintf( T_( 'If you have no account yet, you can <a href="%s">register now</a>...<br />(It only takes a few seconds!)' ), get_user_register_url( $item_url, 'reg to post comment' ) ).'</p>';
	}

	// disabled comment form
	echo '<form class="bComment" action="">';

	echo $params['form_params']['formstart'];

	echo $params['form_params']['fieldset_begin'];

	echo '<div class="comment_posting_disabled_msg">';
	if( $is_logged_in )
	{
		echo '<p>'.$disabled_text.' '.$activateinfo_link.'</p>';
	}
	else
	{ // not logged in, add login and register links
		echo '<p>'.$disabled_text.' '.$login_link.'</p>';
		echo $register_link;
	}
	echo '</div>';

	echo $params['form_params']['fieldset_end'];

	echo $params['form_params']['fieldset_begin'];
	echo $params['form_params']['fieldstart'];
	echo $params['form_params']['labelstart'].$params['form_comment_text'].':'.$params['form_params']['labelend'];
	echo $params['form_params']['inputstart'];
	echo '<textarea id="p" class="bComment form_textarea_input" rows="5" name="p" cols="40" disabled="disabled">'.$disabled_text.'</textarea>';
	echo $params['form_params']['inputend'];
	echo $params['form_params']['fieldend'];
	echo $params['form_params']['fieldset_end'];

	echo $params['form_params']['formend'];

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


/**
 * Dispay the replies of a comment
 *
 * @param integer Comment ID
 * @param array Template params
 * @param integer Level
 */
function display_comment_replies( $comment_ID, $params = array(), $level = 1 )
{
	global $CommentReplies;

	$params = array_merge( array(
			'comment_template'    => '_item_comment.inc.php',
			'preview_block_start' => '',
			'preview_start'       => '<div class="bComment" id="comment_preview">',
			'preview_end'         => '</div>',
			'preview_block_end'   => '',
			'comment_start'       => '<div class="bComment">',
			'comment_end'         => '</div>',
			'comment_error_start' => '<div class="bComment" id="comment_error">',
			'comment_error_end'   => '</div>',
			'link_to'             => 'userurl>userpage', // 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
			'author_link_text'    => 'login', // avatar | only_avatar | login | nickname | firstname | lastname | fullname | preferredname
		), $params );

	if( isset( $CommentReplies[ $comment_ID ] ) )
	{ // This comment has the replies
		foreach( $CommentReplies[ $comment_ID ] as $Comment )
		{ // Loop through the replies:
			if( empty( $Comment->ID ) )
			{ // Get html tag of the comment block of preview
				$comment_start = $Comment->email_is_detected ? $params['comment_error_start'] : $params['preview_start'];
			}
			else
			{ // Get html tag of the comment block of existing comment
				$comment_start = $params['comment_start'];
			}

			// Set margin left for each sub level comment
			$attrs = ' style="margin-left:'.( 20 * $level ).'px"';
			if( strpos( $comment_start, 'class="' ) === false )
			{ // Add a class attribute for the replied comment
				$attrs .= ' class="replied_comment"';
			}
			else
			{ // Add a class name for the replied comment
				$comment_start = str_replace( 'class="', 'class="replied_comment ', $comment_start );
			}
			$comment_start = str_replace( '>', $attrs.'>', $comment_start );

			if( ! empty( $Comment->ID ) )
			{ // Comment from DB
				skin_include( $params['comment_template'], array(
						'Comment'          => & $Comment,
						'comment_start'    => $comment_start,
						'comment_end'      => $params['comment_end'],
						'link_to'          => $params['link_to'],		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
						'author_link_text' => $params['author_link_text'],
					) );
			}
			else
			{ // PREVIEW comment
				skin_include( $params['comment_template'], array(
						'Comment'              => & $Comment,
						'comment_block_start'  => $Comment->email_is_detected ? '' : $params['preview_block_start'],
						'comment_start'        => $comment_start,
						'comment_end'          => $Comment->email_is_detected ? $params['comment_error_end'] : $params['preview_end'],
						'comment_block_end'    => $Comment->email_is_detected ? '' : $params['preview_block_end'],
						'author_link_text'     => $params['author_link_text'],
					) );
			}

			// Display the rest replies recursively
			display_comment_replies( $Comment->ID, $params, $level + 1 );
		}
	}
}


/**
 * JS Behaviour: Output JavaScript code to reply the comments
 *
 * @param object Item
 */
function echo_comment_reply_js( $Item )
{
	global $Blog;

	if( !isset( $Blog ) )
	{
		return false;
	}

	if( !$Blog->get_setting( 'threaded_comments' ) )
	{
		return false;
	}

?>
<script type="text/javascript">
jQuery( 'a.comment_reply' ).click( function()
{	// The click action for the links "Reply to this comment"
	var comment_ID = jQuery( this ).attr( 'rel' );

	// Remove data of a previous comment
	jQuery( 'a.comment_reply_current' ).remove();
	jQuery( 'input[name=reply_ID]' ).remove();
	jQuery( 'a.comment_reply' ).removeClass( 'active' )
		.html( '<?php echo TS_('Reply to this comment') ?>' );

	// Add data for a current comment
	var link_back_comment = '<a href="<?php echo url_add_param( $Item->get_permanent_url(), 'reply_ID=\' + comment_ID + \'&amp;redir=no' ) ?>#c' + comment_ID + '" class="comment_reply_current" rel="' + comment_ID + '"><?php echo TS_('You are currently replying to a specific comment') ?></a>';
	var hidden_reply_ID = '<input type="hidden" name="reply_ID" value="' + comment_ID + '" />';
	jQuery( '#bComment_form_id_<?php echo $Item->ID; ?>' ).prepend( link_back_comment + hidden_reply_ID );

	jQuery( this ).addClass( 'active' )
		.html( '<?php echo TS_('You are currently replying to this comment') ?>' );
	// Scroll to the comment form
	jQuery( window ).scrollTop( jQuery( '#bComment_form_id_<?php echo $Item->ID ?>' ).offset().top - 30 );

	return false;
} );

jQuery( document ).on( 'click', 'a.comment_reply_current', function()
{	// The click action for a link "You are currently replying to a specific comment"
	var comment_ID = jQuery( this ).attr( 'rel' );

	// Scroll to the comment
	jQuery( window ).scrollTop( jQuery( 'a#c' + comment_ID ).offset().top - 10 );

	return false;
} );
</script>
<?php

}


/**
 * JS Behaviour: Output JavaScript code to moderate the comments
 * Vote on the comment
 * Change a status of the comment
 */
function echo_comment_moderate_js()
{
	if( !is_logged_in( false ) )
	{
		return false;
	}

	global $Blog;

	if( empty( $Blog ) )
	{
		return false;
	}
?>
<script type="text/javascript">
/* <![CDATA[ */
function fadeIn( selector, color )
{
	if( jQuery( selector ).length == 0 )
	{
		return;
	}
	if( jQuery( selector ).get(0).tagName == 'TR' )
	{ // Fix selector, <tr> cannot have a css property background-color
		selector = selector + ' td';
	}
	var bg_color = jQuery( selector ).css( 'backgroundColor' );
	jQuery( selector ).animate( { backgroundColor: color }, 200 );
	return bg_color;
}

function fadeInStatus( selector, status )
{
	switch( status )
	{
		case 'published':
			return fadeIn( selector, '#99EE44' );
		case 'community':
			return fadeIn( selector, '#2E8BB9' );
		case 'protected':
			return fadeIn( selector, '#FF9C2A' );
		case 'review':
			return fadeIn( selector, '#CC0099' );
	}
}

// Display voting tool when JS is enable
jQuery( '.vote_spam' ).show();

// Set comments vote
function setCommentVote( id, type, vote )
{
	var row_selector = '#comment_' + id;
	var highlight_class = '';
	var color = '';
	switch(vote)
	{
		case 'spam':
			color = fadeIn( row_selector, '#ffc9c9' );
			highlight_class = 'roundbutton_red';
			break;
		case 'notsure':
			color = fadeIn( row_selector, '#bbbbbb' );
			break;
		case 'ok':
			color = fadeIn( row_selector, '#bcffb5' );
			highlight_class = 'roundbutton_green';
			break;
	}

	if( highlight_class != '' )
	{
		jQuery( '#vote_'+type+'_'+id ).find( 'a.roundbutton, span.roundbutton' ).addClass( highlight_class );
	}

	jQuery.ajax({
	type: "POST",
	url: "<?php echo get_samedomain_htsrv_url(); ?>anon_async.php",
	data:
		{ "blogid": "<?php echo $Blog->ID; ?>",
			"commentid": id,
			"type": type,
			"vote": vote,
			"action": "set_comment_vote",
			"crumb_comment": "<?php echo get_crumb('comment'); ?>",
		},
	success: function(result)
		{
			if( color != '' )
			{ // Revert the color
				fadeIn( row_selector, color );
			}
			jQuery("#vote_"+type+"_"+id).after( ajax_debug_clear( result ) );
			jQuery("#vote_"+type+"_"+id).remove();
		}
	});
}

// Set comment status
function setCommentStatus( id, status, redirect_to )
{
	var row_selector = '[id=comment_' + id + ']';
	var color = fadeInStatus( row_selector, status );

	jQuery.ajax({
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
	data:
		{ 'blogid': '<?php echo $Blog->ID; ?>',
			'commentid': id,
			'status': status,
			'action': 'moderate_comment',
			'redirect_to': redirect_to,
			'crumb_comment': '<?php echo get_crumb('comment'); ?>',
		},
	success: function(result)
		{
			if( color != '' )
			{ // Revert the color
				fadeIn( row_selector, color );
			}
			var statuses = ajax_debug_clear( result ).split( ':' );
			var new_status = statuses[0];
			if( new_status == '' )
			{ // Status was not changed
				return;
			}
			var class_name = jQuery( row_selector ).attr( 'class' );
			class_name = class_name.replace( /vs_([a-z]+)/g, 'vs_' + new_status );
			jQuery( row_selector ).attr( 'class', class_name );
			update_moderation_buttons( row_selector, statuses[1], statuses[2] );
		}
	});
}

// Add classes for first and last roundbuttons, because css pseudo-classes don't support to exclude hidden elements
function update_moderation_buttons( selector, raise_status, lower_status )
{
	var parent_selector = '.roundbutton_group ';
	if( typeof( selector ) != 'undefined' )
	{
		parent_selector = selector + ' ' + parent_selector;
	}
	selector = parent_selector + '.roundbutton_text';

	// Clear previous classes of first and last visible buttons
	jQuery( selector ).removeClass( 'first-child last-child btn_next_status' );
	// Make the raise and lower button are visible
	jQuery( selector + '.btn_raise_' + raise_status ).addClass( 'btn_next_status' );
	jQuery( selector + '.btn_lower_' + lower_status ).addClass( 'btn_next_status' );
	// Add classes for first and last buttons to fix round corners
	jQuery( selector + ':visible:first' ).addClass( 'first-child' );
	jQuery( selector + ':visible:last' ).addClass( 'last-child' );
}
/* ]]> */
</script>
<?php
}


/**
 * Check to display a form to mass delete the comments
 *
 * @param object Comment List
 * @return boolean TRUE - if a form is available
 */
function check_comment_mass_delete( $CommentList )
{
	global $current_User;
	if( !$current_User->check_perm( 'blogs', 'all' ) )
	{	// Check permission
		return false;
	}

	if( $CommentList->get_total_rows() == 0 )
	{	// No comments for current list
		return false;
	}

	// Form is available to mass delete the comments
	return true;
}


/**
 * Dispay a form to mass delete the comments
 *
 * @param object Comment List
 */
function display_comment_mass_delete( $CommentList )
{

	$action = param_action();

	if( $action != 'mass_delete' )
	{	// Display this form only for action "mass_delete"
		return;
	}

	if( !check_comment_mass_delete( $CommentList ) )
	{	// Form is not available
		return;
	}

	require dirname(__FILE__).'/../views/_comment_mass.form.php';
}


/**
 * Delete the comments
 *
 * @param string Type of deleting:
 *               'recycle' - to move into recycle bin
 *               'delete' - to delete permanently
 * @param string sql query to get deletable comment ids
 */
function comment_mass_delete_process( $mass_type, $deletable_comments_query )
{
	if( $mass_type != 'recycle' && $mass_type != 'delete' )
	{ // Incorrect action
		return;
	}

	global $DB, $cache_comments_has_replies, $user_post_read_statuses, $cache_postcats;

	/**
	 * Disable log queries because it increases the memory and stops the process with error "Allowed memory size of X bytes exhausted..."
	 */
	$DB->log_queries = false;

	$Form = new Form();

	$Form->begin_form( 'fform' );

	$Form->begin_fieldset( T_('Mass deleting log') );

	echo T_('The comments are deleting...');
	evo_flush();

	$CommentCache = & get_CommentCache();
	$ItemCache = & get_ItemCache();
	$ChapterCache = & get_ChapterCache();

	// Get the comments by 1000 to avoid an exhausting of memory
	$deletable_comment_ids = $DB->get_col( $deletable_comments_query.' LIMIT 1000' );
	while( !empty( $deletable_comment_ids ) )
	{
		// Get the first slice of the deletable comment ids list
		$ids = array_splice( $deletable_comment_ids, 0, 100 );
		// Make sure the CommentCache is empty
		$CommentCache->clear();
		// Load deletable comment ids
		$CommentCache->load_list( $ids );

		while( ( $iterator_Comment = & $CommentCache->get_next() ) != NULL )
		{ // Delete all comments from CommentCache
			$iterator_Comment->dbdelete( $mass_type == 'delete' );
		}

		// Display progress dot
		echo ' .';
		evo_flush();

		if( empty( $deletable_comment_ids ) )
		{
			// Clear all caches to save memory
			$ItemCache->clear();
			$ChapterCache->clear();
			$cache_comments_has_replies = array();
			$user_post_read_statuses = array();
			$cache_postcats = array();

			// Get new portion of deletable comments
			$deletable_comment_ids = $DB->get_col( $deletable_comments_query.' LIMIT 1000' );
		}
	}

	echo ' OK';

	$Form->end_form();

	// Clear a comment cache
	$CommentCache->clear();
}


/**
 * Delete the comments by keyword
 *
 * @param string SQL "where" clause
 * @param array comment ids to delete - it should be set when the ids are known instead of the "where" clause
 * @return mixed integer the number of the deleted comments on success, false on failure
 */
function comments_delete_where( $sql_where, $comment_ids = NULL )
{
	global $DB;

	if( !empty( $sql_where ) )
	{ // Get all comments that should be deleted
		$comments_SQL = new SQL();
		$comments_SQL->SELECT( 'comment_ID' );
		$comments_SQL->FROM( 'T_comments' );
		$comments_SQL->WHERE( $sql_where );
		$delete_comment_ids = $comments_SQL->get();
	}
	elseif( !empty( $comment_ids ) )
	{
		$delete_comment_ids = implode( ', ', $comment_ids );
		$sql_where = 'comment_ID IN ( '.$delete_comment_ids.' )';
	}
	else
	{ // Delete condition was not set
		return 0;
	}

	$DB->begin();

	// Get the files of these comments
	$files_SQL = new SQL();
	$files_SQL->SELECT( 'link_file_ID' );
	$files_SQL->FROM( 'T_links' );
	$files_SQL->WHERE( 'link_cmt_ID IN ( '.$delete_comment_ids.' )' );
	$files_IDs = $DB->get_col( $files_SQL->get() );

	// Delete the comment data from the related tables
	$temp_Comment = new Comment();
	if( ! empty( $temp_Comment->delete_cascades ) )
	{
		foreach( $temp_Comment->delete_cascades as $cascade )
		{
			$DB->query( 'DELETE
				 FROM '.$cascade['table'].'
				WHERE '.$cascade['fk'].' IN ( '.$delete_comment_ids.' )',
				'Delete the related records of the comments' );
		}
	}

	if( count( $files_IDs ) )
	{ // The deleted comments have some files, we can delete the files only if they are not used by other side
		$used_files_SQL = new SQL();
		$used_files_SQL->SELECT( 'link_file_ID' );
		$used_files_SQL->FROM( 'T_links' );
		$used_files_SQL->WHERE( 'link_file_ID IN ( '.implode( ', ', $files_IDs ).' )' );
		$used_files_IDs = $DB->get_col( $used_files_SQL->get() );

		$delete_folders = array();
		$unused_files_IDs = array_diff( $files_IDs, $used_files_IDs );
		if( count( $unused_files_IDs ) )
		{
			$FileCache = & get_FileCache();
			$index = 0; // use this counter to load only a portion of files into the cache
			foreach( $unused_files_IDs as $unused_file_ID )
			{
				if( ( $index % 100 ) == 0 )
				{ // Clear the cache to save memory and load the next portion list of files
					$FileCache->clear();
					$FileCache->load_list( array_slice( $unused_files_IDs, $index, 100 ) );
				}
				$index++;
				if( $comment_File = & $FileCache->get_by_ID( $unused_file_ID, false ) )
				{ // Delete a file from disk and from DB
					$rdfp_rel_path = $comment_File->get_rdfp_rel_path();
					$folder_path = dirname( $comment_File->get_full_path() );
					if( $comment_File->unlink( false ) &&
						( preg_match( '/^(anonymous_)?comments\/p(\d+)\/.*$/', $rdfp_rel_path ) ) &&
					    ! in_array( $folder_path, $delete_folders ) )
					{ // Collect comment attachments folders to delete the empty folders later
						$delete_folders[] = $folder_path;
					}
				}
			}
		}

		// Delete the empty folders
		if( count( $delete_folders ) )
		{
			foreach( $delete_folders as $delete_folder )
			{
				if( file_exists( $delete_folder ) )
				{ // Delete folder only if it is empty, Hide an error if folder is not empty
					@rmdir( $delete_folder );
				}
			}
		}
	}

	// Delete the comment prerendering contents
	$DB->query( 'DELETE
		 FROM T_comments__prerendering
		WHERE cmpr_cmt_ID IN ( '.$delete_comment_ids.' )',
		'Delete the comment prerendering contents' );

	// Delete all comments
	// asimo> If a comment with this keyword content was inserted here, the user will not even observe that (This is good)
	$r = $DB->query( 'DELETE
		 FROM T_comments
		WHERE '.$sql_where,
		'Delete the comments by where clause' );

	if( $r !== false )
	{ // Success on delete the comments
		$DB->commit();
		return $r;
	}
	else
	{ // Failed
		$DB->rollback();
		return false;
	}
}


/**
 * Display comments results table
 *
 * @param array Params
 */
function comments_results_block( $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'edited_User'          => NULL,
			'results_param_prefix' => 'actv_comment_',
			'results_title'        => T_('Comments posted by the user'),
			'results_no_text'      => T_('User has not posted any comment yet'),
		), $params );

	if( !is_logged_in() )
	{	// Only logged in users can access to this function
		return;
	}

	global $current_User;
	if( !$current_User->check_perm( 'users', 'edit' ) )
	{	// Check minimum permission:
		return;
	}

	$edited_User = $params['edited_User'];
	if( !$edited_User )
	{	// No defined User, probably the function is calling from AJAX request
		$user_ID = param( 'user_ID', 'integer', 0 );
		if( empty( $user_ID ) )
		{	// Bad request, Exit here
			return;
		}
		$UserCache = & get_UserCache();
		if( ( $edited_User = & $UserCache->get_by_ID( $user_ID, false ) ) === false )
		{	// Bad request, Exit here
			return;
		}
	}

	global $DB, $AdminUI;

	param( 'user_tab', 'string', '', true );
	param( 'user_ID', 'integer', 0, true );


	$SQL = new SQL();
	$SQL->SELECT( '*' );
	$SQL->FROM( 'T_comments' );
	$SQL->WHERE( 'comment_author_user_ID = '.$DB->quote( $edited_User->ID ) );

	// Create result set:
	$comments_Results = new Results( $SQL->get(), $params['results_param_prefix'], 'D' );
	$comments_Results->Cache = & get_CommentCache();
	$comments_Results->title = $params['results_title'];
	$comments_Results->no_results_text = $params['results_no_text'];

	if( $comments_Results->get_total_rows() > 0 && $edited_User->has_comment_to_delete() )
	{	// Display action icon to delete all records if at least one record exists & current user can delete at least one comment posted by user
		$comments_Results->global_icon( sprintf( T_('Delete all comments posted by %s'), $edited_User->login ), 'delete', '?ctrl=user&amp;user_tab=activity&amp;action=delete_all_comments&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb('user'), ' '.T_('Delete all'), 3, 4 );
	}

	// Initialize Results object
	comments_results( $comments_Results, array(
			'field_prefix' => 'comment_',
			'display_kind' => false,
			'display_additional_columns' => true,
			'plugin_table_name' => 'activity',
			'display_spam' => false,
		) );

	if( is_ajax_content() )
	{	// init results param by template name
		if( !isset( $params[ 'skin_type' ] ) || ! isset( $params[ 'skin_name' ] ) )
		{
			debug_die( 'Invalid ajax results request!' );
		}
		$comments_Results->init_params_by_skin( $params[ 'skin_type' ], $params[ 'skin_name' ] );
	}

	$results_params = $AdminUI->get_template( 'Results' );
	$display_params = array(
		'before' => str_replace( '>', ' style="margin-top:25px" id="comments_result">', $results_params['before'] ),
	);
	$comments_Results->display( $display_params );

	if( !is_ajax_content() )
	{	// Create this hidden div to get a function name for AJAX request
		echo '<div id="'.$params['results_param_prefix'].'ajax_callback" style="display:none">'.__FUNCTION__.'</div>';
	}
}


/**
 * Initialize Results object for comments list
 *
 * @param object Results
 * @param array Params
 */
function comments_results( & $comments_Results, $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'field_prefix' => '',
			'display_date' => true,
			'display_permalink' => true,
			'display_item' => false,
			'display_status' => false,
			'display_kind' => true,
			'display_author' => true,
			'display_url' => true,
			'display_email' => true,
			'display_ip' => true,
			'display_additional_columns' => false,
			'plugin_table_name' => '',
			'display_spam' => true,
			'display_visibility' => true,
			'display_actions' => true,
			'col_post' => T_('Post'),
		), $params );

	if( $params['display_date'] )
	{	// Display Date column
		$td = '<span class="date">@date()@</span>';
		if( $params['display_permalink'] )
		{
			$td = '@get_permanent_link( get_icon(\'permalink\') )@ '.$td;
		}
		$comments_Results->cols[] = array(
				'th' => T_('Date'),
				'order' => $params['field_prefix'].'date',
				'default_dir' => 'D',
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => $td,
			);
	}

	if( $params['display_item'] )
	{	// Display Date column
		$td = '%{Obj}->get_permanent_item_link()%';
		if( $params['display_status'] )
		{ // Dislpay status banner
			$td = '%{Obj}->get_status( "styled" )%'.$td;
		}
		$comments_Results->cols[] = array(
				'th' => $params['col_post'],
				'order' => $params['field_prefix'].'item_ID',
				'td' => $td,
			);
	}

	if( $params['display_kind'] )
	{	// Display Kind column
		$comments_Results->cols[] = array(
				'th' => T_('Kind'),
				'order' => $params['field_prefix'].'type',
				'th_class' => 'nowrap',
				'td' => '%get_type( {Obj} )%',
			);
	}

	if( $params['display_author'] )
	{	// Display Author column
		$comments_Results->cols[] = array(
				'th' => T_('Author'),
				'order' => $params['field_prefix'].'author',
				'th_class' => 'nowrap',
				'td' => '%get_author( {Obj} )%',
			);
	}

	if( $params['display_url'] )
	{	// Display Url column
		$comments_Results->cols[] = array(
				'th' => T_('URL'),
				'order' => $params['field_prefix'].'author_url',
				'th_class' => 'nowrap',
				'td' => '%get_url( {Obj} )%',
			);
	}

	if( $params['display_email'] )
	{	// Display Email column
		$comments_Results->cols[] = array(
				'th' => T_('Email'),
				'order' => $params['field_prefix'].'author_email',
				'th_class' => 'nowrap',
				'td_class' => 'nowrap',
				'td' => '%get_author_email( {Obj} )%',
			);
	}

	if( $params['display_ip'] )
	{	// Display IP column
		$comments_Results->cols[] = array(
				'th' => T_('IP'),
				'order' => $params['field_prefix'].'author_IP',
				'th_class' => 'nowrap',
				'td_class' => 'nowrap',
				'td' => '%get_author_ip( {Obj}, "'.$comments_Results->param_prefix.'" )%',
			);
	}


	if( $params['display_additional_columns'] )
	{	// Display additional columns from the Plugins
		global $Plugins;
		$Plugins->trigger_event( 'GetAdditionalColumnsTable', array(
			'table'   => $params['plugin_table_name'],
			'column'  => $params['field_prefix'].'author_IP',
			'Results' => $comments_Results ) );
	}

	if( $params['display_spam'] )
	{	// Display Spam karma column
		$comments_Results->cols[] = array(
				'th' => T_('Spam karma'),
				'order' => $params['field_prefix'].'spam_karma',
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => '%get_spam_karma( {Obj} )%'
			);
	}

	if( $params['display_visibility'] )
	{	// Display Visibility column
		$comments_Results->cols[] = array(
				'th' => T_('Visibility'),
				'order' => $params['field_prefix'].'status',
				'th_class' => 'nowrap',
				'td_class' => 'shrinkwrap',
				'td' => '%{Obj}->get_status( "styled" )%',
			);
	}

	if( $params['display_actions'] )
	{	// Display Actions column
		$comments_Results->cols[] = array(
				'th' => T_('Actions'),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => '%comment_edit_actions( {Obj} )%'
			);
	}
}


/**
 * Helper functions to display Comments results.
 * New ( not display helper ) functions must be created above comments_results function
 */

/**
 * Get comment type.
 *
 * @param object Comment
 * @return string Comment type OR '---' if user has no permission to moderate this comment
 */
function get_type( $Comment )
{
	global $current_User;

	if( $current_User->check_perm( 'comment!CURSTATUS', 'moderate', false, $Comment ) )
	{
		return $Comment->get( 'type' );
	}
	else
	{
		return '<span class="dimmed">---</span>';
	}
}


/**
 * Get comment author
 *
 * @param object Comment
 * @return string Comment author OR '---' if user has no permission to moderate this comment
 */
function get_author( $Comment )
{
	global $current_User;

	if( $Comment->get( 'status' ) == 'published' || $current_User->check_perm( 'comment!CURSTATUS', 'moderate', false, $Comment ) )
	{
		$author_User = $Comment->get_author_User();
		if( $author_User != NULL )
		{	// author is a registered user
			return $author_User->get_identity_link();
		}
		// author is not a registered user
		return $Comment->get_author( array( 'link_to' => 'userpage' )  );
	}
	else
	{
		return '<span class="dimmed">---</span>';
	}
}


/**
 * Get comment author url
 *
 * @param object Comment
 * @return string Comment url OR '---' if user has no permission to moderate this comment
 */
function get_url( $Comment )
{
	global $current_User;

	if( $current_User->check_perm( 'comment!CURSTATUS', 'moderate', false, $Comment ) )
	{
		return $Comment->author_url_with_actions( NULL, false );
	}
	else
	{
		return '<span class="dimmed">---</span>';
	}
}


/**
 * Get comment author email
 *
 * @param object Comment
 * @return string Comment author email OR '---' if user has no permission to moderate this comment
 */
function get_author_email( $Comment )
{
	global $current_User;

	if( $current_User->check_perm( 'comment!CURSTATUS', 'moderate', false, $Comment ) )
	{
		return $Comment->get_author_email();
	}
	else
	{
		return '<span class="dimmed">---</span>';
	}
}


/**
 * Get comment author IP
 *
 * @param object Comment
 * @return string Comment author IP OR '---' if user has no permission to moderate this comment
 */
function get_author_ip( $Comment, $param_prefix = '' )
{
	global $current_User;

	if( $current_User->check_perm( 'comment!CURSTATUS', 'moderate', false, $Comment ) )
	{
		$filter_IP_url = regenerate_url( 'filter', $param_prefix.'author_IP='.$Comment->get( 'author_IP' ) );
		$country = $Comment->get_ip_country( ' ' );
		return '<a href="'.$filter_IP_url.'">'.$Comment->get( 'author_IP' ).'</a>'.$country;
	}
	else
	{
		return '<span class="dimmed">---</span>';
	}
}


/**
 * Get comment spam karma
 *
 * @param object Comment
 * @return string Comment spam karma OR '---' if user has no permission to moderate this comment
 */
function get_spam_karma( $Comment )
{
	global $current_User;

	if( $current_User->check_perm( 'comment!CURSTATUS', 'moderate', false, $Comment ) )
	{
		return $Comment->get( 'spam_karma' );
	}
	else
	{
		return '<span class="dimmed">---</span>';
	}
}


/**
 * Get comment status
 *
 * @param object Comment
 * @return string Comment status OR '---' if user has no permission to edit this comment
 */
function get_colored_status( $Comment )
{
	return '<span class="tdComment'.$Comment->get( 'status' ).'">'.$Comment->get( 't_status' ).'</span>';
}


/**
 * Get template for the styled status of comment or item
 *
 * @param string Status value
 * @param string Status title
 * @return string Styled template for status
 */
function get_styled_status( $status_value, $status_title )
{
	return '<div class="floatright">'
		.'<span class="note status_'.$status_value.'">'
		.'<span>'.format_to_output( $status_title ).'</span>'
		.'</span>'
		.'</div>';
}


/**
 * Get the edit actions for comment
 *
 * @param object Comment
 * @return string The edit actions
 */
function comment_edit_actions( $Comment )
{
	global $current_User, $admin_url;

	$r = '';
	if( !is_logged_in() )
	{
		return $r;
	}

	$user_has_edit_perm = $current_User->check_perm( 'comment!CURSTATUS', 'edit', false, $Comment );
	$user_has_delete_perm = $current_User->check_perm( 'comment!CURSTATUS', 'delete', false, $Comment );

	if( $user_has_edit_perm || $user_has_delete_perm )
	{ // Display edit and delete button if current user has the rights:
		$redirect_to = rawurlencode( regenerate_url( 'comment_ID,action', 'filter=restore', '', '&' ) );

		if( $user_has_edit_perm )
		{ // Display edit button only if current user can edit comment with current status
			$Comment->get_Item();
			$item_Blog = & $Comment->Item->get_Blog();
			if( $item_Blog->get_setting( 'in_skin_editing' ) && !is_admin_page() )
			{
				$edit_url = url_add_param( $item_Blog->gen_blogurl(), 'disp=edit_comment&amp;c='.$Comment->ID );
			}
			else
			{
				$edit_url = $admin_url.'?ctrl=comments&amp;comment_ID='.$Comment->ID.'&amp;action=edit&amp;redirect_to='.$redirect_to;
			}
			$r .= action_icon( TS_('Edit this comment...'), 'properties', $edit_url );
		}

		if( $user_has_delete_perm )
		{ // Display delete/recycle button because current user has permission to delete/recycle this comment
			$params = array();
			if( $Comment->status == 'trash' )
			{ // Comment is already in the recycle bin, display delete action and add js confirm
				$title = T_('Delete this comment!');
				$params['onclick'] = "return confirm('".TS_('You are about to delete this comment!\\nThis cannot be undone!')."')";
			}
			else
			{ // Comment will be moved into the recycle bin
				$title = T_('Recycle this comment!');
			}

			$r .=  action_icon( $title, 'delete',
				$admin_url.'?ctrl=comments&amp;comment_ID='.$Comment->ID.'&amp;action=delete&amp;'.url_crumb('comment')
				.'&amp;redirect_to='.$redirect_to, NULL, NULL, NULL, $params );
		}
	}

	return $r;
}

/**
 * End of helper functions block to display Comments results.
 * New ( not display helper ) functions must be created above comments_results function
 */

?>