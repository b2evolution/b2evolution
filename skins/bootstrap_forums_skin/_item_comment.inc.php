<?php
/**
 * This is the template that displays a single comment
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
global $cat;

// Default params:
$params = array_merge( array(
		'comment_start'         => '<article class="evo_comment panel panel-default">',
		'comment_end'           => '</article>',

		'comment_post_display'	=> true,	// Do we want ot display the title of the post we're referring to?
		'comment_post_before'   => '<br /><h4 class="evo_comment_post_title ellipsis">',
		'comment_post_after'    => '</h4>',

		'comment_title_before'  => '<div class="panel-heading posts_panel_title_wrapper"><div class="cell1 ellipsis"><h4 class="evo_comment_title panel-title">',
		'comment_status_before' => '</h4></div>',
		'comment_title_after'   => '</div>',

		'comment_body_before'   => '<div class="panel-body">',
		'comment_body_after'    => '</div>',

		'comment_avatar_before' => '<span class="evo_comment_avatar col-md-1 col-sm-2">',
		'comment_avatar_after'  => '</span>',
		'comment_rating_before' => '<div class="evo_comment_rating">',
		'comment_rating_after'  => '</div>',
		'comment_text_before'   => '<div class="evo_comment_text col-md-11 col-sm-10">',
		'comment_text_after'    => '</div>',
		'link_to'               => 'userurl>userpage', // 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
		'author_link_text'      => 'auto', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
		'before_image'          => '<figure class="evo_image_block">',
		'before_image_legend'   => '<figcaption class="evo_image_legend">',
		'after_image_legend'    => '</figcaption>',
		'after_image'           => '</figure>',
		'image_size'            => 'fit-1280x720',
		'image_class'           => 'img-responsive',
		'Comment'               => NULL, // This object MUST be passed as a param!
		'display_vote_helpful'  => true,
	), $params );

// In this skin, it makes no sense to navigate in any different mode than "same category"
// Use the category from param
$current_cat = param( 'cat', 'integer', 0 );
if( $current_cat == 0 )
{ // Use main category by default because the category wasn't set
	$current_cat = $Item->main_cat_ID;
}

// Increase a number, because Item has 1st number:
$comment_order_shift = ( $disp == 'single' || $disp == 'page' ) ? 1 : 0;

/**
 * @var Comment
 */
$Comment = & $params['Comment'];


/**
 * @var Item
 */
$commented_Item = & $Comment->get_Item();

// Load comment's Item object:
$Comment->get_Item();


$Comment->anchor();

echo update_html_tag_attribs( $params['comment_start'], array(
		'class' => 'vs_'.$Comment->status.( $Comment->is_meta() ? ' evo_comment__meta' : '' ), // Add style class for proper comment status
		'id'    => 'comment_'.$Comment->ID // Add id to know what comment is used on AJAX status changing
	), array( 'id' => 'skip' ) );

// Title
echo $params['comment_title_before'];
switch( $Comment->get( 'type' ) )
{
	// ON *DISP = COMMENTS* SHOW THE FOLLOWING TITLE FOR EACH COMMENT
	case $disp == 'comments': // Display a comment:
	?><a href="<?php echo $Comment->get_permanent_url(); ?>" class="permalink">#<?php echo $Comment->get_inlist_order() + $comment_order_shift; ?></a> <?php
		if( empty($Comment->ID) )
		{	// PREVIEW comment
			echo '<span class="evo_comment_type_preview">'.T_('PREVIEW Comment from:').'</span> ';
		}
		else
		{	// Normal comment
			$Comment->permanent_link( array(
					'before'    => '',
					'after'     => '',
					'text'      => '',
					'class'     => 'evo_comment_type',
					'nofollow'  => true,
				) );
		}

		$Comment->author2( array(
				'before'       => ' ',
				'after'        => '#',
				'before_user'  => '',
				'after_user'   => '#',
				'format'       => 'htmlbody',
				'link_to'      => $params['link_to'],		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
				'link_text'    => $params['author_link_text'],
			) );

		echo ' <span class="text-muted">';
		$Comment->date( locale_extdatefmt().' '.locale_shorttimefmt() );
		echo '</span>';

		// Post title
		if( $params['comment_post_display'] )
		{
			echo $params['comment_post_before'];
			echo ' '.T_('in response to').': ';
			$Comment->Item->title( array(
					'link_type' => 'permalink',
				) );
			echo $params['comment_post_after'];
		}

		if( ! $Comment->get_author_User() )
		{ // Display action icon to message only if this comment is from a visitor
			$Comment->msgform_link( $Blog->get( 'msgformurl' ) );
		}
		break;

	// ON *DISP = SINGLE* SHOW THE FOLLOWING TITLE FOR EACH COMMENT
	case 'comment': // Display a comment:
	case 'meta': // Display a meta comment:
		if( $Comment->is_meta() )
		{	// Meta comment:
			?><span class="badge badge-info"><?php echo $Comment->get_inlist_order(); ?></span> <?php
		}
		else
		{	// Normal comment:
			?><a href="<?php echo $Comment->get_permanent_url(); ?>" class="permalink">#<?php echo $Comment->get_inlist_order() + $comment_order_shift; ?></a> <?php
		}
		if( empty($Comment->ID) )
		{	// PREVIEW comment
			echo '<span class="evo_comment_type_preview">'.T_('PREVIEW Comment from:').'</span> ';
		}
		else
		{	// Normal comment
			$Comment->permanent_link( array(
					'before'    => '',
					'after'     => '',
					'text'      => '',
					'class'     => 'evo_comment_type',
					'nofollow'  => true,
				) );
		}

		$Comment->author2( array(
				'before'       => ' ',
				'after'        => '#',
				'before_user'  => '',
				'after_user'   => '#',
				'format'       => 'htmlbody',
				'link_to'      => $params['link_to'],		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
				'link_text'    => $params['author_link_text'],
			) );

		echo ' <span class="text-muted">';
		$Comment->date( locale_extdatefmt().' '.locale_shorttimefmt() );
		echo '</span>';

		if( ! $Comment->get_author_User() )
		{ // Display action icon to message only if this comment is from a visitor
			$Comment->msgform_link( $Blog->get( 'msgformurl' ) );
		}
		break;

	case 'trackback': // Display a trackback:
		$Comment->permanent_link( array(
				'before'    => '',
				'after'     => ' '.T_('from:').' ',
				'text' 		=> T_('Trackback'),
				'class'		=> 'evo_comment_type',
				'nofollow'	=> true,
			) );
		$Comment->author( '', '#', '', '#', 'htmlbody', true, $params['author_link_text'] );
		break;

	case 'pingback': // Display a pingback:
		$Comment->permanent_link( array(
				'before'    => '',
				'after'     => ' '.T_('from:').' ',
				'text' 		=> T_('Pingback'),
				'class'		=> 'evo_comment_type',
				'nofollow'	=> true,
			) );
		$Comment->author( '', '#', '', '#', 'htmlbody', true, $params['author_link_text'] );
		break;
}

echo $params['comment_status_before'];

// Status banners
if( $Skin->enabled_status_banner( $Comment->status ) && $Comment->ID > 0 )
{ // Don't display status for previewed comments
		echo '<div class="cell2">';
		$Comment->format_statuses( array(
				'template' => '<div class="evo_status evo_status__$status$ badge pull-right" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
			) );
		echo '</div>';
		$legend_statuses[] = $Comment->status;
}

echo $params['comment_title_after'];

echo $params['comment_body_before'];

// Avatar:
echo $params['comment_avatar_before'];
$Comment->author2( array(
					'link_text'  => 'only_avatar',
					'thumb_size' => 'crop-top-80x80',
					'after_user' => ''
				) );
echo $params['comment_avatar_after'];

// Rating:
$Comment->rating( array(
		'before' => $params['comment_rating_before'],
		'after'  => $params['comment_rating_after'],
	) );

// Text:
echo $params['comment_text_before'];

$Comment->content( 'htmlbody', false, true, $params );

echo $params['comment_text_after'];

echo $params['comment_body_after'];

/* ======================== START OF COMMENT FOOTER ======================== */
?>
<div class="panel-footer small clearfix">
	<a href="<?php
		if( $disp == 'comments' )
		{	// We are displaying a comment in the Latest comments page:
			echo $Blog->get('lastcommentsurl');
		}
		else
		{	// We are displaying a comment under a post/topic:
			echo $Item->get_permanent_url();
		}
		?>#skin_wrapper" class="to_top"><?php echo T_('Back to top'); ?></a>
	<?php
	// Check if BBcode plugin is enabled for current blog
	$bbcode_plugin_is_enabled = false;
	if( class_exists( 'bbcode_plugin' ) )
	{ // Plugin exists
		global $Plugins;
		$bbcode_Plugin = & $Plugins->get_by_classname( 'bbcode_plugin' );
		if( $bbcode_Plugin->status == 'enabled' && $bbcode_Plugin->get_coll_setting( 'coll_apply_comment_rendering', $Blog ) != 'never' )
		{ // Plugin is enabled and activated for comments
			$bbcode_plugin_is_enabled = true;
		}
	}
	if( $bbcode_plugin_is_enabled && $commented_Item && $commented_Item->can_comment( NULL ) )
	{ // Display button to quote this comment
		echo '<a href="'.$commented_Item->get_permanent_url().'?mode=quote&amp;qc='.$Comment->ID.'#form_p'.$commented_Item->ID.'" title="'.T_('Reply with quote').'" class="'.button_class( 'text' ).' pull-left quote_button">'.get_icon( 'comments', 'imgtag', array( 'title' => T_('Reply with quote') ) ).' '.T_('Quote').'</a>';
	}

	$Comment->reply_link( ' ', ' ', '#', '#', 'pull-left' ); /* Link for replying to the Comment */

	if( $params['display_vote_helpful'] )
	{	// Display a voting panel for comment:
		$Skin->display_comment_voting_panel( $Comment );
	}

	// Display Spam Voting system
	$Comment->vote_spam( '', '', '&amp;', true, true );

	echo '<span class="pull-left">';
		$comment_redirect_url = $Comment->get_permanent_url();
		$Comment->edit_link( ' ', '', '#', T_('Edit this reply'), button_class( 'text' ).' comment_edit_btn', '&amp;', true, $comment_redirect_url ); /* Link for editing */
	echo '</span>';
	echo '<div class="action_btn_group">';
		$Comment->edit_link( ' ', '', '#', T_('Edit this reply'), button_class( 'text' ).' comment_edit_btn', '&amp;', true, $comment_redirect_url ); /* Link for editing */
		echo '<span class="'.button_class( 'group' ).'">';
		$delete_button_is_displayed = is_logged_in() && $current_User->check_perm( 'comment!CURSTATUS', 'delete', false, $Comment );
		$Comment->moderation_links( array(
				'ajax_button' => true,
				'class'       => button_class( 'text' ),
				'redirect_to' => $comment_redirect_url,
				'detect_last' => !$delete_button_is_displayed,
			) );
		$Comment->delete_link( '', '', '#', T_('Delete this reply'), button_class( 'text' ), false, '&amp;', true, false, '#', $commented_Item->get_permanent_url() ); /* Link to backoffice for deleting */

		echo '</span>';
	echo '</div>';
	?>
</div>

<?php echo $params['comment_end'];
?>