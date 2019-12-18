<?php
/**
 * This is the template that displays a recipe in a collection
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?p=123
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2019 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
		// Classes for the <article> tag:
		'item_class'               => 'evo_post evo_content_block',
		'item_type_class'          => 'evo_post__ptyp_',
		'item_status_class'        => 'evo_post__',
		'item_style'               => '',
		// Controlling the title:
		'item_title_before'        => '<h2>',
		'item_title_after'         => '</h2>',
		// Controlling the content:
		'author_link_text'         => 'auto',
		'before_content_teaser'    => '<p>',
		'after_content_teaser'     => '</p>',
		'before_content_extension' => '',
		'after_content_extension'  => '',
		// Controlling the images:
		'image_positions'          => 'cover,teaser,teaserperm,teaserlink',
		'before_images'            => '<div class="evo_post_images">',
		'before_image'             => '<figure class="evo_image_block">',
		'before_image_legend'      => '<figcaption class="evo_image_legend">',
		'after_image_legend'       => '</figcaption>',
		'after_image'              => '</figure>',
		'after_images'             => '</div>',
		'image_class'              => 'img-responsive',
		'image_size'               => 'crop-320x320',
		'image_limit'              =>  1000,
		'image_link_to'            => 'original', // Can be 'original', 'single' or empty
		'excerpt_image_class'      => '',
		'excerpt_image_size'       => 'fit-80x80',
		'excerpt_image_limit'      => 0,
		'excerpt_image_link_to'    => 'single',
		'before_gallery'           => '<div class="evo_post_gallery">',
		'after_gallery'            => '</div>',
		'gallery_table_start'      => '',
		'gallery_table_end'        => '',
		'gallery_row_start'        => '',
		'gallery_row_end'          => '',
		'gallery_cell_start'       => '<div class="evo_post_gallery__image">',
		'gallery_cell_end'         => '</div>',
		'gallery_image_size'       => 'crop-80x80',
		'gallery_image_limit'      => 1000,
		'gallery_colls'            => 5,
		'gallery_order'            => '', // Can be 'ASC', 'DESC', 'RAND' or empty

		'page_links_start'         => '<p class="evo_post_pagination">'.T_('Pages').': ',
		'page_links_end'           => '</p>',
		'page_links_separator'     => '&middot; ',
		'page_links_single'        => '',
		'page_links_current_page'  => '#',
		'page_links_pagelink'      => '%d',
		'page_links_url'           => '',

		'footer_text_mode'         => '#', // 'single', 'xml' or empty. Will detect 'single' from $disp automatically.
		'footer_text_start'        => '<div class="evo_post_footer">',
		'footer_text_end'          => '</div>',
	), $params );

// Display message if no post:
display_if_empty( array(
		'before'      => '<p class="msg_nothing">',
		'after'       => '</p>',
		'msg_empty'   => T_('Sorry, there is nothing to display...'),
	) );

if( mainlist_get_item() )
{	// If Item is found by requested slug:

	global $cat;

	// In this skin, it makes no sense to navigate in any different mode than "same category"
	// Use the category from param
	$current_cat = param( 'cat', 'integer', 0 );
	if( $current_cat == 0 )
	{	// Use main category by default because the category wasn't set:
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
			'coll_logo_size'   => 'fit-128x16',
		) );
?>

<a name="top"></a>
<a name="p<?php echo $Item->ID; ?>"></a>

<?php
	$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
?>

<div class="forums_list single_topic evo_content_block">

	<div class="single_page_title">
		<?php
			// ------------------------- "Item Single - Header" CONTAINER EMBEDDED HERE --------------------------
			// Display container contents:
			widget_container( 'item_single_header', array(
				'widget_context'             => 'item',	// Signal that we are displaying within an Item
				// The following (optional) params will be used as defaults for widgets included in this container:
				'container_display_if_empty' => false, // If no widget, don't display container at all
				// This will enclose each widget in a block:
				'block_start'                => '<div class="evo_widget $wi_class$">',
				'block_end'                  => '</div>',
				// This will enclose the title of each widget:
				'block_title_start'          => '<h3>',
				'block_title_end'            => '</h3>',
				'author_link_text'           => $params['author_link_text'],
				// Controlling the title:
				'widget_item_title_display' => false,
				// Item Next Previous widget
				'widget_item_next_previous_display' => ! $Item->is_featured(), // Do not show Item Next Previous widget if featured item
				'widget_item_next_previous_params' => array(
						'target_blog'     => $Blog->ID,	// this forces to stay in the same blog, should the post be cross posted in multiple blogs
						'post_navigation' => 'same_category', // force to stay in the same category in this skin
						'featured'        => false, // don't include the featured posts into navigation list
					),
			) );
			// ----------------------------- END OF "Item Single - Header" CONTAINER -----------------------------
		?>
	</div>

	<div class="row">
		<div class="<?php echo $Skin->get_column_class( 'single' ); ?>">

	<section class="table evo_content_block<?php echo ' evo_voting_layout__'.$Skin->get_setting( 'voting_place' ); ?>">
	<div class="panel panel-default">
		<div class="panel-heading posts_panel_title_wrapper">
			<div class="cell1 ellipsis">
				<h4 class="evo_comment_title panel-title"><a href="<?php echo $Item->get_permanent_url(); ?>" class="permalink">#1</a>
					<?php
						// Display author avatar:
						$Item->author( array(
							'link_text' => 'auto',
						) );
					?>
					<?php
						// Display the post date:
						$Item->issue_time( array(
								'before'      => '<span class="text-muted">',
								'after'       => '</span> &nbsp; &nbsp; ',
								'time_format' => locale_extdatefmt().' '.locale_shorttimefmt(),
							) );
					?>
				</h4>
			</div>
					<?php
						if( $Skin->enabled_status_banner( $Item->status ) )
						{ // Status banner
							echo '<div class="cell2">';
							$Item->format_status( array(
									'template' => '<div class="evo_status evo_status__$status$ badge pull-right" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
								) );
							$legend_statuses[] = $Item->status;
							echo '</div>';
						}
					?>
		</div>

		<div class="panel-body">
			<div class="ft_avatar<?php echo $Skin->get_setting( 'voting_place' ) == 'under_content' ? ' col-md-1 col-sm-2' : ''; ?>"><?php
				if( $Skin->get_setting( 'voting_place' ) == 'left_score' )
				{	// Display voting panel instead of author avatar:
					$Skin->display_item_voting_panel( $Item, 'left_score' );
				}
				else
				{	// Display author avatar:
					$Item->author( array(
						'link_text'  => 'only_avatar',
						'thumb_size' => 'crop-top-80x80',
					) );
				}
			?></div>
			<div class="post_main<?php echo $Skin->get_setting( 'voting_place' ) == 'under_content' ? ' col-md-11 col-sm-10' : ''; ?>">

	<div class="row">
		<div class="col-sm-5">
		<?php
			// Display images that are linked to this post:
			$Item->images( array(
					'before'              => $params['before_images'],
					'before_image'        => $params['before_image'],
					'before_image_legend' => $params['before_image_legend'],
					'after_image_legend'  => $params['after_image_legend'],
					'after_image'         => $params['after_image'],
					'after'               => $params['after_images'],
					'image_class'         => $params['image_class'],
					'image_size'          => $params['image_size'],
					'limit'               => $params['image_limit'],
					'image_link_to'       => $params['image_link_to'],
					'before_gallery'      => $params['before_gallery'],
					'after_gallery'       => $params['after_gallery'],
					'gallery_table_start' => $params['gallery_table_start'],
					'gallery_table_end'   => $params['gallery_table_end'],
					'gallery_row_start'   => $params['gallery_row_start'],
					'gallery_row_end'     => $params['gallery_row_end'],
					'gallery_cell_start'  => $params['gallery_cell_start'],
					'gallery_cell_end'    => $params['gallery_cell_end'],
					'gallery_image_size'  => $params['gallery_image_size'],
					'gallery_image_limit' => $params['gallery_image_limit'],
					'gallery_colls'       => $params['gallery_colls'],
					'gallery_order'       => $params['gallery_order'],
					// Optionally restrict to files/images linked to specific position: 'teaser'|'teaserperm'|'teaserlink'|'aftermore'|'inline'|'cover'
					'restrict_to_image_position' => $params['image_positions'],
				) );
		?>
		</div>
		<div class="col-sm-7">
		<?php
			// Item Title:
			$Item->title( array(
					'before'    => $params['item_title_before'],
					'after'     => $params['item_title_after'],
					'link_type' => 'permalink'
				) );

			// Item Content Teaser:
			$Item->content_teaser( array(
					'before'              => $params['before_content_teaser'],
					'after'               => $params['after_content_teaser'],
					'before_image'        => $params['before_image'],
					'before_image_legend' => $params['before_image_legend'],
					'after_image_legend'  => $params['after_image_legend'],
					'after_image'         => $params['after_image'],
					'image_class'         => $params['image_class'],
					'image_size'          => $params['image_size'],
					'limit'               => $params['image_limit'],
					'image_link_to'       => $params['image_link_to'],
					'before_gallery'      => $params['before_gallery'],
					'after_gallery'       => $params['after_gallery'],
					'gallery_table_start' => $params['gallery_table_start'],
					'gallery_table_end'   => $params['gallery_table_end'],
					'gallery_row_start'   => $params['gallery_row_start'],
					'gallery_row_end'     => $params['gallery_row_end'],
					'gallery_cell_start'  => $params['gallery_cell_start'],
					'gallery_cell_end'    => $params['gallery_cell_end'],
					'gallery_image_size'  => $params['gallery_image_size'],
					'gallery_image_limit' => $params['gallery_image_limit'],
					'gallery_colls'       => $params['gallery_colls'],
					'gallery_order'       => $params['gallery_order'],
				) );

			// Tags:
			$Item->tags( array(
					'before'    => '<nav class="small post_tags">',
					'after'     => '</nav>',
					'separator' => ' ',
				) );

			// Custom fields: Course, Cuisine, Servings (if they exist for current Item):
			$Item->custom_fields( array(
					'fields'                               => 'course,cuisine,servings',
					'custom_fields_table_start'            => '',
					'custom_fields_row_start'              => '<div class="row"$row_attrs$>',
					'custom_fields_row_header_field'       => '<div class="col-xs-3 $header_cell_class$"><b>$field_title$$field_description_icon$</b></div>',
					'custom_fields_description_icon_class' => 'grey',
					'custom_fields_value_default'          => '<div class="col-xs-9 $data_cell_class$"$data_cell_attrs$>$field_value$</div>',
					'custom_fields_row_end'                => '</div>',
					'custom_fields_table_end'              => '',
				) );

			// Custom fields: Prep Time, Cook Time, Passive Time, Total time (if they exist for current Item):
			$Item->custom_fields( array(
					'fields'                               => 'prep_time,cook_time,passive_time,total_time',
					'custom_fields_table_start'            => '<br /><div class="row">',
					'custom_fields_row_start'              => '<span$row_attrs$>',
					'custom_fields_row_header_field'       => '<div class="col-sm-3 col-xs-6 $header_cell_class$"><b>$field_title$$field_description_icon$</b>',
					'custom_fields_description_icon_class' => 'grey',
					'custom_fields_value_default'          => '<br /><span class="$data_cell_class$"$data_cell_attrs$>$field_value$</span></div>',
					'custom_fields_row_end'                => '</span>',
					'custom_fields_table_end'              => '</div>',
					'hide_empty_lines'                     => true,
				) );
		?>
		</div>
	</div>

	<div class="row">
		<?php
		// Custom field "Ingredients" (if it exists for current Item):
		$ingredients = $Item->get_custom_field_value( 'ingredients' );
		if( $ingredients !== false )
		{	// Display "Ingredients" only if this custom field exists for the current Item:
		?>
		<div class="col-lg-3 col-sm-4">
			<h4><?php echo $Item->get_custom_field_title( 'ingredients' ); ?></h4>
			<p><?php echo $ingredients; ?></p>
		</div>
		<?php
			$directions_col_size = 'col-lg-9 col-sm-8';
		}
		else
		{	// Use full width if ingredients field is not detected:
			$directions_col_size = 'col-sm-12';
		}

		// Directions:
		?>
		<div class="<?php echo $directions_col_size; ?>">
			<h4><?php echo T_('Directions'); ?></h4>
			<?php
			// Display the "after more" part of the text: (part after "[teaserbreak]")
			$Item->content_extension( array(
					'before'              => $params['before_content_extension'],
					'after'               => $params['after_content_extension'],
					'before_image'        => $params['before_image'],
					'before_image_legend' => $params['before_image_legend'],
					'after_image_legend'  => $params['after_image_legend'],
					'after_image'         => $params['after_image'],
					'image_class'         => $params['image_class'],
					'image_size'          => $params['image_size'],
					'limit'               => $params['image_limit'],
					'image_link_to'       => $params['image_link_to'],
					'force_more'          => true,
				) );

			// Links to post pages (for multipage posts):
			$Item->page_links( array(
					'before'      => $params['page_links_start'],
					'after'       => $params['page_links_end'],
					'separator'   => $params['page_links_separator'],
					'single'      => $params['page_links_single'],
					'current_page'=> $params['page_links_current_page'],
					'pagelink'    => $params['page_links_pagelink'],
					'url'         => $params['page_links_url'],
				) );
			?>
		</div>
	</div>
			</div>
		</div><!-- ../panel-body -->

		<div class="panel-footer clearfix small">
			<?php if( $disp != 'page' ) { ?>
			<a href="<?php echo $Item->get_permanent_url(); ?>#skin_wrapper" class="to_top"><?php echo T_('Back to top'); ?></a>
			<?php
			}
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
					echo '<a href="'.$Item->get_permanent_url().'?mode=quote&amp;qp='.$Item->ID.'#form_p'.$Item->ID.'" title="'.T_('Reply with quote').'" class="'.button_class( 'text' ).' pull-left quote_button">'.get_icon( 'comments', 'imgtag', array( 'title' => T_('Reply with quote') ) ).' '.T_('Quote').'</a>';
				}

				if( $disp != 'page' )
				{	// Display a panel with voting buttons for item:
					$Skin->display_item_voting_panel( $Item, 'under_content' );
				}

				echo '<span class="pull-left">';
					$Item->edit_link( array(
							'before' => ' ',
							'after'  => '',
							'title'  => T_('Edit this topic'),
							'text'   => '#',
							'class'  => button_class( 'text' ).' comment_edit_btn',
						) );
				echo '</span>';
				echo '<div class="action_btn_group">';
					$Item->edit_link( array(
							'before' => ' ',
							'after'  => '',
							'title'  => T_('Edit this topic'),
							'text'   => '#',
							'class'  => button_class( 'text' ).' comment_edit_btn',
						) );
					echo '<span class="'.button_class( 'group' ).'">';
					// Set redirect after publish to the same category view of the items permanent url
					$redirect_after_publish = $Item->add_navigation_param( $Item->get_permanent_url(), 'same_category', $current_cat );
					$Item->next_status_link( array( 'before' => ' ', 'class' => button_class( 'text' ), 'post_navigation' => 'same_category', 'nav_target' => $current_cat ), true );
					$Item->next_status_link( array( 'class' => button_class( 'text' ), 'before_text' => '', 'post_navigation' => 'same_category', 'nav_target' => $current_cat ), false );
					$Item->delete_link( '', '', '#', T_('Delete this topic'), button_class( 'text' ), false, '#', TS_('You are about to delete this post!\\nThis cannot be undone!'), get_caturl( $current_cat ) );
					echo '</span>';
				echo '</div>';
		?>

		</div><!-- ../panel-footer -->
	</div><!-- ../panel panel-default -->
	</section><!-- ../table evo_content_block -->

	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array_merge( $params, array(
			'disp_comments'         => true,
			'disp_comment_form'     => true,
			'disp_trackbacks'       => true,
			'disp_trackback_url'    => true,
			'disp_pingbacks'        => true,
			'disp_webmentions'      => true,
			'disp_meta_comments'    => false,

			'disp_section_title'    => false,
			'disp_meta_comment_info' => false,

			'comment_post_before'   => '<br /><h4 class="evo_comment_post_title ellipsis">',
			'comment_post_after'    => '</h4>',

			'comment_title_before'  => '<div class="panel-heading posts_panel_title_wrapper"><div class="cell1 ellipsis"><h4 class="evo_comment_title panel-title">',
			'comment_status_before' => '</h4></div>',
			'comment_title_after'   => '</div>',

			'comment_avatar_before' => '<span class="evo_comment_avatar'.( $Skin->get_setting( 'voting_place' ) == 'under_content' ? ' col-md-1 col-sm-2' : '' ).'">',
			'comment_avatar_after'  => '</span>',
			'comment_text_before'   => '<div class="evo_comment_text'.( $Skin->get_setting( 'voting_place' ) == 'under_content' ? ' col-md-11 col-sm-10' : '' ).'">',
			'comment_text_after'    => '</div>',
		) ) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.

		echo_comment_moderate_js();

		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>

</div><!-- .col -->

		<?php
		if( $Skin->is_visible_sidebar( 'single' ) )
		{	// Display sidebar:
		?>
		<aside class="col-md-3<?php echo ( $Skin->get_setting_layout( 'single' ) == 'left_sidebar' ? ' pull-left-md' : '' ); ?>">
			<div class="evo_container evo_container__sidebar_single">
			<?php
				// ------------------------- "Sidebar Single" CONTAINER EMBEDDED HERE --------------------------
				// Display container contents:
				skin_container( NT_('Sidebar Single'), array(
						// The following (optional) params will be used as defaults for widgets included in this container:
						// This will enclose each widget in a block:
						'block_start' => '<div class="panel panel-default evo_widget $wi_class$">',
						'block_end' => '</div>',
						// This will enclose the title of each widget:
						'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
						'block_title_end' => '</h4></div>',
						// This will enclose the body of each widget:
						'block_body_start' => '<div class="panel-body">',
						'block_body_end' => '</div>',
						// If a widget displays a list, this will enclose that list:
						'list_start' => '<ul>',
						'list_end' => '</ul>',
						// This will enclose each item in a list:
						'item_start' => '<li>',
						'item_end' => '</li>',
						// This will enclose sub-lists in a list:
						'group_start' => '<ul>',
						'group_end' => '</ul>',
						// This will enclose (foot)notes:
						'notes_start' => '<div class="notes">',
						'notes_end' => '</div>',
						// Widget 'Search form':
						'search_class'         => 'compact_search_form',
						'search_input_before'  => '<div class="input-group">',
						'search_input_after'   => '',
						'search_submit_before' => '<span class="input-group-btn">',
						'search_submit_after'  => '</span></div>',
						// Widget 'Item Custom Fields':
						'custom_fields_table_start'                => '<div class="item_custom_fields">',
						'custom_fields_row_start'                  => '<div class="row"$row_attrs$>',
						'custom_fields_topleft_cell'               => '<div class="col-md-12 col-xs-6" style="border:none"></div>',
						'custom_fields_col_header_item'            => '<div class="col-md-12 col-xs-6 center" width="$col_width$"$col_attrs$>$item_link$$item_status$</div>',  // Note: we will also add reverse view later: 'custom_fields_col_header_field
						'custom_fields_row_header_field'           => '<div class="col-md-12 col-xs-6"><b>$field_title$$field_description_icon$:</b></div>',
						'custom_fields_item_status_template'       => '<div><div class="evo_status evo_status__$status$ badge" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div></div>',
						'custom_fields_description_icon_class'     => 'grey',
						'custom_fields_value_default'              => '<div class="col-md-12 col-xs-6"$data_cell_attrs$>$field_value$</div>',
						'custom_fields_value_difference_highlight' => '<div class="col-md-12 col-xs-6 bg-warning"$data_cell_attrs$>$field_value$</div>',
						'custom_fields_value_green'                => '<div class="col-md-12 col-xs-6 bg-success"$data_cell_attrs$>$field_value$</div>',
						'custom_fields_value_red'                  => '<div class="col-md-12 col-xs-6 bg-danger"$data_cell_attrs$>$field_value$</div>',
						'custom_fields_edit_link_cell'             => '<div class="col-md-12 col-xs-6 center"$edit_link_attrs$>$edit_link$</div>',
						'custom_fields_edit_link_class'            => 'btn btn-xs btn-default',
						'custom_fields_row_end'                    => '</div>',
						'custom_fields_table_end'                  => '</div>',
						// Separate template for separator fields:
						// (Possible to use templates for all field types: 'numeric', 'string', 'html', 'text', 'url', 'image', 'computed', 'separator')
						'custom_fields_separator_row_header_field' => '<div class="col-xs-12" colspan="$cols_count$"><b>$field_title$$field_description_icon$</b></div>',
					) );
				// ----------------------------- END OF "Sidebar Single" CONTAINER -----------------------------
			?>
			</div>
		</aside><!-- .col -->
		<?php } ?>
	</div><!-- .row -->

</div><!-- ../forums_list single_topic -->

<script>
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
<?php
	locale_restore_previous();	// Restore previous locale (Blog locale)
}
?>