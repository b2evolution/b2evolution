<?php
/**
 * This file implements the UI view for the user properties.
 *
 * Called by {@link b2users.php}
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

/**
 * @var User
 */
global $current_User;
/**
 * @var AdminUI_general
 */
global $AdminUI;
/**
 * @var User
 */
global $edited_User;
/**
 * @var GeneralSettings
 */
global $Settings;
/**
 * @var UserSettings
 */
global $UserSettings;
/**
 * @var Plugins
 */
global $Plugins;

global $action, $user_profile_only;

// Begin payload block:
$this->disp_payload_begin();


$Form = & new Form( NULL, 'user_checkchanges' );

if( !$user_profile_only )
{
	$Form->global_icon( T_('Compose message'), 'comments', '?ctrl=threads&action=new&user_login='.$edited_User->login );

	$Form->global_icon( ( $action != 'view' ? T_('Cancel editing!') : T_('Close user profile!') ), 'close', regenerate_url( 'user_ID,action' ) );
}

if( $edited_User->ID == 0 )
{	// Creating new user:
	$creating = true;
	$Form->begin_form( 'fform', T_('Create new user profile') );
}
else
{	// Editing existing user:
	$creating = false;
	$Form->begin_form( 'fform', T_('Profile for:').' '.$edited_User->dget('fullname').' ['.$edited_User->dget('login').']' );
}

$Form->hidden_ctrl();
$Form->hidden( 'user_ID', $edited_User->ID );
$Form->hidden( 'identity_form', '1' );
$Form->hidden( 'password_form', '1' );
$Form->hidden( 'preferences_form', '1' );

// _____________________________________________________________________

$Form->begin_fieldset( T_('User permissions'), array( 'class'=>'fieldset clear' ) );

	$edited_User->get_Group();

	if( $edited_User->ID != 1 && $current_User->check_perm( 'users', 'edit' ) )
	{	// This is not Admin and we're not restricted: we're allowed to change the user group:
		$chosengroup = ( $edited_User->Group === NULL ) ? $Settings->get('newusers_grp_ID') : $edited_User->Group->ID;
		$GroupCache = & get_GroupCache();
		$Form->select_object( 'edited_user_grp_ID', $chosengroup, $GroupCache, T_('User group') );
	}
	else
	{
		echo '<input type="hidden" name="edited_user_grp_ID" value="'.$edited_User->Group->ID.'" />';
		$Form->info( T_('User group'), $edited_User->Group->dget('name') );
	}

	$field_note = '[0 - 10] '.sprintf( T_('See <a %s>online manual</a> for details.'), 'href="http://manual.b2evolution.net/User_levels"' );
	if( $action != 'view' && $current_User->check_perm( 'users', 'edit' ) )
	{
		$Form->text_input( 'edited_user_level', $edited_User->get('level'), 2, T_('User level'), $field_note, array( 'required' => true ) );
	}
	else
	{
		$Form->info_field( T_('User level'), $edited_User->get('level'), array( 'note' => $field_note ) );
	}

$Form->end_fieldset();

// _____________________________________________________________________

$Form->begin_fieldset( T_('Email communications') );

	$email_fieldnote = '<a href="mailto:'.$edited_User->get('email').'">'.get_icon( 'email', 'imgtag', array('title'=>T_('Send an email')) ).'</a>';

  if( $action != 'view' )
	{ // We can edit the values:

		$Form->text_input( 'edited_user_email', $edited_User->email, 30, T_('Email'), $email_fieldnote, array( 'maxlength' => 100, 'required' => true ) );
		if( $current_User->check_perm( 'users', 'edit' ) )
		{ // user has "edit users" perms:
			$Form->checkbox( 'edited_user_validated', $edited_User->get('validated'), T_('Validated email'), T_('Has this email address been validated (through confirmation email)?') );
		}
		else
		{ // info only:
			$Form->info( T_('Validated email'), ( $edited_User->get('validated') ? T_('yes') : T_('no') ), T_('Has this email address been validated (through confirmation email)?') );
		}
		$Form->checkbox( 'edited_user_allow_msgform', $edited_User->get('allow_msgform'), T_('Message form'), T_('Check this to allow receiving emails through a message form.') );
		$Form->checkbox( 'edited_user_notify', $edited_User->get('notify'), T_('Notifications'), T_('Check this to receive a notification whenever someone else comments on one of <strong>your</strong> posts.') );

	}
	else
	{ // display only

		$Form->info( T_('Email'), $edited_User->get('email'), $email_fieldnote );
		$Form->info( T_('Validated email'), ( $edited_User->get('validated') ? T_('yes') : T_('no') ), T_('Has this email address been validated (through confirmation email)?') );
		$Form->info( T_('Message form'), ($edited_User->get('allow_msgform') ? T_('yes') : T_('no')) );
		$Form->info( T_('Notifications'), ($edited_User->get('notify') ? T_('yes') : T_('no')) );

  }

$Form->end_fieldset();

// _____________________________________________________________________

$Form->begin_fieldset( T_('Identity') );


  if( $action != 'view' )
	{ // We can edit the values:

		global $admin_url;
		$avatar_tag = $edited_User->get_avatar_imgtag();
		if( !empty($avatar_tag) )
		{
			$avatar_tag .= ' '.action_icon( T_('Remove'), 'delete', '?ctrl=users&amp;user_ID='.$edited_User->ID.'&amp;action=remove_avatar', T_('Remove') );
			if( $current_User->check_perm( 'files', 'view' ) )
			{
				$avatar_tag .= ' '.action_icon( T_('Change'), 'link', '?ctrl=files&amp;user_ID='.$edited_User->ID, T_('Change' ).' &raquo;', 5, 5 );
			}
		}
		elseif( $current_User->check_perm( 'files', 'view' ) )
		{
			$avatar_tag .= ' '.action_icon( T_('Upload or choose an avatar'), 'link', '?ctrl=files&amp;user_ID='.$edited_User->ID, T_('Upload/Select' ).' &raquo;', 5, 5 );
		}



		$Form->info( T_('Avatar'), $avatar_tag );

		// fp> TODO: a javascript REFRAME feature would ne neat here: selecting a square area of the img and saving it as a new avatar image

		$Form->text_input( 'edited_user_login', $edited_User->login, 20, T_('Login'), '', array( 'required' => true ) );
		$Form->text_input( 'edited_user_firstname', $edited_User->firstname, 20, T_('First name'), '', array( 'maxlength' => 50 ) );
		$Form->text_input( 'edited_user_lastname', $edited_User->lastname, 20, T_('Last name'), '', array( 'maxlength' => 50 ) );
		$Form->text_input( 'edited_user_nickname', $edited_User->nickname, 20, T_('Nickname'), '', array( 'maxlength' => 50, 'required' => true ) );
		$Form->select( 'edited_user_idmode', $edited_User->get( 'idmode' ), array( &$edited_User, 'callback_optionsForIdMode' ), T_('Identity shown') );

		$CountryCache = & get_CountryCache();
		$Form->select_input_object( 'edited_user_ctry_ID', $edited_User->ctry_ID, $CountryCache, 'Country', array('required'=>true) );

		$Form->checkbox( 'edited_user_showonline', $edited_User->get('showonline'), T_('Show online'), T_('Check this to be displayed as online when visiting the site.') );
		$Form->checkbox( 'edited_user_set_login_multiple_sessions', $UserSettings->get('login_multiple_sessions', $edited_User->ID), T_('Multiple sessions'),
			T_('Check this if you want to log in from different computers/browsers at the same time. Otherwise, logging in from a new computer/browser will disconnect you on the previous one.') );

	}
	else
	{ // display only

		$Form->info( T_('Avatar'), $edited_User->get_avatar_imgtag() );

		$Form->info( T_('Login'), $edited_User->get('login') );
		$Form->info( T_('First name'), $edited_User->get('firstname') );
		$Form->info( T_('Last name'), $edited_User->get('lastname') );
		$Form->info( T_('Nickname'), $edited_User->get('nickname') );
		$Form->info( T_('Identity shown'), $edited_User->get('preferredname') );
		$Form->info( T_('Country'), $edited_User->get_country_name() );
		$Form->info( T_('Show online'), ($edited_User->get('showonline')) ? T_('yes') : T_('no') );
		$Form->info( T_('Multiple sessions'), ($UserSettings->get('login_multiple_sessions', $edited_User->ID) ? T_('Allowed') : T_('Forbidden')) );
  }

$Form->end_fieldset();

// _____________________________________________________________________


if( $action != 'view' )
{ // We can edit the values:

	$Form->begin_fieldset( T_('Password') );
		$Form->password_input( 'edited_user_pass1', '', 20, T_('New password'), array( 'note' => ( !empty($edited_User->ID) ? T_('Leave empty if you don\'t want to change the password.') : '' ), 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off' ) );
		$Form->password_input( 'edited_user_pass2', '', 20, T_('Confirm new password'), array( 'note'=>sprintf( T_('Minimum length: %d characters.'), $Settings->get('user_minpwdlen') ), 'maxlength' => 50, 'required' => ($edited_User->ID == 0), 'autocomplete'=>'off' ) );

	$Form->end_fieldset();
}

// _____________________________________________________________________


$Form->begin_fieldset( T_('Preferences') );

	$value_admin_skin = get_param('edited_user_admin_skin');
	if( !$value_admin_skin )
	{ // no value supplied through POST/GET
		$value_admin_skin = $UserSettings->get( 'admin_skin', $edited_User->ID );
	}
	if( !$value_admin_skin )
	{ // Nothing set yet for the user, use the default
		$value_admin_skin = $Settings->get('admin_skin');
	}

	if( $action != 'view' )
	{ // We can edit the values:

		$Form->select( 'edited_user_locale', $edited_User->get('locale'), 'locale_options_return', T_('Preferred locale'), T_('Preferred locale for admin interface, notifications, etc.'));

		$Form->select_input_array( 'edited_user_admin_skin', $value_admin_skin, get_admin_skins(), T_('Admin skin'), T_('The skin defines how the backoffice appears to you.') );

	  // fp> TODO: We gotta have something like $edited_User->UserSettings->get('legend');
		// Icon/text thresholds:
		$Form->text( 'edited_user_action_icon_threshold', $UserSettings->get( 'action_icon_threshold', $edited_User->ID), 1, T_('Action icon display'), T_('1:more icons ... 5:less icons') );
		$Form->text( 'edited_user_action_word_threshold', $UserSettings->get( 'action_word_threshold', $edited_User->ID), 1, T_('Action word display'), T_('1:more action words ... 5:less action words') );

		// To display or hide icon legend:
		$Form->checkbox( 'edited_user_legend', $UserSettings->get( 'display_icon_legend', $edited_User->ID ), T_('Display icon legend'), T_('Display a legend at the bottom of every page including all action icons used on that page.') );

		// To activate or deactivate bozo validator:
		$Form->checkbox( 'edited_user_bozo', $UserSettings->get( 'control_form_abortions', $edited_User->ID ), T_('Control form closing'), T_('This will alert you if you fill in data into a form and try to leave the form before submitting the data.') );

		// To activate focus on first form input text
		$Form->checkbox( 'edited_user_focusonfirst', $UserSettings->get( 'focus_on_first_input', $edited_User->ID ), T_('Focus on first field'), T_('The focus will automatically go to the first input text field.') );

		// Number of results per page
		$Form->text( 'edited_user_results_per_page', $UserSettings->get( 'results_per_page', $edited_User->ID ), 3, T_('Results per page'), T_('Number of rows displayed in results tables.') );

	}
	else
	{ // display only

		$Form->info( T_('Preferred locale'), $edited_User->get('locale'), T_('Preferred locale for admin interface, notifications, etc.') );

		$Form->info_field( T_('Admin skin'), $value_admin_skin, array( 'note' => T_('The skin defines how the backoffice appears to you.') ) );

		// fp> TODO: a lot of things will not be displayed in view only mode. Do we want that?

		$Form->info_field( T_('Results per page'), $UserSettings->get( 'results_per_page', $edited_User->ID ), array( 'note' => T_('Number of rows displayed in results tables.') ) );
	}

$Form->end_fieldset();

// _____________________________________________________________________

if( $action != 'view' )
{ // We can edit the values:
	// PluginUserSettings
	load_funcs('plugins/_plugin.funcs.php');

	$Plugins->restart();
	while( $loop_Plugin = & $Plugins->get_next() )
	{
		if( ! $loop_Plugin->UserSettings /* NOTE: this triggers autoloading in PHP5, which is needed for the "hackish" isset($this->UserSettings)-method to see if the settings are queried for editing (required before 1.9) */
			&& ! $Plugins->has_event($loop_Plugin->ID, 'PluginSettingsEditDisplayAfter') ) // What do we care about this event for?
		{
			continue;
		}

		// We use output buffers here to display the fieldset only, if there's content in there (either from PluginUserSettings or PluginSettingsEditDisplayAfter).
		ob_start();
		$Form->begin_fieldset( $loop_Plugin->name );

		ob_start();
		// UserSettings:
		$plugin_user_settings = $loop_Plugin->GetDefaultUserSettings( $tmp_params = array('for_editing'=>true) );
		if( is_array($plugin_user_settings) )
		{
			foreach( $plugin_user_settings as $l_name => $l_meta )
			{
				// Display form field for this setting:
				autoform_display_field( $l_name, $l_meta, $Form, 'UserSettings', $loop_Plugin, $edited_User );
			}
		}

		// fp> what's a use case for this event? (I soooo want to nuke it...)
		$Plugins->call_method( $loop_Plugin->ID, 'PluginUserSettingsEditDisplayAfter',
			$tmp_params = array( 'Form' => & $Form, 'User' => $edited_User ) );

		$has_contents = strlen( ob_get_contents() );
		$Form->end_fieldset();

		if( $has_contents )
		{
			ob_end_flush();
			ob_end_flush();
		}
		else
		{ // No content, discard output buffers:
			ob_end_clean();
			ob_end_clean();
		}
	}
}

// _____________________________________________________________________


$Form->begin_fieldset( T_('Additional info') );

	if( ! $creating )
	{ // We're NOT creating a new user:
		$Form->info_field( T_('ID'), $edited_User->ID );

		$Form->info_field( T_('Posts'), $edited_User->get_num_posts() );

		$Form->info_field( T_('Created on'), $edited_User->dget('datecreated') );
		$Form->info_field( T_('From IP'), $edited_User->dget('ip') );
		$Form->info_field( T_('From Domain'), $edited_User->dget('domain') );
		$Form->info_field( T_('With Browser'), $edited_User->dget('browser') );
	}


	if( ($url = $edited_User->get('url')) != '' )
	{
		if( !preg_match('#://#', $url) )
		{
			$url = 'http://'.$url;
		}
		$url_fieldnote = '<a href="'.$url.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Visit the site')) ).'</a>';
	}
	else
		$url_fieldnote = '';

	if( $edited_User->get('icq') != 0 )
		$icq_fieldnote = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$edited_User->get('icq').'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Search on ICQ.com')) ).'</a>';
	else
		$icq_fieldnote = '';

	if( $edited_User->get('aim') != '' )
		$aim_fieldnote = '<a href="aim:goim?screenname='.$edited_User->get('aim').'&amp;message=Hello">'.get_icon( 'play', 'imgtag', array('title'=>T_('Instant Message to user')) ).'</a>';
	else
		$aim_fieldnote = '';


  if( $action != 'view' )
	{ // We can edit the values:

		$Form->text_input( 'edited_user_url', $edited_User->url, 30, T_('URL'), $url_fieldnote, array( 'maxlength' => 100 ) );
		$Form->text_input( 'edited_user_icq', $edited_User->icq, 30, T_('ICQ'), $icq_fieldnote, array( 'maxlength' => 10 ) );
		$Form->text_input( 'edited_user_aim', $edited_User->aim, 30, T_('AIM'), $aim_fieldnote, array( 'maxlength' => 50 ) );
		$Form->text_input( 'edited_user_msn', $edited_User->msn, 30, T_('MSN IM'), '', array( 'maxlength' => 100 ) );
		$Form->text_input( 'edited_user_yim', $edited_User->yim, 30, T_('YahooIM'), '', array( 'maxlength' => 50 ) );

	}
	else
	{ // display only

		$Form->info( T_('URL'), $edited_User->get('url'), $url_fieldnote );
		$Form->info( T_('ICQ'), $edited_User->get('icq', 'formvalue'), $icq_fieldnote );
		$Form->info( T_('AIM'), $edited_User->get('aim'), $aim_fieldnote );
		$Form->info( T_('MSN IM'), $edited_User->get('msn') );
		$Form->info( T_('YahooIM'), $edited_User->get('yim') );

  }

$Form->end_fieldset();


$Form->begin_fieldset( T_('Experimental') );

	// This totally needs to move into User object
	global $DB;

	// Get existing userfields:
	$userfields = $DB->get_results( '
		SELECT uf_ID, ufdf_ID, ufdf_type, ufdf_name, uf_varchar
			FROM T_users__fields LEFT JOIN T_users__fielddefs ON uf_ufdf_ID = ufdf_ID
		 WHERE uf_user_ID = '.$edited_User->ID.'
		 ORDER BY uf_ID' );

	foreach( $userfields as $userfield )
	{
		switch( $userfield->ufdf_ID )
		{
			case 10200:
				$field_note = '<a href="aim:goim?screenname='.$userfield->uf_varchar.'&amp;message=Hello">'.get_icon( 'play', 'imgtag', array('title'=>T_('Instant Message to user')) ).'</a>';
				break;

			case 10300:
				$field_note = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$userfield->uf_varchar.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Search on ICQ.com')) ).'</a>';
				break;

			default:
				if( $userfield->ufdf_ID >= 100000 && $userfield->ufdf_ID < 200000 )
				{
					$url = $userfield->uf_varchar;
					if( !preg_match('#://#', $url) )
					{
						$url = 'http://'.$url;
					}
					$field_note = '<a href="'.$url.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Visit the site')) ).'</a>';
				}
				else
				{
					$field_note = '';
				}
		}

		$uf_val = param( 'uf_'.$userfield->uf_ID, 'string', NULL );
		if( is_null( $uf_val ) )
		{	// No value submitted yet, get DB val:
			$uf_val = $userfield->uf_varchar;
		}

		// Display existing field:
		$Form->text_input( 'uf_'.$userfield->uf_ID, $uf_val, 50, $userfield->ufdf_name, $field_note, array( 'maxlength' => 255 ) );
	}

	// Get list of possible field types:
	// TODO: use userfield manipulation functions
	$userfielddefs = $DB->get_results( '
		SELECT ufdf_ID, ufdf_type, ufdf_name
			FROM T_users__fielddefs
		 ORDER BY ufdf_ID' );
	// New fields:
	// TODO: JS for adding more than 3 at a time.
	for( $i=1; $i<=3; $i++ )
	{
		$label = '<select name="new_uf_type_'.$i.'"><option value="">Add field...</option><optgroup label="Instant Messaging">';
		foreach( $userfielddefs as $fielddef )
		{
			// check for group header:
			switch( $fielddef->ufdf_ID )
			{
				case 50000:
					$label .= "\n".'</optgroup><optgroup label="Phone">';
					break;
				case 100000:
					$label .= "\n".'</optgroup><optgroup label="Web">';
					break;
				case 200000:
					$label .= "\n".'</optgroup><optgroup label="Organization">';
					break;
				case 300000:
					$label .= "\n".'</optgroup><optgroup label="Address">';
					break;
			}
			$label .= "\n".'<option value="'.$fielddef->ufdf_ID.'"';
			if( param( 'new_uf_type_'.$i, 'string', '' ) == $fielddef->ufdf_ID )
			{	// We had selected this type before getting an error:
				$label .= ' selected="selected"';
			}
			$label .= '>'.$fielddef->ufdf_name.'</option>';
		}
		$label .= '</optgroup></select>';

		$Form->text_input( 'new_uf_val_'.$i, param( 'new_uf_val_'.$i, 'string', '' ), 50, $label, '', array('maxlength' => 255, 'clickable_label'=>false) );
	}

$Form->end_fieldset();

// _____________________________________________________________________

if( $action != 'view' )
{ // Edit buttons
	$Form->buttons( array(
		array( '', 'actionArray[update]', T_('Save !'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ),
		// dh> TODO: Non-Javascript-confirm before trashing all settings with a misplaced click.
		array( 'type' => 'submit', 'name' => 'actionArray[default_settings]', 'value' => T_('Restore defaults'), 'class' => 'ResetButton',
			'onclick' => "return confirm('".TS_('This will reset all your user settings.').'\n'.TS_('This cannot be undone.').'\n'.TS_('Are you sure?')."');" ),
	) );
}


$Form->end_form();

// End payload block:
$this->disp_payload_end();


/*
 * $Log$
 * Revision 1.25  2009/10/25 15:22:47  efy-maxim
 * user - identity, password, preferences tabs
 *
 * Revision 1.24  2009/10/10 21:43:09  tblue246
 * cleanup
 *
 * Revision 1.23  2009/10/10 21:34:44  tblue246
 * Bugfix: Do not "allow_none" for country selection on user form.
 *
 * Revision 1.22  2009/09/26 12:00:44  tblue246
 * Minor/coding style
 *
 * Revision 1.21  2009/09/25 07:33:15  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.20  2009/09/23 19:23:10  efy-bogdan
 * Cleanup users.ctrl.php
 *
 * Revision 1.19  2009/09/19 20:50:57  fplanque
 * added action icons/links
 *
 * Revision 1.18  2009/09/19 01:04:06  fplanque
 * button to remove an avatar from an user profile
 *
 * Revision 1.17  2009/09/13 12:27:28  tblue246
 * Only display link to change to the avatar if user has appropriate permissions
 *
 * Revision 1.16  2009/09/10 13:51:38  tblue246
 * Do not show form field for user level if action == view.
 *
 * Revision 1.15  2009/09/07 23:35:49  fplanque
 * cleanup
 *
 * Revision 1.14  2009/09/07 14:26:49  efy-maxim
 * Country field has been added to User form (but without updater)
 *
 * Revision 1.13  2009/08/30 00:42:11  fplanque
 * fixed user form
 *
 * Revision 1.12  2009/05/26 19:32:00  fplanque
 * Plugins can now have Settings that are specific to each blog.
 *
 * Revision 1.11  2009/03/20 03:38:04  fplanque
 * rollback -- http://forums.b2evolution.net/viewtopic.php?t=18269
 *
 * Revision 1.8  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.7  2009/02/28 23:51:59  blueyed
 * Add autocomplete=off to password fields in user profile, so that FF3 does not prefill the first one (only).
 *
 * Revision 1.6  2009/01/13 23:45:59  fplanque
 * User fields proof of concept
 *
 * Revision 1.5  2008/09/29 15:36:20  blueyed
 * Fix display of user avatar in view-only profile
 *
 * Revision 1.4  2008/09/29 08:30:40  fplanque
 * Avatar support
 */
?>
