<?php
/**
 * This is the template that displays the item block
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_forums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item, $preview, $dummy_fields, $cat;

/**
 * @var array Save all statuses that used on this page in order to show them in the footer legend
 */
global $legend_statuses;

if( !is_array( $legend_statuses ) )
{ // Init this array only first time
	$legend_statuses = array();
}

// Default params:
$params = array_merge( array(
		'feature_block'      => false,
		'content_mode'       => 'auto',		// 'auto' will auto select depending on $disp-detail
		'item_class'         => 'evo_post',
		'item_type_class'    => 'evo_post__ptyp_',
		'item_status_class'  => 'evo_post__',
		'item_disp_class'    => NULL,
		'image_size'         => 'fit-400x320',
	), $params );

// In this skin, it makes no sense to navigate in any different mode than "same category"
// Use the category from param
$current_cat = param( 'cat', 'integer', 0 );
if( $current_cat == 0 )
{ // Use main category by default because the category wasn't set
	$current_cat = $Item->main_cat_ID;
}

// Breadcrumbs
$cat = $current_cat;
skin_widget( array(
		// CODE for the widget:
		'widget' => 'breadcrumb_path',
		// Optional display params
		'block_start'      => '<ol class="breadcrumb">',
		'block_end'        => '</ol><div class="clear"></div>',
		'separator'        => '',
		'item_mask'        => '<li><a href="$url$">$title$</a></li>',
		'item_active_mask' => '<li class="active">$title$</li>',
	) );
?>

<a name="top"></a>
<a name="p<?php echo $Item->ID; ?>"></a>
<div class="panel panel-default forums_list single_topic">
	<?php /* This empty row is used to fix columns width, when table has css property "table-layout:fixed" */ ?>
	<div class="panel-heading">
		<?php
		// Page title
		$Item->title( array(
				'before'    => '<h2>',
				'after'     => '</h2>',
				'link_type' => 'permalink'
			) );
				// Author info:
				echo '<div class="ft_author_info">'.T_('Started by');
				$Item->author( array( 'link_text' => 'login', 'after' => '' ) );
				echo ', '.mysql2date( 'D M j, Y H:i', $Item->datecreated );
				echo '</div>';
		?>
	</div>
	<div class="panel-body"><?php
		// Buttons to post/reply
		$post_buttons = $Skin->get_post_button( $current_cat, $Item );
		echo $post_buttons;

		if( !$Item->is_featured() )
		{
			// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
			item_prevnext_links( array(
					'block_start'     => '<ul class="pager post_nav">',
					'prev_start'      => '<li>',
					'prev_text'       => '<span aria-hidden="true">&larr;</span> $title$',
					'prev_end'        => '</li>',
					'separator'       => ' ',
					'next_start'      => '<li>',
					'next_text'       => '$title$ <span aria-hidden="true">&rarr;</span>',
					'next_end'        => '</li>',
					'block_end'       => '</ul>',
					'target_blog'     => $Blog->ID,	// this forces to stay in the same blog, should the post be cross posted in multiple blogs
					'post_navigation' => 'same_category', // force to stay in the same category in this skin
					'featured'        => false, // don't include the featured posts into navigation list
				) );
			// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
		}
		?>
	</div>
<table id="styled_content_block" class="table evo_content_block">
	<tr class="ft_post_info">
		<td><?php
			$Item->author( array(
				'link_text' => 'login',
			) );
		?></td>
		<td><?php
			if( $Skin->get_setting( 'display_post_date' ) )
			{ // We want to display the post date:
				$Item->issue_time( array(
						'before'      => '',
						'after'       => ' &nbsp; &nbsp; ',
						'time_format' => 'D M j, Y H:i',
					) );
			}
		?>
			<a href="<?php echo $Item->get_permanent_url(); ?>" class="permalink">#1</a>
		</td>
	</tr>
	<tr class="valign-top">
		<td class="ft_avatar"><?php
			$Item->author( array(
				'link_text'  => 'only_avatar',
				'thumb_size' => 'crop-top-80x80',
			) );
		?></td>
		<td>
			<?php
			$post_header_class = 'bPostDate';
			if( $Skin->enabled_status_banner( $Item->status ) )
			{
				$Item->status( array( 'format' => 'styled' ) );
				$legend_statuses[] = $Item->status;
			}
			?>
<?php
	// ---------------------- POST CONTENT INCLUDED HERE ----------------------
	skin_include( '_item_content.inc.php', $params );
	// Note: You can customize the default item content by copying the generic
	// /skins/_item_content.inc.php file into the current skin folder.
	// -------------------------- END OF POST CONTENT -------------------------
?>
		</td>
	</tr>
	<tr>
		<td><a href="<?php echo $Item->get_permanent_url(); ?>#skin_wrapper" class="postlink"><?php echo T_('Back to top'); ?></a></td>
		<td>
		<?php
			echo '<div class="floatleft">';

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
			if( $bbcode_plugin_is_enabled && $Item->can_comment( NULL ) )
			{	// Display button to quote this post
				echo '<a href="'.$Item->get_permanent_url().'?mode=quote&amp;qp='.$Item->ID.'#form_p'.$Item->ID.'" title="'.T_('Reply with quote').'" class="'.button_class( 'text' ).' floatleft quote_button">'.get_icon( 'comments', 'imgtag', array( 'title' => T_('Reply with quote') ) ).' '.T_('Quote').'</a>';
			}
			echo '</div>';

			// List all tags attached to this topic:
			$Item->tags( array(
					'before' =>    '<span class="topic_tags">'.T_('Tags').': ',
					'after' =>     '</span>',
					'separator' => ', ',
				) );

			echo '<div class="floatright">';
			$Item->edit_link( array(
					'before' => ' ',
					'after'  => '',
					'title'  => T_('Edit this topic'),
					'text'   => '#',
					'class'  => button_class( 'text' ),
				) );
			echo ' <span class="'.button_class( 'group' ).'">';
			// Set redirect after publish to the same category view of the items permanent url
			$redirect_after_publish = $Item->add_navigation_param( $Item->get_permanent_url(), 'same_category', $current_cat );
			$Item->next_status_link( array( 'before' => ' ', 'class' => button_class( 'text' ), 'post_navigation' => 'same_category', 'nav_target' => $current_cat ), true );
			$Item->next_status_link( array( 'class' => button_class( 'text' ), 'before_text' => '', 'post_navigation' => 'same_category', 'nav_target' => $current_cat ), false );
			$Item->delete_link( '', '', '#', T_('Delete this topic'), button_class( 'text' ), false, '#', TS_('You are about to delete this post!\\nThis cannot be undone!'), get_caturl( $current_cat ) );
			echo '</span>';
			echo '</div>';
		?>
		</td>
	</tr>
<?php
if( !$Item->can_see_comments( true ) || $preview )
{	// If comments are disabled for this post we should close the <table> tag that was opened above for post content
	// Otherwise this tag will be closed below by 'comment_list_end'
	echo '</table></div>';
}
?>

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array_merge( array(
				'preview_block_start'  => '<div id="comment_preview" class="panel panel-warning forums_list single_topic"><table id="styled_content_block" class="table evo_content_block">',
				'preview_block_end'    => '</table></div>',
				'notification_text'    => T_( 'This is your topic. You are receiving notifications when anyone posts a reply on your topics.' ),
				'notification_text2'   => T_( 'You will be notified by email when someone posts a reply here.' ),
				'notification_text3'   => T_( 'Notify me by email when someone posts a reply here.' ),
				'before_section_title' => '<h3>',
				'after_section_title'  => '</h3>',
				'comment_list_end'     => '</table></div>',
				'disp_rating_summary'  => false,
				'disp_section_title'   => false,
				'form_comment_text'    => T_('Message body'),
				'form_submit_text'     => T_('Submit'),
				'form_title_text'      => T_('Post a reply'),
				'comments_disabled_text_member'     => T_( 'You must be a member of this blog to post a reply.' ),
				'comments_disabled_text_registered' => T_( 'You must be logged in to post a reply.' ),
				'comments_disabled_text_validated'  => T_( 'You must activate your account before you can post a reply.' ),
				'feed_title'                        => get_icon( 'feed' ).' '.T_('RSS feed for replies to this topic'),
				'before_comment_error' => '<p class="center" style="font-size:150%"><b>',
				'comment_closed_text'  => T_('This topic is closed.'),
				'after_comment_error'  => '</b></p>',
				// Params for ajax comment form on quote action
				'comment_mode'         => param( 'mode', 'string', '' ),
				'comment_qc'           => param( 'qc', 'integer', 0 ),
				'comment_qp'           => param( 'qp', 'integer', 0 ),
				$dummy_fields[ 'content' ] => param( $dummy_fields[ 'content' ], 'html' ),
				// Comments list navigation:
				'disp_nav_top'      => false,
				'nav_bottom_inside' => true,
				'nav_block_start'   => '<tr class="panel bottom"><td colspan="2">'.$post_buttons.'<div class="navigation">',
				'nav_block_end'     => '</div></td></tr>',
				'nav_prev_text'     => T_('Previous'),
				'nav_next_text'     => T_('Next'),
				'nav_prev_class'    => 'prev',
				'nav_next_class'    => 'next',
			), $params ) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.

echo_comment_moderate_js();

		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>

	<?php
		locale_restore_previous();	// Restore previous locale (Blog locale)
	?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( '.quote_button' ).click( function()
	{ // Submit a form to save the already entered content
		console.log( jQuery( this ).attr( 'href' ) );
		var form = jQuery( 'form[id^=evo_omment_form_id_]' );
		if( form.length == 0 )
		{ // No form found, Use an url of this link
			return true;
		}
		// Set an action as url of this link and submit a form
		form.attr( 'action', jQuery( this ).attr( 'href' ) );
		form.submit();
		return false;
	} );
} );
</script>