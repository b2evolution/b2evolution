<?php
/**
 * This file implements the UI view for the Collection features properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog, $AdminUI, $Settings;
$notifications_mode = $Settings->get( 'outbound_notifications_mode' );

$Form = new Form( NULL, 'coll_features_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'features' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Post list').get_manual_link('item-list-features') );
	$Form->select_input_array( 'orderby', $edited_Blog->get_setting('orderby'), get_available_sort_options(), T_('Order by'), T_('Default ordering of posts.') );
	$Form->select_input_array( 'orderdir', $edited_Blog->get_setting('orderdir'), array(
												'ASC'  => T_('Ascending'),
												'DESC' => T_('Descending'), ), T_('Direction') );
	$Form->begin_line( T_('Display') );
		$Form->text( 'posts_per_page', $edited_Blog->get_setting('posts_per_page'), 4, '', '', 4 );
		$Form->radio( 'what_to_show', $edited_Blog->get_setting('what_to_show'),
									array(  array( 'days', T_('days') ),
													array( 'posts', T_('posts') ),
												), '' );
	$Form->end_line( T_('per page') );

	$Form->checkbox( 'disp_featured_above_list', $edited_Blog->get_setting( 'disp_featured_above_list' ), T_('Featured post above list'), T_('Check to display a featured post above the list (as long as no Intro post is displayed).') );

	$ItemTypeCache = & get_ItemTypeCache();
	$enabled_item_types = $edited_Blog->get_enabled_item_types( 'post' );
	$show_post_types_options = array();
	$show_post_types_values = explode( ',', $edited_Blog->get_setting( 'show_post_types' ) );
	foreach( $enabled_item_types as $enabled_item_type_ID )
	{
		if( ( $enabled_ItemType = & $ItemTypeCache->get_by_ID( $enabled_item_type_ID, false, false ) ) )
		{
			$show_post_types_options[] = array( 'show_post_types[]', $enabled_item_type_ID, $enabled_ItemType->get_name(), ! in_array( $enabled_item_type_ID, $show_post_types_values ) );
		}
	}
	$Form->checklist( $show_post_types_options, '', T_('Show post types') );

	$Form->output = false;
	$Form->switch_layout( 'none' );
	$timestamp_min_duration_input = $Form->duration_input( 'timestamp_min_duration', $edited_Blog->get_setting('timestamp_min_duration'), '' );
	$Form->switch_layout( NULL );
	$Form->output = true;
	$Form->radio( 'timestamp_min', $edited_Blog->get_setting('timestamp_min'),
								array(  array( 'yes', T_('yes') ),
												array( 'no', T_('no') ),
												array( 'duration', T_('only the last'), '', $timestamp_min_duration_input ),
											), T_('Show past posts'), true );

	$Form->output = false;
	$Form->switch_layout( 'none' );
	$timestamp_max_duration_input = $Form->duration_input( 'timestamp_max_duration', $edited_Blog->get_setting('timestamp_max_duration'), '' );
	$Form->switch_layout( NULL );
	$Form->output = true;
	$Form->radio( 'timestamp_max', $edited_Blog->get_setting('timestamp_max'),
								array(  array( 'yes', T_('yes') ),
												array( 'no', T_('no') ),
												array( 'duration', T_('only the next'), '', $timestamp_max_duration_input ),
											), T_('Show future posts'), true );

	$Form->checklist( get_inskin_statuses_options( $edited_Blog, 'post' ), 'post_inskin_statuses', T_('Front office statuses'), false, false, array( 'note' => 'Uncheck the statuses that should never appear in the front office.' ) );

	$Form->radio( 'main_content', $edited_Blog->get_setting('main_content'),
	array(
			array( 'excerpt', T_('Post excerpts'), '('.T_('No Teaser images will be displayed on default skins').')' ),
			array( 'normal', T_('Standard post contents (stopping at "[teaserbreak]")'), '('.T_('Teaser images will be displayed').')' ),
			array( 'full', T_('Full post contents (including after "[teaserbreak]")'), '('.T_('All images will be displayed').')' ),
		), T_('Post contents'), true );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Post options').get_manual_link('blog_features_settings'), array( 'id' => 'post_options' ) );

	$Form->radio( 'enable_goto_blog', $edited_Blog->get_setting( 'enable_goto_blog' ),
		array( array( 'no', T_( 'No' ), T_( 'Check this to view list of the posts.' ) ),
			array( 'blog', T_( 'View home page' ), T_( 'Check this to automatically view the blog after publishing a post.' ) ),
			array( 'post', T_( 'View new post' ), T_( 'Check this to automatically view the post page.' ) ), ),
			T_( 'View blog after creating' ), true );

	$Form->radio( 'editing_goto_blog', $edited_Blog->get_setting( 'editing_goto_blog' ),
		array( array( 'no', T_( 'No' ), T_( 'Check this to view list of the posts.' ) ),
			array( 'blog', T_( 'View home page' ), T_( 'Check this to automatically view the blog after editing a post.' ) ),
			array( 'post', T_( 'View edited post' ), T_( 'Check this to automatically view the post page.' ) ), ),
			T_( 'View blog after editing' ), true );

	// FP> TODO:
	// -post_url  always('required')|optional|never
	// -multilingual:  true|false   or better yet: provide a list to narrow down the active locales
	// -tags  always('required')|optional|never

	$Form->radio( 'post_categories', $edited_Blog->get_setting('post_categories'),
		array( array( 'one_cat_post', T_('Allow only one category per post') ),
			array( 'multiple_cat_post', T_('Allow multiple categories per post') ),
			array( 'main_extra_cat_post', T_('Allow one main + several extra categories') ),
			array( 'no_cat_post', T_('Don\'t allow category selections'), T_('(Main cat will be assigned automatically)') ) ),
			T_('Post category options'), true );

	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// Permission to edit advanced admin settings:
		$Form->checklist( array(
				array( 'in_skin_editing', 1, T_('Allow posting/editing from the Front-Office').get_admin_badge(), $edited_Blog->get_setting( 'in_skin_editing' ) ),
				array( 'in_skin_editing_renderers', 1, T_('Allow Text Renderers selection in Front-Office edit screen').get_admin_badge(), $edited_Blog->get_setting( 'in_skin_editing_renderers' ), ! $edited_Blog->get_setting( 'in_skin_editing' ) ),
				array( 'in_skin_editing_category', 1, T_('Allow Category selection in Front-Office edit screen').get_admin_badge(), $edited_Blog->get_setting( 'in_skin_editing_category' ), ! $edited_Blog->get_setting( 'in_skin_editing' ) ),
			), 'front_office_posting', T_('Front-Office posting') );
	}

	$Form->radio( 'post_navigation', $edited_Blog->get_setting('post_navigation'),
		array( array( 'same_blog', T_('same blog') ),
			array( 'same_category', T_('same category') ),
			array( 'same_author', T_('same author') ),
			array( 'same_tag', T_('same tag') ) ),
			T_('Default post by post navigation should stay in'), true, T_( 'Skins may override this setting!') );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Voting options').get_manual_link( 'item-voting-options' ), array( 'id' => 'voting_options' ) );

	$voting_disabled = ! $edited_Blog->get_setting( 'voting_positive' );

	$Form->checkbox( 'voting_positive', $edited_Blog->get_setting( 'voting_positive' ), T_('Allow Positive vote'), get_icon( 'thumb_up', 'imgtag', array( 'title' => T_('Allow Positive vote') ) ) );

	$Form->checkbox( 'voting_neutral', $edited_Blog->get_setting( 'voting_neutral' ), T_('Allow Neutral vote'), get_icon( 'ban', 'imgtag', array( 'title' => T_('Allow Neutral vote') ) ), '', 1, $voting_disabled );

	$Form->checkbox( 'voting_negative', $edited_Blog->get_setting( 'voting_negative' ), T_('Allow Negative vote'), get_icon( 'thumb_down', 'imgtag', array( 'title' => T_('Allow Negative vote') ) ), '', 1, $voting_disabled );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Post moderation').get_manual_link( 'post-moderation' ) );

	// Get max allowed visibility status:
	$max_allowed_status = get_highest_publish_status( 'comment', $edited_Blog->ID, false );

	// Get those statuses which are not allowed for the current User to create posts in this blog
	$exclude_statuses = array_merge( get_restricted_statuses( $edited_Blog->ID, 'blog_post!', 'create' ), array( 'trash' ) );
	$default_post_status_index = array_search( $edited_Blog->get_setting( 'default_post_status' ), $exclude_statuses );
	if( $default_post_status_index !== false )
	{	// Allow to select status that is selected currently:
		unset( $exclude_statuses[ $default_post_status_index ] );
	}

	if( isset( $AdminUI, $AdminUI->skin_name ) && $AdminUI->skin_name == 'bootstrap' )
	{	// Use dropdown for bootstrap skin:
		$default_status_field = get_status_dropdown_button( array(
				'name'             => 'default_post_status',
				'value'            => $edited_Blog->get_setting('default_post_status'),
				'title_format'     => 'notes-string',
				'exclude_statuses' => $exclude_statuses,
			) );
		$Form->info( T_('Default status'), $default_status_field, T_('Default status for new posts') );
		$Form->hidden( 'default_post_status', $edited_Blog->get_setting('default_post_status') );
		echo_form_dropdown_js();
	}
	else
	{	// Use standard select element for other skins:
		$Form->select_input_array( 'default_post_status', $edited_Blog->get_setting('default_post_status'), get_visibility_statuses( 'notes-string', $exclude_statuses ), T_('Default status'), T_('Default status for new posts') );
	}

	// Moderation statuses setting:
	$all_statuses = get_visibility_statuses( 'keys', NULL );
	$not_moderation_statuses = array_diff( $all_statuses, get_visibility_statuses( 'moderation' ) );
	// Get moderation statuses with status text:
	$moderation_statuses = get_visibility_statuses( '', $not_moderation_statuses );
	$moderation_status_icons = get_visibility_statuses( 'icons', $not_moderation_statuses );
	$blog_moderation_statuses = $edited_Blog->get_setting( 'post_moderation_statuses' );
	$checklist_options = array();
	// Set this flag to false in order to find first allowed status below:
	$status_is_hidden = true;
	foreach( $all_statuses as $status )
	{	// Add a checklist option for each possible modeartion status:
		if( $status == $max_allowed_status )
		{	// This is first allowed status, then all next statuses are also allowed:
			$status_is_hidden = false;
		}
		if( ! isset( $moderation_statuses[ $status ] ) )
		{	// Don't display a checkbox for non moderation status:
			continue;
		}
		$checklist_options[] = array(
				'post_notif_'.$status, // Field name of checkbox
				1, // Field value
				$moderation_status_icons[ $status ].' '.$moderation_statuses[ $status ], // Text
				( strpos( $blog_moderation_statuses, $status) !== false ), // Checked?
				'', // Disabled?
				'', // Note
				'', // Class
				$status_is_hidden, // Hidden field instead of checkbox?
				array(
					'data-toggle' => 'tooltip',
					'data-placement' => 'top',
					'title' => get_status_tooltip_title( $status ) )
			);
	}
	$Form->checklist( $checklist_options, 'post_moderation_statuses', T_('"Require moderation" statuses'), false, false, array( 'note' => T_('Posts with the selected statuses will be considered to require moderation. They will trigger "moderation required" notifications and will appear as such on the collection dashboard.') ) );

	$Form->text_input( 'old_content_alert', $edited_Blog->get_setting( 'old_content_alert' ), 2, T_('Stale content alert'), T_('Posts that have not been updated within the set delay will be reported to content moderators. Leave empty if you don\'t want such alerts.'), array( 'input_suffix' => ' '.T_('months').'.' ) );

$Form->end_fieldset();

// display features settings provided by optional modules:
modules_call_method( 'display_collection_features', array( 'Form' => & $Form, 'edited_Blog' => & $edited_Blog ) );

$Form->begin_fieldset( T_('RSS/Atom feeds').get_manual_link('item-feeds-features') );
	if( $edited_Blog->get_setting( 'allow_access' ) == 'users' )
	{
		echo '<p class="center orange">'.T_('This collection is for logged in users only.').' '.T_('It is recommended to keep feeds disabled.').'</p>';
	}
	elseif( $edited_Blog->get_setting( 'allow_access' ) == 'members' )
	{
		echo '<p class="center orange">'.T_('This collection is for members only.').' '.T_('It is recommended to keep feeds disabled.').'</p>';
	}
	$Form->radio( 'feed_content', $edited_Blog->get_setting('feed_content'),
								array(  array( 'none', T_('No feeds') ),
												array( 'title', T_('Titles only') ),
												array( 'excerpt', T_('Post excerpts') ),
												array( 'normal', T_('Standard post contents (stopping at "[teaserbreak]")') ),
												array( 'full', T_('Full post contents (including after "[teaserbreak]")') ),
											), T_('Post feed contents'), true, T_('How much content do you want to make available in post feeds?') );

	$Form->text( 'posts_per_feed', $edited_Blog->get_setting('posts_per_feed'), 4, T_('Posts in feeds'),  T_('How many of the latest posts do you want to include in RSS & Atom feeds?'), 4 );

	if( isset($GLOBALS['files_Module']) )
	{
		load_funcs( 'files/model/_image.funcs.php' );
		$params['force_keys_as_values'] = true;
		$Form->select_input_array( 'image_size', $edited_Blog->get_setting('image_size') , get_available_thumb_sizes(), T_('Image size'), '', $params );
	}
$Form->end_fieldset();

if( $notifications_mode != 'off' )
{
	$Form->begin_fieldset( T_('Subscriptions').get_manual_link( 'item-subscriptions' ) );
		$Form->checkbox( 'allow_subscriptions', $edited_Blog->get_setting( 'allow_subscriptions' ), T_('Email subscriptions'), T_('Allow users to subscribe and receive email notifications for each new post.') );
		$Form->checkbox( 'allow_item_subscriptions', $edited_Blog->get_setting( 'allow_item_subscriptions' ), '', T_( 'Allow users to subscribe and receive email notifications for comments on a specific post.' ) );
	$Form->end_fieldset();
}

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>
<script type="text/javascript">
jQuery( 'input[name=in_skin_editing]' ).click( function()
{
	if( jQuery( this ).is( ':checked' ) )
	{
		jQuery( 'input[name=in_skin_editing_renderers]' ).removeAttr( 'disabled' );
		jQuery( 'input[name=in_skin_editing_category]' ).removeAttr( 'disabled' );
	}
	else
	{
		jQuery( 'input[name=in_skin_editing_renderers]' ).attr( 'disabled', 'disabled' );
		jQuery( 'input[name=in_skin_editing_category]' ).attr( 'disabled', 'disabled' );
	}
} );

jQuery( '#voting_positive' ).click( function()
{
	if( jQuery( this ).is( ':checked' ) )
	{
		jQuery( '#voting_neutral, #voting_negative' ).removeAttr( 'disabled' );
	}
	else
	{
		jQuery( '#voting_neutral, #voting_negative' ).attr( 'disabled', 'disabled' ).removeAttr( 'checked' );
	}
} );
</script>