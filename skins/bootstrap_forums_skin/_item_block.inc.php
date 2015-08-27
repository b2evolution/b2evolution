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

global $Item, $preview, $dummy_fields, $cat, $current_User;

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
		'image_size'         => 'fit-1280x720',
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

	<?php
		// Buttons to prev/next post on single disp
		if( !$Item->is_featured() )
		{
			// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
			item_prevnext_links( array(
					'block_start'     => '<ul class="pager col-lg-12 post_nav">',
					'prev_start'      => '<li class="prev-left">',
					'prev_text'       => '<span aria-hidden="true">&larr;</span> $title$',
					'prev_end'        => '</li>',
					'separator'       => ' ',
					'next_start'      => '<li class="next-right">',
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

<div id="styled_content_block" class="forums_list single_topic">
	<?php /* This empty row is used to fix columns width, when table has css property "table-layout:fixed" */ ?>
	
	<div class="single_page_title">
		<?php
		// Page title
		$Item->title( array(
				'before'    => '<h2>',
				'after'     => '</h2>',
				'link_type' => 'permalink'
			) );
				// Author info:
				echo '<div class="ft_author_info">'.T_('Thread started by');
				$Item->author( array( 'link_text' => 'login', 'after' => '' ) );
				echo ', '.mysql2date( 'D M j, Y H:i', $Item->datecreated );
				echo '<span class="text-muted"> &ndash; '
						.T_('Last touched:').' '.mysql2date( 'D M j, Y H:i', $Item->get( 'last_touched_ts' ) )
					.'</span>';
				echo '</div>';
				// Author info - shrinked:
				echo '<div class="ft_author_info shrinked">'.T_('Started by');
				$Item->author( array( 'link_text' => 'login', 'after' => '' ) );
				echo ', '.mysql2date( 'm/j/y', $Item->datecreated );
				echo '</div>';
		?>
	</div>
	
	<section class="table evo_content_block">
	<div class="panel panel-default">
		<div class="panel-heading posts_panel_title_wrapper">
			<div class="cell1 ellipsis">
				<h4 class="evo_comment_title panel-title"><a href="<?php echo $Item->get_permanent_url(); ?>" class="permalink">#1</a>
					<?php
						$Item->author( array(
							'link_text' => 'login',
						) );
					?>
					<?php
						if( $Skin->get_setting( 'display_post_date' ) )
						{ // We want to display the post date:
							$Item->issue_time( array(
									'before'      => '',
									'after'       => ' &nbsp; &nbsp; ',
									'time_format' => 'M j, Y H:i',
								) );
						}
					?>
				</h4>
			</div>	
					<?php
						if( $Skin->enabled_status_banner( $Item->status ) )
						{ // Status banner
							echo '<div class="cell2">';
							$Item->format_status( array(
									'template' => '<div class="evo_status evo_status__$status$ badge pull-right">$status_title$</div>',
								) );
							$legend_statuses[] = $Item->status;
							echo '</div>';
						}
					?>	
		</div>
		
		<div class="panel-body">
			<div class="ft_avatar col-md-1 col-sm-2"><?php
				$Item->author( array(
					'link_text'  => 'only_avatar',
					'thumb_size' => 'crop-top-80x80',
				) );
			?></div>
			<div class="post_main col-md-11 col-sm-10">
				<?php
					// ---------------------- POST CONTENT INCLUDED HERE ----------------------
					skin_include( '_item_content.inc.php', $params );
					// Note: You can customize the default item content by copying the generic
					// /skins/_item_content.inc.php file into the current skin folder.
					// -------------------------- END OF POST CONTENT -------------------------

				if( ! $Item->is_intro() )
				{ // List all tags attached to this topic:
					$Item->tags( array(
							'before'    => '<nav class="small post_tags">',
							'after'     => '</nav>',
							'separator' => ' ',
						) );
				}
				?>
			</div>
		</div><!-- ../panel-body -->
		
		<div class="panel-footer clearfix">
		<a href="<?php echo $Item->get_permanent_url(); ?>#skin_wrapper" class="to_top"><?php echo T_('Back to top'); ?></a>
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
			if( $bbcode_plugin_is_enabled && $Item->can_comment( NULL ) )
			{	// Display button to quote this post
				echo '<a href="'.$Item->get_permanent_url().'?mode=quote&amp;qp='.$Item->ID.'#form_p'.$Item->ID.'" title="'.T_('Reply with quote').'" class="'.button_class( 'text' ).' pull-left quote_button">'.get_icon( 'comments', 'imgtag', array( 'title' => T_('Reply with quote') ) ).' '.T_('Quote').'</a>';
			}
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
		
		</div><!-- ../panel-footer -->
	</div><!-- ../panel panel-default -->
	</section><!-- ../table evo_content_block -->
	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array ( 
			'disp_section_title'    => false,
			'comment_post_before'   => '<h4 class="evo_comment_post_title ellipsis">',
			'comment_post_after'    => '</h4>',

			'comment_title_before'  => '<div class="panel-heading posts_panel_title_wrapper"><div class="cell1 ellipsis"><h4 class="evo_comment_title panel-title">',
			'comment_status_before' => '</h4></div>',
			'comment_title_after'   => '</div>',

			'comment_avatar_before' => '<div class="panel-body"><span class="evo_comment_avatar col-md-1 col-sm-2">',
			'comment_avatar_after'  => '</span>',
			'comment_text_before'   => '<div class="evo_comment_text col-md-11 col-sm-10">',
			'comment_text_after'    => '</div>',
		) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.

echo_comment_moderate_js();

		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------

	if( is_logged_in() && $Blog->get_setting( 'use_workflow' ) && $current_User->check_perm( 'item_post!CURSTATUS', 'edit', true, $Item ) )
	{ // Display workflow properties if current user can edit this post:
		$Form = new Form( '' );

		$Form->begin_form( '' );

		$Form->begin_fieldset( T_('Workflow properties') );

		$Form->info( T_('Priority'), '<div class="task_priority_edit" rel="'.$Item->ID.'">'.item_td_task_cell( 'priority', $Item ).'</div>' );

		$Form->info( T_('Assigned to'), '<div class="task_assigned_edit" rel="'.$Item->ID.'">'.item_td_task_cell( 'assigned', $Item ).'</div>' );

		$Form->info( T_('Priority'), '<div class="task_status_edit" rel="'.$Item->ID.'">'.item_td_task_cell( 'status', $Item ).'</div>' );

		$Form->end_fieldset();

		$Form->end_form();

		// Print JS to edit a task priority
		echo_editable_column_js( array(
			'column_selector' => '.task_priority_edit',
			'ajax_url'        => get_secure_htsrv_url().'anon_async.php?action=item_task_edit&field=priority&'.url_crumb( 'itemtask' ),
			'options'         => item_priority_titles(),
			'new_field_name'  => 'new_priority',
			'ID_value'        => 'jQuery( this ).attr( "rel" )',
			'ID_name'         => 'post_ID' ) );

		// Print JS to edit a task assigned
		// Load current blog members into cache:
		$UserCache = & get_UserCache();
		// Load only first 21 users to know when we should display an input box instead of full users list
		$UserCache->load_blogmembers( $Blog->ID, 21, false );
		// Init this array only for <select> when we have less than 21 users, otherwise we use <input> field with autocomplete feature
		$field_type = count( $UserCache->cache ) < 21 ? 'select' : 'text';

		$task_assignees = array( 0 => T_('No user') );
		if( $field_type == 'select' )
		{
			foreach( $UserCache->cache as $User )
			{
				$task_assignees[ $User->ID ] = $User->login;
			}
		}
		echo_editable_column_js( array(
			'column_selector' => '.task_assigned_edit',
			'ajax_url'        => get_secure_htsrv_url().'anon_async.php?action=item_task_edit&field=assigned&'.url_crumb( 'itemtask' ),
			'options'         => $task_assignees,
			'new_field_name'  => $field_type == 'select' ? 'new_assigned_ID' : 'new_assigned_login',
			'ID_value'        => 'jQuery( this ).attr( "rel" )',
			'ID_name'         => 'post_ID',
			'field_type'      => $field_type,
			'field_class'     => 'autocomplete_login only_assignees',
			'null_text'       => TS_('No user') ) );

		// Print JS to edit a task status
		$ItemStatusCache = & get_ItemStatusCache();
		$ItemStatusCache->load_all();
		$task_statuses = array( 0 => T_('No status') );
		foreach( $ItemStatusCache->cache as $ItemStatus )
		{
			// Add '_' to don't break a sorting by name on jeditable:
			$task_statuses[ '_'.$ItemStatus->ID ] = $ItemStatus->name;
		}
		echo_editable_column_js( array(
			'column_selector' => '.task_status_edit',
			'ajax_url'        => get_secure_htsrv_url().'anon_async.php?action=item_task_edit&field=status&'.url_crumb( 'itemtask' ),
			'options'         => $task_statuses,
			'new_field_name'  => 'new_status',
			'ID_value'        => 'jQuery( this ).attr( "rel" )',
			'ID_name'         => 'post_ID' ) );
	}
	?>
</div><!-- ../forums_list single_topic -->

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