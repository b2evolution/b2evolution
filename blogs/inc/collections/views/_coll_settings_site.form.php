<?php
/**
 * This file implements the UI view for the site settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _coll_settings_site.form.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;
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

if( $current_User->check_perm( 'users', 'edit' ) )
{
	$Form->begin_fieldset( T_('Locking down b2evolution for maintenance, upgrade or server switching...').get_manual_link('system-lock') );
		$Form->checkbox_input( 'system_lock', $Settings->get('system_lock'), T_('Lock system'), array(
				'note' => T_('check this to prevent login (except for admins) and sending comments/messages. This prevents the DB from receiving updates (other than logging)').'<br />'.
				          T_('Note: for a more complete lock down, rename the file /conf/_maintenance.html to /conf/maintenance.html (complete lock) or /conf/imaintenance.html (gives access to /install)') ) );
	$Form->end_fieldset();
}

// --------------------------------------------

$Form->begin_fieldset( T_('Site Settings').get_manual_link('site-settings') );

	$Form->text_input( 'site_code', $Settings->get( 'site_code' ), 10, T_('Site code'), '$instance_name = '.$instance_name, array( 'maxlength' => 20 ) );
	$Form->color_input( 'site_color', $Settings->get( 'site_color' ), T_('Site color'), T_('E-g: #ff0000 for red') );
	$Form->text_input( 'notification_short_name', $Settings->get( 'notification_short_name' ), 50, T_( 'Short site name' ), T_('Shared with email settings'), array( 'maxlength' => 127, 'required' => true ) );
	$Form->text_input( 'notification_long_name', $Settings->get( 'notification_long_name' ), 50, T_( 'Long site name' ), T_('Shared with email settings'), array( 'maxlength' => 255 ) );
	$Form->text_input( 'notification_logo', $Settings->get( 'notification_logo' ), 50, T_( 'Small site logo (URL)' ), T_('Shared with email settings'), array( 'maxlength' => 5000 ) );
	$Form->text_input( 'notification_logo_large', $Settings->get( 'notification_logo_large' ), 50, T_( 'Large site logo (URL)' ), '', array( 'maxlength' => 5000 ) );
	$Form->text_input( 'site_footer_text', $Settings->get( 'site_footer_text' ), 50, T_('Site footer text'), '', array( 'maxlength' => 5000 ) );
	$Form->checkbox_input( 'site_skins_enabled', $Settings->get( 'site_skins_enabled' ), T_('Enable site skins'), array( 'note' => T_('Enables a sitewide header and footer') ) );

	$BlogCache = & get_BlogCache();

	$Form->select_input_object( 'info_blog_ID', $Settings->get( 'info_blog_ID' ), $BlogCache, T_('Blog for info pages'), array(
		'note' => '<a href="'.$admin_url.'?ctrl=collections&action=new">'.T_('Create new blog').' &raquo;</a>',
		'allow_none' => true,
		'loop_object_method' => 'get_maxlen_name' ) );

	$Form->select_input_object( 'default_blog_ID', $Settings->get('default_blog_ID'), $BlogCache, T_('Default blog to display'), array(
			'note' => T_('This blog will be displayed on index.php.').' <a href="'.$admin_url.'?ctrl=collections&action=new">'.T_('Create new blog').' &raquo;</a>',
			'allow_none' => true,
			'loop_object_method' => 'get_maxlen_name' ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Advanced Site Settings').get_manual_link('advanced-site-settings') );

	$Form->duration_input( 'reloadpage_timeout', (int)$Settings->get('reloadpage_timeout'), T_('Reload-page timeout'), 'minutes', 'seconds', array( 'minutes_step' => 1, 'required' => true ) );
	// $Form->text_input( 'reloadpage_timeout', (int)$Settings->get('reloadpage_timeout'), 5,
	// T_('Reload-page timeout'), T_('Time (in seconds) that must pass before a request to the same URI from the same IP and useragent is considered as a new hit.'), array( 'maxlength'=>5, 'required'=>true ) );

	$Form->checkbox_input( 'general_cache_enabled', $Settings->get('general_cache_enabled'), T_('Enable general cache'), array( 'note'=>T_('Cache rendered pages that are not controlled by a skin. See Blog Settings for skin output caching.') ) );

$Form->end_fieldset();

// --------------------------------------------

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>