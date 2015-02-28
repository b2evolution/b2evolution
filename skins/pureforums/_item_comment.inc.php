<?php
/**
 * This is the template that displays a single comment
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage pureforums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $comment_template_counter, $disp;

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
		'comment_block_start'  => '',
		'comment_start'        => '<div class="bComment">',
		'comment_end'          => '</div>',
		'comment_block_end'    => '',
		'link_to'              => 'userurl>userpage', // 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
		'before_image'         => '<div class="image_block">',
		'before_image_legend'  => '<div class="image_legend">',
		'after_image_legend'   => '</div>',
		'after_image'          => '</div>',
		'image_size'           => 'fit-400x320',
		'Comment'              => NULL, // This object MUST be passed as a param!
		'display_vote_helpful' => true,
		'comment_number'       => 1,
	), $params );

if( ! isset( $comment_template_counter ) )
{
	$comment_template_counter = isset( $params['comment_number'] ) ? $params['comment_number'] : 1;
	if( $disp == 'single' || $disp == 'post' )
	{ // Increase a number, because Item has 1st number
		$comment_template_counter++;
	}
}

/**
 * @var Comment
 */
$Comment = & $params['Comment'];

$comment_class = 'vs_'.$Comment->status;
$comment_class = ' class="'.$comment_class.'"';

?>
<!-- ========== START of a COMMENT/TB/PB ========== -->
<?php echo $params['comment_block_start'];
	if( $disp == 'comments' )
	{ // We are displaying a comment in the Latest comments page, we want to show what post/topic it relates to:
	?>
	<tr class="ft_comment_response">
		<td colspan="2"><h3>
				<?php echo T_('In response to:');
					$Comment->permanent_link( array(
							'text' => $Comment->Item->dget( 'title' ),
						) );
				?>
			</h3></td>
	</tr>
	<?php
	}
	?>
	<tr<?php echo $comment_class; ?> id="comment_<?php echo $Comment->ID; ?>">
		<td class="ft_post_info"><?php
			$Comment->anchor();
			$Comment->author2( array(
				'link_text' => 'login',
			) );
		?></td>
		<td class="ft_post_info"><?php
			if( $Skin->get_setting( 'display_post_date' ) )
			{ // We want to display the post date:
				$Comment->date( 'D M j, Y H:i' );
			}
			$Comment->rating( array(
					'before' => '',
					'after'  => '',
				) );
		?>
			<a href="<?php echo $Comment->get_permanent_url(); ?>" class="permalink">#<?php echo $comment_template_counter; ?></a>
		</td>
	</tr>
	<tr valign="top"<?php echo $comment_class; ?> id="comment_<?php echo $Comment->ID; ?>">
		<td class="ft_avatar"><?php
		$Comment->author2( array(
				'before'       => '',
				'after'        => '',
				'before_user'  => '',
				'after_user'   => '',
				'link_to'      => $params['link_to'],		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
				'link_text'    => 'only_avatar',
				'thumb_size'   => 'crop-top-80x80',
			) );
	?></td>
		<td valign="top">
		<?php
		$post_header_class = 'bPostDate';
		if( $Skin->enabled_status_banner( $Comment->status ) && $Comment->ID > 0 )
		{ // Don't display status for previewed comments
			$Comment->statuses();
			$post_header_class .= ' '.$Comment->status;
			$legend_statuses[] = $Comment->status;
		}
		echo $params['comment_start'];
		$Comment->content( 'htmlbody', false, true, $params );
		echo $params['comment_end'];
		?>
		</td>
	</tr>
	<tr<?php echo $comment_class; ?> id="comment_<?php echo $Comment->ID; ?>">
		<td><a href="<?php
		if( $disp == 'comments' )
		{	// We are displaying a comment in the Latest comments page:
			echo $Blog->get('lastcommentsurl');
		}
		else
		{	// We are displaying a comment under a post/topic:
			echo $Item->get_permanent_url();
		}
		?>#skin_wrapper" class="postlink"><?php echo T_('Back to top'); ?></a></td>
	<td>
	<?php
		if( $Comment->ID > 0 )
		{ // Display action buttons only for existing comments(Disable for previewed comment)
			$commented_Item = & $Comment->get_Item();

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
			if( $bbcode_plugin_is_enabled && $commented_Item && $commented_Item->can_comment( NULL ) )
			{ // Display button to quote this comment
				echo '<a href="'.$commented_Item->get_permanent_url().'?mode=quote&amp;qc='.$Comment->ID.'#form_p'.$commented_Item->ID.'" title="'.T_('Reply with quote').'" class="roundbutton_text floatleft quote_button">'.get_icon( 'comments', 'imgtag', array( 'title' => T_('Reply with quote') ) ).T_('Quote').'</a>';
			}

			if( $params['display_vote_helpful'] )
			{ // Display a voting tool
				$Comment->vote_helpful( '', '', '&amp;', true, true, array(
						'helpful_text'    => T_('Is this reply helpful?'),
						'title_yes'       => T_('Mark this reply as helpful!'),
						'title_yes_voted' => T_('You think this reply is helpful'),
						'title_no'        => T_('Mark this reply as not helpful!'),
						'title_no_voted'  => T_('You think this reply is not helpful'),
						'class'           => 'vote_helpful'
					) );
			}

			// Display Spam Voting system
			$Comment->vote_spam( '', '', '&amp;', true, true, array(
					'title_spam'          => T_('Mark this reply as spam!'),
					'title_spam_voted'    => T_('You think this reply is spam'),
					'title_notsure'       => T_('Mark this reply as not sure!'),
					'title_notsure_voted' => T_('You are not sure in this reply'),
					'title_ok'            => T_('Mark this reply as OK!'),
					'title_ok_voted'      => T_('You think this reply is OK'),
				) );
			echo '</div>';

			echo '<div class="floatright">';
			$comment_redirect_url = rawurlencode( $Comment->get_permanent_url() );
			$Comment->edit_link( ' ', '', '#', T_('Edit this reply'), 'roundbutton_text', '&amp;', true, $comment_redirect_url ); /* Link for editing */
			echo ' <span class="roundbutton_group">';
			$delete_button_is_displayed = is_logged_in() && $current_User->check_perm( 'comment!CURSTATUS', 'delete', false, $Comment );
			$Comment->moderation_links( array(
					'ajax_button' => true,
					'class'       => 'roundbutton_text',
					'redirect_to' => $comment_redirect_url,
					'detect_last' => !$delete_button_is_displayed,
				) );
			$Comment->delete_link( '', '', '#', T_('Delete this reply'), 'roundbutton_text', false, '&amp;', true, false, '#', rawurlencode( $commented_Item->get_permanent_url() ) ); /* Link to backoffice for deleting */
			echo '</span>';
			echo '</div>';
		}
	?>
		</td>
	</tr>
<?php echo $params['comment_block_end']; ?>
<!-- ========== END of a COMMENT/TB/PB ========== -->
<?php

$comment_template_counter++;
?>