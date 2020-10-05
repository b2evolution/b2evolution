<?php
/**
 * This file implements the UI view for the site settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var GeneralSettings
 */
global $Settings;

global $admin_url;

global $collections_Module;

global $instance_name;

$Form = new Form( NULL, 'settings_checkchanges' );
$Form->begin_form( 'fform', '',
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

$Form->add_crumb( 'collectionsettings' );
$Form->hidden( 'ctrl', 'collections' );
$Form->hidden( 'tab', get_param( 'tab' ) );
$Form->hidden( 'action', 'update_settings_site' );

// --------------------------------------------

if( check_user_perm( 'users', 'edit' ) )
{
	$Form->begin_fieldset( TB_('Locking down b2evolution for maintenance, upgrade or server switching...').get_manual_link('system-lock') );
		$Form->checkbox_input( 'system_lock', $Settings->get('system_lock'), TB_('Lock system'), array(
				'note' => TB_('check this to prevent login (except for admins) and sending comments/messages. This prevents the DB from receiving updates (other than logging)').'<br />'.
				          TB_('Note: for a more complete lock down, rename the file /conf/_maintenance.html to /conf/maintenance.html (complete lock) or /conf/imaintenance.html (gives access to /install)') ) );
	$Form->end_fieldset();
}

// --------------------------------------------

$Form->begin_fieldset( TB_('Global Site Settings').get_manual_link('global-site-settings') );

	$Form->text_input( 'site_code', $Settings->get( 'site_code' ), 10, TB_('Site code'), '$instance_name = '.$instance_name, array( 'maxlength' => 20 ) );
	$Form->color_input( 'site_color', $Settings->get( 'site_color' ), TB_('Site color') );
	$Form->text_input( 'notification_short_name', $Settings->get( 'notification_short_name' ), 50, TB_( 'Short site name' ), TB_('Shared with email settings'), array( 'maxlength' => 127, 'required' => true ) );
	$Form->text_input( 'notification_long_name', $Settings->get( 'notification_long_name' ), 50, TB_( 'Long site name' ), TB_('Shared with email settings'), array( 'maxlength' => 255 ) );
	$site_logo_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => TB_('Select site logo'), 'root' => 'shared_0', 'size_name' => 'fit-320x320' );
	$Form->fileselect( 'notification_logo_file_ID', $Settings->get( 'notification_logo_file_ID' ), TB_('Site logo'), NULL, $site_logo_params );
	$social_media_boilerplate_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => TB_('Select logo for social media boilerplate'), 'root' => 'shared_0', 'size_name' => 'fit-320x320' );
	$Form->fileselect( 'social_media_image_file_ID', $Settings->get( 'social_media_image_file_ID' ), TB_('Social media boilerplate'), NULL, $social_media_boilerplate_params );
	$Form->checkbox_input( 'site_skins_enabled', $Settings->get( 'site_skins_enabled' ), TB_('Enable site skins'), array( 'note' => TB_('Enables a sitewide header and footer') ) );
	$Form->begin_line( TB_('Terms & Conditions'), 'site_terms_enabled' );
		$Form->checkbox_input( 'site_terms_enabled', $Settings->get( 'site_terms_enabled' ), '' );
		$Form->text_input( 'site_terms', $Settings->get( 'site_terms' ), 7, TB_('Enable and display Post ID:'), TB_('Enter ID of the page containing the terms & conditions.'), array( 'maxlength' => 11 ) );
	$Form->end_line();

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( TB_('Default collections').get_manual_link('default-collections') );

	$BlogCache = & get_BlogCache();

	$create_new_blog_link = ' <a href="'.$admin_url.'?ctrl=collections&action=new">'.TB_('Create new collection').' &raquo;</a>';

	$Form->select_input_object( 'default_blog_ID', $Settings->get( 'default_blog_ID' ), $BlogCache, get_icon( 'coll_default' ).' '.TB_('Default collection to display'), array(
			'note' => TB_('This collection will be displayed on index.php.').$create_new_blog_link,
			'allow_none' => false,
			'loop_object_method' => 'get_maxlen_name',
			'prepend_options' => array(
					0  => TB_('None - display default page instead'),
					-1 => TB_('None - display back-office instead'),
				)
	) );

	$BlogCache->none_option_text = TB_('Current collection');
	$Form->select_input_object( 'login_blog_ID', $Settings->get( 'login_blog_ID' ), $BlogCache, get_icon( 'coll_login' ).' '.TB_('Collection for login/registration'), array(
		'note' => TB_('This collection will be used for all login/registration functions.').$create_new_blog_link,
		'allow_none' => true,
		'loop_object_method' => 'get_maxlen_name' ) );

	$Form->select_input_object( 'msg_blog_ID', $Settings->get( 'msg_blog_ID' ), $BlogCache, get_icon( 'coll_message' ).' '.TB_('Collection for profiles/messaging'), array(
		'note' => TB_('This collection will be used for all messaging, profile viewing and profile editing functions.').$create_new_blog_link,
		'allow_none' => true,
		'loop_object_method' => 'get_maxlen_name' ) );

	$BlogCache->none_option_text = TB_('No shared collection');
	$Form->select_input_object( 'info_blog_ID', $Settings->get( 'info_blog_ID' ), $BlogCache, get_icon( 'coll_info' ).' '.TB_('Collection for shared content blocks'), array(
		'note' => TB_('The content blocks of this collections can be included anywhere.').$create_new_blog_link,
		'allow_none' => true,
		'loop_object_method' => 'get_maxlen_name' ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( TB_('Technical Site Settings').get_manual_link('technical-site-settings') );

	$Form->duration_input( 'reloadpage_timeout', (int)$Settings->get('reloadpage_timeout'), TB_('Reload-page timeout'), 'minutes', 'seconds', array( 'minutes_step' => 1, 'required' => true ) );
	// $Form->text_input( 'reloadpage_timeout', (int)$Settings->get('reloadpage_timeout'), 5,
	// TB_('Reload-page timeout'), TB_('Time (in seconds) that must pass before a request to the same URI from the same IP and useragent is considered as a new hit.'), array( 'maxlength'=>5, 'required'=>true ) );

	$Form->checkbox_input( 'general_cache_enabled', $Settings->get('general_cache_enabled'), get_icon( 'page_cache_on' ).' '.TB_('Enable general cache'), array( 'note'=>TB_('Cache rendered pages that are not controlled by a skin. See Blog Settings for skin output caching.') ) );

$Form->end_fieldset();

// --------------------------------------------

if( check_user_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton' ) ) );
}

?>