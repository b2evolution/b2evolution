<?php
/**
 * This file implements the post browsing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Collection, $Blog;
/**
 * @var ItemList2
 */
global $ItemList;
/**
 * Note: definition only (does not need to be a global)
 * @var Item
 */
global $Item;

global $blog, $posts, $poststart, $postend, $ReqURI;
global $edit_item_url, $delete_item_url, $p, $dummy_fields;
global $comment_allowed_tags, $comment_type;
global $Plugins, $DB, $UserSettings, $Session, $Messages;

$highlight = param( 'highlight', 'integer', NULL );

// Run the query:
$ItemList->query();

// Old style globals for category.funcs:
global $postIDlist;
$postIDlist = $ItemList->get_page_ID_list();
global $postIDarray;
$postIDarray = $ItemList->get_page_ID_array();


$block_item_Widget = new Widget( 'block_item' );

// This block is used to keep correct css style for the post status banners
echo '<div class="evo_content_block evo_content_summary">';

	$block_item_Widget->title = T_('Posts Browser').get_manual_link( 'browse-edit-tab' );
	if( $ItemList->is_filtered() )
	{ // List is filtered, offer option to reset filters:
		$block_item_Widget->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset', T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
	}

	// Generate global icons depending on seleted tab with item type
	item_type_global_icons( $block_item_Widget );

	$block_item_Widget->disp_template_replaced( 'block_start' );

	// --------------------------------- START OF CURRENT FILTERS --------------------------------
	skin_widget( array(
			// CODE for the widget:
			'widget' => 'coll_current_filters',
			// Optional display params
			'ItemList'             => $ItemList,
			'block_start'          => '',
			'block_end'            => '',
			'block_title_start'    => '<b>',
			'block_title_end'      => ':</b> ',
			'show_filters'         => array( 'time' => 1, 'visibility' => 1, 'itemtype' => 1 ),
			'display_button_reset' => false,
			'display_empty_filter' => true,
		) );
	// ---------------------------------- END OF CURRENT FILTERS ---------------------------------

	$block_item_Widget->disp_template_replaced( 'block_end' );

	global $AdminUI;
	$admin_template = $AdminUI->get_template( 'Results' );

	// Initialize things in order to be ready for displaying.
	$display_params = array(
			'header_start' => $admin_template['header_start'],
			'footer_start' => $admin_template['footer_start'],
		);

$ItemList->display_init( $display_params );

// Display navigation:
$ItemList->display_nav( 'header' );

/*
 * Display posts:
 */
while( $Item = & $ItemList->get_item() )
{
	?>
	<div id="<?php $Item->anchor_id() ?>" class="panel panel-default evo_post evo_post__status_<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">
		<?php
		// We don't switch locales in the backoffice, since we use the user pref anyway
		// Load item's creator user:
		$Item->get_creator_User();
		?>

		<div class="panel-body">
			<?php
			$Item->format_status( array(
					'template' => '<div class="pull-right"><span class="note status_$status$" data-toggle="tooltip" data-placement="top" title="$tooltip_title$"><span>$status_title$</span></span></div>',
				) );

			$post_image = get_social_media_image( $Item, array( 'return_as_link' => true ) );

			if( ! empty( $post_image ) )
			{
				if( get_class( $post_image ) == 'Link' )
				{
					echo $post_image->get_tag( array(
							'before_image'   => '<div class="evo_image_block pull-left">',
							'after_image'    => '</div>',
							'image_size'     => 'fit-400x320',
							'image_link_to'  => 'original',
							'image_link_rel' => 'lightbox[p'.$post_image->ID.']',
							'image_desc'     => '',
						) );
				}
				else
				{
					echo $post_image->get_tag( '<div class="evo_image_block pull-left">', '', '', '</div>', 'fit-400x320', 'original', '', 'lightbox[p'.$post_image->ID.']' );
				}
			}
			?>
			<div class="bText">
					<span class="bTitle">
					<?php
					$title_length = mb_strlen( html_entity_decode( $Item->title ) );
					$label = 'default';
					if( $title_length <= 55 )
					{
						$label = 'success';
					}
					elseif( $title_length <= 60 )
					{
						$label = 'warning';
					}
					else
					{
						$label = 'danger';
					}
					echo $Item->title( array( 'target_blog' => '' ) ).'</span> <span class="label label-'.$label.'">'.$title_length.'</span>';
					?>
					<p>
					<?php
					$excerpt = format_to_output( $Item->get_excerpt2(), 'htmlbody' );
					$excerpt_length = mb_strlen( html_entity_decode( $excerpt ) );
					$label = 'default';
					if( $excerpt_length <= 55 )
					{
						$label = 'success';
					}
					elseif( $excerpt_length <= 60 )
					{
						$label = 'warning';
					}
					else
					{
						$label = 'danger';
					}
					echo $excerpt.' <span class="label label-'.$label.'">'.$excerpt_length.'</span>';
					?>
					</p>

					<?php
					if( $post_image )
					{
						echo '<p class="note">';
						if( get_class( $post_image ) == 'Link' )
						{
							$image = & $post_image->get_File();
						}
						else
						{
							$image = $post_image;
						}
						$image_size = $image->get_image_size( 'widthheight_assoc' );
						$image_ratio = $image_size['width'] / $image_size['height'];
						$image_size = $image->get_image_size( 'widthxheight' );
						$label = 'default';
						if( ( $image_ratio >= 1.90 ) && ( $image_ratio <= 1.92 ) )
						{
							$label = 'success';
						}
						elseif( ( $image_ratio >= 1.90 ) && ( $image_ratio <= 1.92 ) )
						{
							$label = 'warning';
						}
						else
						{
							$label = 'danger';
						}
						echo sprintf( T_('Image dimensions: %s - Ratio: %s'), $image_size, '<span class="label label-'.$label.'">'.number_format( $image_ratio, 2 ).'</span>' );
						echo '</p>';
					}
					?>

				<div>
					<?php

					// Edit : Propose change | Duplicate... | Merge with...
					$edit_buttons = array();
					if( $item_edit_url = $Item->get_edit_url() )
					{	// Edit
						$edit_buttons[] = array(
							'url'  => $item_edit_url,
							'text' => get_icon( 'edit_button' ).' '.T_('Edit'),
						);
					}
					if( $item_propose_change_url = $Item->get_propose_change_url() )
					{	// Propose change
						$edit_buttons[] = array(
							'url'  => $item_propose_change_url,
							'text' => get_icon( 'edit_button' ).' '.T_('Propose change'),
						);
					}
					if( $item_copy_url = $Item->get_copy_url() )
					{	// Duplicate...
						$edit_buttons[] = array(
							'url'  => $item_copy_url,
							'text' => get_icon( 'copy' ).' '.T_('Duplicate...'),
						);
					}
					if( $item_merge_click_js = $Item->get_merge_click_js( $params ) )
					{	// Merge with...
						$edit_buttons[] = array(
							'onclick' => $item_merge_click_js,
							'text'    => get_icon( 'merge' ).' '.T_('Merge with...'),
						);
						echo_item_merge_js();
					}
					$edit_buttons_num = count( $edit_buttons );
					if( $edit_buttons_num > 1 )
					{	// Display buttons in dropdown style:
						echo '<div class="'.button_class( 'group' ).'">';
						echo '<a href="'.$edit_buttons[0]['url'].'" class="'.button_class( 'small_text_primary' ).'">'.$edit_buttons[0]['text'].'</a>';
						echo '<button type="button" class="'.button_class( 'small_text' ).' dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span></button>';
						echo '<ul class="dropdown-menu">';
						for( $b = 1; $b < $edit_buttons_num; $b++ )
						{
							echo '<li><a href="'.( empty( $edit_buttons[ $b ]['url'] ) ? '#' : $edit_buttons[ $b ]['url'] ).'"'
									.( empty( $edit_buttons[ $b ]['onclick'] ) ? '' : ' onclick="'.$edit_buttons[ $b ]['onclick'].'"' ).'>'
									.$edit_buttons[ $b ]['text']
								.'</a></li>';
						}
						echo '</ul></div>';
					}
					elseif( $edit_buttons_num == 1 )
					{	// Display single button:
						echo '<span class="'.button_class( 'group' ).'">';
						echo '<a href="'.$edit_buttons[0]['url'].'" class="'.button_class( 'small_text_primary' ).'">'.$edit_buttons[0]['text'].'</a>';
						echo '</span>';
					}

					echo '<span class="'.button_class( 'group' ).'">';

					echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'" class="'.button_class( 'small_text' ).'">'.get_icon( 'magnifier' ).' '.T_('Details').'</a>';

					echo $Item->get_history_link( array(
							'class'     => button_class( $Item->has_proposed_change() ? 'small_text_warning' : 'small_text' ),
							'link_text' => '$icon$ '.T_('Changes'),
						) );

					if( $Blog->get_setting( 'allow_comments' ) != 'never' )
					{
						echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'#comments" class="'.button_class( 'small_text' ).'">';
						$comments_number = generic_ctp_number( $Item->ID, 'comments', 'total', true );
						echo get_icon( $comments_number > 0 ? 'comments' : 'nocomment' ).' ';
						// TRANS: Link to comments for current post
						comments_number( T_('no comment'), T_('1 comment'), T_('%d comments'), $Item->ID );
						load_funcs('comments/_trackback.funcs.php'); // TODO: use newer call below
						trackback_number('', ' &middot; '.T_('1 Trackback'), ' &middot; '.T_('%d Trackbacks'), $Item->ID);
						echo '</a>';
					}
					echo '</span>';

					echo '<span class="'.button_class( 'group' ).'"> ';
					// Display the moderate buttons if current user has the rights:
					$status_link_params = array(
							'class'       => button_class( 'small_text' ),
							'redirect_to' => regenerate_url( '', '&highlight='.$Item->ID.'#item_'.$Item->ID, '', '&' ),
						);
					$Item->next_status_link( $status_link_params, true );
					$Item->next_status_link( $status_link_params, false );

					$next_status_in_row = $Item->get_next_status( false );
					if( $next_status_in_row && $next_status_in_row[0] != 'deprecated' )
					{ // Display deprecate button if current user has the rights:
						$Item->deprecate_link( '', '', get_icon( 'move_down_grey', 'imgtag', array( 'title' => '' ) ), '#', button_class( 'small_text') );
					}

					// Display delete button if current user has the rights:
					$Item->delete_link( '', ' ', '#', '#', button_class( 'small_text' ), false );
					echo '</span>';

					?>

					<div class="clearfix"></div>
				</div>
			</div>
		</div>
	</div>
<?php
}

// Display navigation:
$ItemList->display_nav( 'footer' );

echo '</div>';// END OF <div class="evo_content_block">

?>