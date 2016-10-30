<?php
/**
 * This file implements Comment handling functions.
  *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 *
 * @todo implement CommentCache based on LinkCache
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

	$show_statuses = ( is_admin_page() || ( ! $filter_by_perm ) ) ? get_visibility_statuses( 'keys', array( 'trash', 'redirected' ) ) : get_inskin_statuses( $blog, 'comment' );
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
			$count_SQL->FROM_add( 'LEFT JOIN T_items__item_settings as expiry_setting ON comment_item_ID = iset_item_ID AND iset_name = "comment_expiry_delay"' );
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
						'feedbacks'  => $statuses_array,
						'metas'      => $statuses_array
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

				if( $row->comment_type != 'meta' )
				{ // Exclude meta comments from feedbacks
					// Total for status on post:
					$cache_ctp_number[$filter_index][$row->comment_item_ID]['feedbacks'][$row->comment_status] += $row->type_count;

					// Total for post:
					$cache_ctp_number[$filter_index][$row->comment_item_ID]['feedbacks']['total'] += $row->type_count;
				}
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
				'comments'   => $statuses_array,
				'trackbacks' => $statuses_array,
				'pingbacks'  => $statuses_array,
				'feedbacks'  => $statuses_array,
				'metas'      => $statuses_array
			);

		$count_SQL->WHERE_and( 'comment_item_ID = '.intval($post_id) );

		foreach( $DB->get_results( $count_SQL->get() ) as $row )
		{
			// detail by status, type and post:
			$cache_ctp_number[$filter_index][$row->comment_item_ID][$row->comment_type.'s'][$row->comment_status] = $row->type_count;

			// Total for type on post:
			$cache_ctp_number[$filter_index][$row->comment_item_ID][$row->comment_type.'s']['total'] += $row->type_count;

			if( $row->comment_type != 'meta' )
			{ // Exclude meta comments from feedbacks
				// Total for status on post:
				$cache_ctp_number[$filter_index][$row->comment_item_ID]['feedbacks'][$row->comment_status] += $row->type_count;

				// Total for post:
				$cache_ctp_number[$filter_index][$row->comment_item_ID]['feedbacks']['total'] += $row->type_count;
			}
		}
	}

	if( ! in_array( $mode, array( 'comments', 'trackbacks', 'pingbacks', 'metas' ) ) )
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
	global $Blog, $AdminUI;

	if( $edited_Comment->is_meta() )
	{ // Meta comments don't have a status, Display only one button to update
		$Form->submit( array( 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) );
	}
	else
	{ // Normal comments have a status, Display the buttons to change it and update
		if( $edited_Comment->status != 'trash' )
		{
			// ---------- VISIBILITY ----------
			echo T_('Visibility').get_manual_link( 'visibility-status' ).': ';
			// Get those statuses which are not allowed for the current User to create comments in this blog
			if( $edited_Comment->is_meta() )
			{	// Don't restrict statuses for meta comments:
				$restricted_statuses = array();
			}
			else
			{	// Restrict statuses for normal comments:
				$comment_Item = & $edited_Comment->get_Item();
				// Comment status cannot be more than post status, restrict it:
				$restrict_max_allowed_status = ( $comment_Item ? $comment_Item->status : '' );
				$restricted_statuses = get_restricted_statuses( $Blog->ID, 'blog_comment!', 'edit', $edited_Comment->status, $restrict_max_allowed_status );
			}
			$exclude_statuses = array_merge( $restricted_statuses, array( 'redirected', 'trash' ) );
			// Get allowed visibility statuses
			$status_options = get_visibility_statuses( '', $exclude_statuses );

			if( isset( $AdminUI, $AdminUI->skin_name ) && $AdminUI->skin_name == 'bootstrap' )
			{ // Use dropdown for bootstrap skin
				$status_icon_options = get_visibility_statuses( 'icons', $exclude_statuses );
				$Form->hidden( 'comment_status', $edited_Comment->status );
				echo '<div class="btn-group dropup comment_status_dropdown">';
				echo '<button type="button" class="btn btn-status-'.$edited_Comment->status.' dropdown-toggle" data-toggle="dropdown" aria-expanded="false" id="comment_status_dropdown">'
								.'<span>'.$status_options[ $edited_Comment->status ].'</span>'
							.' <span class="caret"></span></button>';
				echo '<ul class="dropdown-menu" role="menu" aria-labelledby="comment_status_dropdown">';
				foreach( $status_options as $status_key => $status_title )
				{
					echo '<li rel="'.$status_key.'" role="presentation"><a href="#" role="menuitem" tabindex="-1">'.$status_icon_options[ $status_key ].' <span>'.$status_title.'</span></a></li>';
				}
				echo '</ul>';
				echo '</div>';
			}
			else
			{ // Use standard select element for other skins
				echo '<select name="comment_status">';
				foreach( $status_options as $status_key => $status_title )
				{
					echo '<option value="'.$status_key.'"'
								.( $edited_Comment->status == $status_key ? ' selected="selected"' : '' )
								.' class="btn-status-'.$status_key.'">'
							.$status_title
						.'</option>';
				}
				echo '</select>';
			}
		}

		echo '<span class="btn-group">';

		// ---------- SAVE BUTTONS ----------
		$Form->submit( array( 'actionArray[update_edit]', /* TRANS: This is the value of an input submit button */ T_('Save & edit'), 'SaveEditButton btn-status-'.$edited_Comment->status ) );
		$Form->submit( array( 'actionArray[update]', T_('Save'), 'SaveButton btn-status-'.$edited_Comment->status ) );

		echo '</span>';
	}
}


/**
 * Display buttons to update a comment
 *
 * @param object Form
 * @param object edited Comment
 */
function echo_comment_status_buttons( $Form, $edited_Comment )
{
	global $Blog;

	if( $edited_Comment->is_meta() )
	{	// Don't suggest to change a status of meta comment:
		$Form->submit( array( 'actionArray[update]', T_('Save Changes!'), 'SaveButton', '' ) );
		return;
	}

	$comment_Item = & $edited_Comment->get_Item();
	// Comment status cannot be more than post status, restrict it:
	$restrict_max_allowed_status = ( $comment_Item ? $comment_Item->status : '' );

	// Get those statuses which are not allowed for the current User to create posts in this blog
	$exclude_statuses = array_merge( get_restricted_statuses( $Blog->ID, 'blog_comment!', 'edit', $edited_Comment->status, $restrict_max_allowed_status ), array( 'redirected', 'trash' ) );
	// Get allowed visibility statuses
	$status_options = get_visibility_statuses( 'button-titles', $exclude_statuses );
	$status_icon_options = get_visibility_statuses( 'icons', $exclude_statuses );

	$Form->hidden( 'comment_status', $edited_Comment->status );
	echo '<div class="btn-group dropup comment_status_dropdown">';
	echo '<button type="submit" class="btn btn-status-'.$edited_Comment->status.'" name="actionArray[update]">'
				.'<span>'.T_( $status_options[ $edited_Comment->status ] ).'</span>'
			.'</button>'
			.'<button type="button" class="btn btn-status-'.$edited_Comment->status.' dropdown-toggle" data-toggle="dropdown" aria-expanded="false" id="comment_status_dropdown">'
				.'<span class="caret"></span>'
			.'</button>';
	echo '<ul class="dropdown-menu" role="menu" aria-labelledby="comment_status_dropdown">';
	foreach( $status_options as $status_key => $status_title )
	{
		echo '<li rel="'.$status_key.'" role="presentation"><a href="#" role="menuitem" tabindex="-1">'.$status_icon_options[ $status_key ].' <span>'.T_( $status_title ).'</span></a></li>';
	}
	echo '</ul>';
	echo '</div>';
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

	$ban_domain = get_ban_domain( $url );
	$ban_url = $admin_url.'?ctrl=antispam&amp;action=ban&amp;keyword='.rawurlencode( $ban_domain ).'&amp;'.url_crumb('antispam');

	return '<a id="ban_url" href="'.$ban_url.'" onclick="ban_url(\''.$ban_domain.'\'); return false;">'.get_icon( 'ban' ).'</a>';
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
	{ // Current user has no permission to edit the spam contents
		return $content;
	}

	if( stristr( $content, '<code' ) !== false || stristr( $content, '<pre' ) !== false )
	{ // Add icons only outside <code> and <pre>
		return callback_on_non_matching_blocks( $content,
				'~<(code|pre)[^>]+class="codeblock"[^>]*>.*?</\1>~is',
				'add_ban_icons_callback' );
	}
	else
	{
		return add_ban_icons_callback( $content );
	}
}


/**
 * Callback function to add a javascript ban action icon after each url in the given content
 *
 * @param string Comment content
 * @return string the content with a ban icon after each url if the user has spamblacklist permission, the incoming content otherwise
 */
function add_ban_icons_callback( $content )
{
	$atags = get_atags( $content );
	$imgtags = get_imgtags( $content );
	$urls = get_urls( $content );
	$result = '';
	$from = 0; // current processing position
	$length = 0; // current url or tag length
	$i = 0; // url counter
	$j = 0; // "a" tag counter
	$k = 0; // "img" tag counter

	// Remove the duplicated <a> tags from array
	$atags_fixed = array();
	foreach( $atags as $atag )
	{
		if( preg_match( '#href="([^"]+)"#', $atag, $matches ) && ! isset( $atags_fixed[ $matches[1] ] ) )
		{
			$atags_fixed[ $matches[1] ] = $atag;
		}
	}
	$atags = array();
	foreach( $atags_fixed as $atag )
	{
		$atags[] = $atag;
	}

	$used_urls = array();
	while( isset( $urls[$i] ) )
	{ // there is unprocessed url
		$url = $urls[$i];
		if( validate_url( $url, 'posting', false ) )
		{ // skip not valid urls
			$i++;
			continue;
		}
		if( in_array( $url, $used_urls ) )
		{ // skip already passed url
			$i++;
			continue;
		}
		$used_urls[] = $url;
		while( isset( $imgtags[$k] ) && ( strpos( $content, $imgtags[$k] ) < $from ) )
		{ // skip already passed img tags
			$k++;
		}

		$pos = strpos( $content, $url, $from );
		$length = utf8_strlen($url);
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
 * @param array Additional params
 * @return Open recycle bin link if user has the corresponding 'blogs' - 'editall' permission, empty string otherwise
 */
function get_opentrash_link( $check_perm = true, $force_show = false, $params = array() )
{
	global $admin_url, $current_User, $DB, $blog;

	$params = array_merge( array(
			'before' => '<div id="recycle_bin" class="pull-right">',
			'after'  => ' </div>',
			'class'  => 'action_icon btn btn-default btn-sm',
		), $params );

	$show_recycle_bin = ( !$check_perm || $current_User->check_perm( 'blogs', 'editall' ) );
	if( $show_recycle_bin && ( !$force_show ) )
	{ // get number of trash comments:
		$SQL = new SQL( 'Get number of trash comments for open trash link' );
		$SQL->SELECT( 'COUNT( comment_ID )' );
		$SQL->FROM( 'T_comments' );
		$SQL->FROM_add( 'INNER JOIN T_items__item ON comment_item_ID = post_ID' );
		$SQL->FROM_add( 'INNER JOIN T_categories ON post_main_cat_ID = cat_ID' );
		$SQL->WHERE( 'comment_status = "trash"' );
		if( isset( $blog ) )
		{
			$SQL->WHERE_and( 'cat_blog_ID = '.$DB->quote( $blog ) );
		}
		$show_recycle_bin = ( $DB->get_var( $SQL->get(), 0, NULL, $SQL->title ) > 0 );
	}

	$result = $params['before'];
	if( $show_recycle_bin )
	{ // show "Open recycle bin"
		global $CommentList;
		$comment_list_param_prefix = 'cmnt_fullview_';
		if( !empty( $CommentList->param_prefix ) )
		{
			$comment_list_param_prefix = $CommentList->param_prefix;
		}
		$result .= action_icon( T_('Open recycle bin'), 'recycle_full',
						$admin_url.'?ctrl=comments&amp;blog='.$blog.'&amp;'.$comment_list_param_prefix.'show_statuses[]=trash', ' '.T_('Open recycle bin'), 5, 3, array( 'class' => $params['class'] ) );
	}
	return $result.$params['after'];
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
			'form_class_comment'                => 'bComment',
		), $params );

	if( empty( $params['form_params'] ) )
	{
		$params['form_params'] = array();
	}

	$params['form_params'] = array_merge( array(
			'comments_disabled_before' => '<div class="comment_posting_disabled_msg"><p>',
			'comments_disabled_after' => '</p></div>',
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
		$login_link = '<a class="btn btn-primary btn-sm" href="'.get_login_url( 'cannot comment', $item_url ).'">'.T_( 'Log in now!' ).'</a>';
	}
	elseif( $current_User->check_status( 'can_be_validated' ) )
	{ // logged in but the account is not activated
		$disabled_text = $params['comments_disabled_text_validated'];
		$activateinfo_link = '<a href="'.get_activate_info_url( $item_url, '&amp;' ).'">'.T_( 'More info &raquo;' ).'</a>';
	}
	// else -> user is logged in and account was activated

	$register_link = '';
	if( ( !$is_logged_in ) && ( $Settings->get( 'newusers_canregister' ) == 'yes' ) && ( $Settings->get( 'registration_is_public' ) ) )
	{
		$register_link = '<p>'.sprintf( T_( 'If you have no account yet, you can <a href="%s">register now</a>...<br />(It only takes a few seconds!)' ), get_user_register_url( $item_url, 'reg to post comment' ) ).'</p>';
	}

	// disabled comment form
	echo '<form class="'.$params['form_class_comment'].'" action="">';

	echo $params['form_params']['formstart'];

	echo $params['form_params']['fieldset_begin'];

	echo $params['form_params']['comments_disabled_before'];
	if( $is_logged_in )
	{
		echo $disabled_text.' '.$activateinfo_link;
	}
	else
	{ // not logged in, add login and register links
		echo $disabled_text.' '.$login_link;
		echo $register_link;
	}
	echo $params['form_params']['comments_disabled_after'];

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
 * @param object Comment
 * @param string Kind of session var: 'unsaved' or 'preview'
 * @param string Comment type: Meta or Normal
 */
function save_comment_to_session( $Comment, $kind = 'unsaved', $type = '' )
{
	global $Session;

	if( $type != 'meta' )
	{	// Use default type if it is not allowed:
		$type = '';
	}

	$Session->set( 'core.'.$kind.'_Comment'.$type, $Comment );
}


/**
 * Get Comment object from the current Session
 *
 * @param string Kind of session var: 'unsaved' or 'preview'
 * @param string Comment type: Meta or Normal
 * @return Comment|NULL Comment object if Session core.unsaved_Comment param is set, NULL otherwise
 */
function get_comment_from_session( $kind = 'unsaved', $type = '' )
{
	global $Session;

	if( $type != 'meta' )
	{	// Use default type if it is not allowed:
		$type = '';
	}

	if( ( $Comment = $Session->get( 'core.'.$kind.'_Comment'.$type ) ) && $Comment instanceof Comment )
	{	// If Comment is detected for current Session:

		// Delete Comment to clear Session data:
		$Session->delete( 'core.'.$kind.'_Comment'.$type );

		// Return Comment:
		return $Comment;
	}

	// Comment is not detected, Return NULL:
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
			'author_link_text'    => 'name', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
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
				skin_include( $params['comment_template'], array_merge( $params, array(
						'Comment'          => & $Comment,
						'comment_start'    => $comment_start
					) ) );
			}
			else
			{ // PREVIEW comment
				skin_include( $params['comment_template'], array_merge( $params, array(
						'Comment'              => & $Comment,
						'comment_block_start'  => $Comment->email_is_detected ? '' : $params['preview_block_start'],
						'comment_start'        => $comment_start,
						'comment_end'          => $Comment->email_is_detected ? $params['comment_error_end'] : $params['preview_end'],
						'comment_block_end'    => $Comment->email_is_detected ? '' : $params['preview_block_end'],
						'author_link_text'     => $params['author_link_text'],
					) ) );
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

	load_funcs( 'comments/model/_comment_js.funcs.php' );
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
			$iterator_Comment->dbdelete( ($mass_type == 'delete') );
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
 * Move all child comments to new post by parent comment ID
 *
 * @param integer/array Parent comment IDs
 * @param integer Item ID
 * @param boolean TRUE to convert a comment to root/top comment of the post
 */
function move_child_comments_to_item( $comment_IDs, $item_ID, $convert_to_root = true )
{
	global $DB;

	$child_comments_SQL = new SQL();
	$child_comments_SQL->SELECT( 'comment_ID' );
	$child_comments_SQL->FROM( 'T_comments' );
	$child_comments_SQL->WHERE( 'comment_in_reply_to_cmt_ID IN ( '.$DB->quote( $comment_IDs ).' )' );
	$child_comments_IDs = $DB->get_col( $child_comments_SQL->get() );

	if( empty( $child_comments_IDs ) )
	{ // No child comments, Exit here
		return;
	}

	// Move the child comments recursively
	move_child_comments_to_item( $child_comments_IDs, $item_ID, false );

	// Update item ID to new
	if( $convert_to_root )
	{ // Make these comments as root comments (remove their parent depending)
		$update_sql = ', comment_in_reply_to_cmt_ID = NULL';
	}
	$DB->query( 'UPDATE T_comments
		SET comment_item_ID = '.$DB->quote( $item_ID ).$update_sql.'
		WHERE comment_in_reply_to_cmt_ID IN ( '.$DB->quote( $comment_IDs ).' )' );
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
	if( !$current_User->check_perm( 'users', 'moderate' ) )
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
		$comments_Results->global_icon( sprintf( T_('Delete all comments posted by %s'), $edited_User->login ), 'recycle', '?ctrl=user&amp;user_tab=activity&amp;action=delete_all_comments&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb('user'), ' '.T_('Delete all'), 3, 4 );
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
		if( empty( $Comment->author_IP ) )
		{
			return '';
		}
		else
		{
			$antispam_icon = get_icon( 'lightning', 'imgtag', array( 'title' => T_( 'Go to edit this IP address in antispam control panel' ) ) );
			$antispam_link = ' '.implode( ', ', get_linked_ip_list( array( $Comment->author_IP ), NULL, $antispam_icon ) );

			$filter_IP_url = regenerate_url( 'filter', $param_prefix.'author_IP='.$Comment->get( 'author_IP' ) );
			$country = $Comment->get_ip_country( ' ' );

			return '<a href="'.$filter_IP_url.'">'.$Comment->get( 'author_IP' ).'</a>'.$antispam_link.$country;
		}
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
 * @param string Status class
 * @return string Styled template for status
 */
// DEPRECATED: instead use something like: $Item->format_status( array(	'template' => '<div class="evo_status__banner evo_status__$status$">$status_title$</div>' ) );
function get_styled_status( $status_value, $status_title, $status_class = '' )
{
	return '<div class="floatright">'
		.'<span class="note status_'.$status_value.'">'
		.'<span'.( empty( $status_class ) ? '' : ' class="'.$status_class.'"' ).'>'.format_to_output( $status_title ).'</span>'
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

			$r .=  action_icon( $title, 'recycle',
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