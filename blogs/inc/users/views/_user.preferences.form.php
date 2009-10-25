<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
/**
 * @var instance of UserSettings class
 */
global $UserSettings;
/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var current action
 */
global $action;
/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;
/**
 * @var Plugins
 */
global $Plugins;


// Begin payload block:
$this->disp_payload_begin();

$Form = & new Form( NULL, 'user_checkchanges' );

if( !$user_profile_only )
{
	$Form->global_icon( T_('Compose message'), 'comments', '?ctrl=threads&action=new&user_login='.$edited_User->login );
	$Form->global_icon( ( $action != 'view' ? T_('Cancel editing!') : T_('Close user profile!') ), 'close', regenerate_url( 'user_ID,action' ) );
}

$Form->begin_form( 'fform', sprintf( T_('Edit %s preferences'), $edited_User->dget('fullname').' ['.$edited_User->dget('login').']' ) );

$Form->hidden_ctrl();
$Form->hidden( 'tab', 'preferences' );
$Form->hidden( 'preferences_form', '1' );

$Form->hidden( 'user_ID', $edited_User->ID );
$Form->hidden( 'edited_user_login', $edited_User->login );

	/***************  Preferences  **************/

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

	/***************  Plugins  **************/

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

	/***************  Buttons  **************/

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
 * Revision 1.1  2009/10/25 15:22:48  efy-maxim
 * user - identity, password, preferences tabs
 *
 */
?>