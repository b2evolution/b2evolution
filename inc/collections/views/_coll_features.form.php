<?php
/**
 * This file implements the UI view for the Collection features properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog, $AdminUI;


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
  $Form->radio( 'what_to_show', $edited_Blog->get_setting('what_to_show'),
                array(  array( 'days', T_('days') ),
                        array( 'posts', T_('posts') ),
                      ), T_('Display unit'), false,  T_('Do you want to restrict on the number of days or the number of posts?') );
  $Form->text( 'posts_per_page', $edited_Blog->get_setting('posts_per_page'), 4, T_('Posts/Days per page'), T_('How many days or posts do you want to display on the home page?'), 4 );

  $Form->radio( 'timestamp_min', $edited_Blog->get_setting('timestamp_min'),
                array(  array( 'yes', T_('yes') ),
                        array( 'no', T_('no') ),
                        array( 'duration', T_('only the last') ),
                      ), T_('Show past posts'), true );
  $Form->duration_input( 'timestamp_min_duration', $edited_Blog->get_setting('timestamp_min_duration'), '' );

  $Form->radio( 'timestamp_max', $edited_Blog->get_setting('timestamp_max'),
                array(  array( 'yes', T_('yes') ),
                        array( 'no', T_('no') ),
                        array( 'duration', T_('only the next') ),
                      ), T_('Show future posts'), true );
  $Form->duration_input( 'timestamp_max_duration', $edited_Blog->get_setting('timestamp_max_duration'), '' );

  $Form->checklist( get_inskin_statuses_options( $edited_Blog, 'post' ), 'post_inskin_statuses', T_('Front office statuses'), false, false, array( 'note' => 'Uncheck the statuses that should never appear in the front office.' ) );

$Form->end_fieldset();


$Form->begin_fieldset( T_('Post options').get_manual_link('blog_features_settings'), array( 'id' => 'post_options' ) );

	$Form->radio( 'enable_goto_blog', $edited_Blog->get_setting( 'enable_goto_blog' ),
		array( array( 'no', T_( 'No' ), T_( 'Check this to view list of the posts.' ) ),
			array( 'blog', T_( 'View home page' ), T_( 'Check this to automatically view the blog after publishing a post.' ) ),
			array( 'post', T_( 'View new post' ), T_( 'Check this to automatically view the post page.' ) ), ),
			T_( 'View blog after publishing' ), true );

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

	$Form->radio( 'post_navigation', $edited_Blog->get_setting('post_navigation'),
		array( array( 'same_blog', T_('same blog') ),
			array( 'same_category', T_('same category') ),
			array( 'same_author', T_('same author') ),
			array( 'same_tag', T_('same tag') ) ),
			T_('Default post by post navigation should stay in'), true, T_( 'Skins may override this setting!') );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Post moderation') . get_manual_link('post-moderation') );

	if( isset( $AdminUI, $AdminUI->skin_name ) && $AdminUI->skin_name == 'bootstrap' )
	{	// Use dropdown for bootstrap skin:
		$default_status_field = get_status_dropdown_button( array(
				'name'         => 'default_post_status',
				'value'        => $edited_Blog->get_setting('default_post_status'),
				'title_format' => 'notes-string',
			) );
		$Form->info( T_('Default status'), $default_status_field, T_('Default status for new posts') );
		$Form->hidden( 'default_post_status', $edited_Blog->get_setting('default_post_status') );
		echo_form_dropdown_js();
	}
	else
	{	// Use standard select element for other skins:
		$Form->select_input_array( 'default_post_status', $edited_Blog->get_setting('default_post_status'), get_visibility_statuses( 'notes-string' ), T_('Default status'), T_('Default status for new posts') );
	}

	// Moderation statuses setting
	$not_moderation_statuses = array_diff( get_visibility_statuses( 'keys', NULL ), get_visibility_statuses( 'moderation' ) );
	// Get moderation statuses with status text
	$moderation_statuses = get_visibility_statuses( '', $not_moderation_statuses );
	$moderation_status_icons = get_visibility_statuses( 'icons', $not_moderation_statuses );
	$blog_moderation_statuses = $edited_Blog->get_setting( 'post_moderation_statuses' );
	$checklist_options = array();
	foreach( $moderation_statuses as $status => $status_text )
	{ // Add a checklist option for each possible modeartion status
		$is_checked = ( strpos( $blog_moderation_statuses, $status) !== false );
		$checklist_options[] = array( 'post_notif_'.$status, 1, $moderation_status_icons[ $status ].' '.$status_text, $is_checked );
	}
	$Form->checklist( $checklist_options, 'post_moderation_statuses', T_('Post moderation reminder statuses'), false, false, array( 'note' => 'Posts with the selected statuses will be notified on the "Send reminders about posts awaiting moderation" scheduled job.' ) );

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

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>