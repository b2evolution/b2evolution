<?php
/**
 * This file implements the UI view for the Collection features other properties.
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
global $edited_Blog;


$Form = new Form( NULL, 'coll_other_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'other' );
$Form->hidden( 'blog', $edited_Blog->ID );


$Form->begin_fieldset( T_('Sitemaps').get_manual_link( 'sitemaps-other' ) );
	if( $edited_Blog->get_setting( 'allow_access' ) == 'users' )
	{
		echo '<p class="center orange">'.T_('This collection is for logged in users only.').' '.T_('It is recommended to keep sitemaps disabled.').'</p>';
	}
	elseif( $edited_Blog->get_setting( 'allow_access' ) == 'members' )
	{
		echo '<p class="center orange">'.T_('This collection is for members only.').' '.T_('It is recommended to keep sitemaps disabled.').'</p>';
	}
	$Form->checkbox( 'enable_sitemaps', $edited_Blog->get_setting( 'enable_sitemaps' ),
						T_( 'Enable sitemaps' ), T_( 'Check to allow usage of skins with the "sitemap" type.' ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Subscriptions').get_manual_link( 'subscriptions-other' ) );
	$Form->checkbox( 'allow_subscriptions', $edited_Blog->get_setting( 'allow_subscriptions' ), T_('Email subscriptions'), T_('Allow users to subscribe and receive email notifications for each new post and/or comment.') );
	$Form->checkbox( 'allow_item_subscriptions', $edited_Blog->get_setting( 'allow_item_subscriptions' ), '', T_( 'Allow users to subscribe and receive email notifications for comments on a specific post.' ) );
	// TODO: checkbox 'Enable RSS/Atom feeds'
	// TODO2: which feeds (skins)?
$Form->end_fieldset();

$Form->begin_fieldset( T_('Tracking').get_manual_link( 'tracking-other' ) );
	$Form->checkbox( 'track_unread_content', $edited_Blog->get_setting( 'track_unread_content' ), T_('Tracking of unread content'), T_('Check this if you want this blog to display special marks in case of unread posts and comments.') );
$Form->end_fieldset();

if( isset($GLOBALS['files_Module']) )
{
	load_funcs( 'files/model/_image.funcs.php' );
	$params['force_keys_as_values'] = true;
	
	$Form->begin_fieldset( T_('User directory').get_manual_link( 'user-directory-other' ) );
			$Form->select_input_array( 'image_size_user_list', $edited_Blog->get_setting( 'image_size_user_list' ), get_available_thumb_sizes(), T_('Profile picture size'), '', $params );
	$Form->end_fieldset();
		
	$Form->begin_fieldset( T_('Messaging').get_manual_link( 'messaging-other' ) );
			$Form->select_input_array( 'image_size_messaging', $edited_Blog->get_setting( 'image_size_messaging' ), get_available_thumb_sizes(), T_('Profile picture size'), '', $params );
	$Form->end_fieldset();

}


$Form->begin_fieldset( T_('Archives').get_manual_link( 'archives-other' ) );
	$Form->radio( 'archive_mode', $edited_Blog->get_setting( 'archive_mode' ),
							array(  array( 'monthly', T_('monthly') ),
											array( 'weekly', T_('weekly') ),
											array( 'daily', T_('daily') ),
											array( 'postbypost', T_('post by post') )
										), T_('Archive grouping'), false,  T_('How do you want to browse the post archives? May also apply to permalinks.') );

	// TODO: Hide if archive_mode != 'postbypost' (JS)
	// fp> there should probably be no post by post mode since we do have other ways to list posts now
	// fp> TODO: this is display param and should go to plugin/widget
	$Form->radio( 'archives_sort_order', $edited_Blog->get_setting( 'archives_sort_order' ),
							array(  array( 'date', T_('date') ),
											array( 'title', T_('title') ),
										), T_('Archive sorting'), false,  T_('How to sort your archives? (only in post by post mode)') );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Download pages').get_manual_link( 'download-display-other' ) );
	$Form->text_input( 'download_delay', $edited_Blog->get_setting( 'download_delay' ), 2, T_('Download delay') );
$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>