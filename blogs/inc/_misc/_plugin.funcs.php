<?php
/**
 * Functions for Plugin handling.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link https://thequod.de/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Recursive helper function to display a field of the plugin's settings.
 *
 * This gets used for PluginSettings ("Edit plugin") and PluginUserSettings ("Edit user settings").
 *
 * @param string Settings name (key)
 * @param array Meta data for this setting. See {@link Plugin::GetDefaultSettings()}
 * @param Plugin (by reference)
 * @param Form (by reference)
 * @param string Settings type ('Settings' or 'UserSettings')
 * @param mixed Target (User object for 'UserSettings')
 * @param mixed Value to really use (used for recursion into array type settings)
 */
function display_settings_fieldset_field( $set_name, $set_meta, & $Plugin, & $Form, $set_type = 'Settings', $set_target = NULL, $use_value = NULL )
{
	global $debug, $Request;

	$params = array();

	if( isset($set_meta['note']) )
	{
		$params['note'] = $set_meta['note'];
	}

	if( ! isset($set_meta['type']) )
	{
		$set_meta['type'] = 'text';
	}

	if( strpos($set_meta['type'], 'select_') === 0 )
	{ // 'allow_none' setting for select_* types
		if( isset($set_meta['allow_none']) )
		{
			$params['allow_none'] = $set_meta['allow_none'];
		}
	}

	$help_icon = NULL;
	if( isset($set_meta['help']) )
	{
		if( $set_meta['help'] === true )
		{ // link to $set_name-target:
			$help_target = '#'.preg_replace( array('~\]?\[\d+\]\[~', '~\]$~'), array('_',''), $set_name );
		}
		else
		{
			$help_target = $set_meta['help'];
		}
		$help_icon = get_plugin_help_link( $Plugin, $help_target );
	}

	$set_label = isset($set_meta['label']) ? $set_meta['label'] : '';


	// "Layout" settings:
	if( isset($set_meta['layout']) )
	{
		switch( $set_meta['layout'] )
		{
			case 'begin_fieldset':
				$fieldset_title = $set_label;
				if( isset($help_icon) )
				{
					$Form->begin_fieldset( $fieldset_title, array(), array($help_icon) );
				}
				else
				{
					$Form->begin_fieldset( $fieldset_title );
				}
				break;

			case 'end_fieldset':
				$Form->end_fieldset();
				break;

			case 'separator':
				echo '<hr />';
				break;
		}
		return;
	}

	// Append help icon to note:
	if( isset($params['note']) )
	{
		$params['note'] .= ' '.$help_icon;
	}
	else
	{
		$params['note'] = $help_icon;
	}


	if( ( $set_value = $Request->get('edit_plugin_'.$Plugin->ID.'_set_'.$set_name) ) !== NULL )
	{ // use value provided with Request!
	}
	elseif( isset($use_value) )
	{
		$set_value = $use_value;
	}
	else
	{
		if( $set_type == 'UserSettings' )
		{
			$set_value = $Plugin->UserSettings->get( $set_name, $set_target->ID );
			$error_value = $Plugin->PluginUserSettingsValidateSet( $tmp_params = array(
				'name' => $set_name, 'value' => & $set_value, 'meta' => $set_meta, 'User' => $set_target ) );
		}
		else
		{
			$val_method = 'Plugin'.$set_type.'ValidateSet';
			$set_value = $Plugin->$set_type->get( $set_name );
			$error_value = $Plugin->$val_method( $tmp_params = array( 'name' => $set_name, 'value' => & $set_value, 'meta' => $set_meta ) );
		}

		if( $error_value )
		{ // add error
			$Request->param_error( 'edit_plugin_'.$Plugin->ID.'_set_'.$set_name, NULL, $error_value ); // only add the error to the field
		}
	}

	// Display input element:
	switch( $set_meta['type'] )
	{
		case 'checkbox':
			$Form->checkbox_input( 'edit_plugin_'.$Plugin->ID.'_set_'.$set_name, $set_value, $set_label, $params );
			break;

		case 'textarea':
			$textarea_rows = isset($set_meta['rows']) ? $set_meta['rows'] : 3;
			$Form->textarea_input( 'edit_plugin_'.$Plugin->ID.'_set_'.$set_name, $set_value, $textarea_rows, $set_label, $params );
			break;

		case 'select':
			$params['value'] = $set_value;
			$Form->select_input_array( 'edit_plugin_'.$Plugin->ID.'_set_'.$set_name, $set_meta['options'], $set_label, $params );
			break;

		case 'select_group':
			global $GroupCache;
			$Form->select_input_object( 'edit_plugin_'.$Plugin->ID.'_set_'.$set_name, $set_value, $GroupCache, $set_label, $params );
			break;

		case 'select_user':
			global $UserCache;
			$UserCache->load_all();
			if( ! isset($params['loop_object_method']) )
			{
				$params['loop_object_method'] = 'get_preferred_name';
			}
			$Form->select_input_object( 'edit_plugin_'.$Plugin->ID.'_set_'.$set_name, $set_value, $UserCache, $set_label, $params );
			break;

		case 'array':
			$fieldset_title = $set_label;
			if( $debug )
			{
				$fieldset_title .= ' [debug: '.$set_name.']';
			}
			$Form->begin_fieldset( $fieldset_title );

			if( ! empty($params['note']) )
			{
				echo '<p class="notes">'.$params['note'].'</p>';
			}

			$insert_new_set_as = 0;
			if( is_array( $set_value ) )
			{
				foreach( $set_value as $k => $v )
				{
					$fieldset_icons = array();
					if( ! isset($set_meta['min_count']) || count($set_value) > $set_meta['min_count'] )
					{ // provide icon to remove this set
						$fieldset_icons[] = action_icon( T_('Delete set!'), 'delete', regenerate_url( 'action', array('action=delete_settings_set', 'set_path='.$set_name.'['.$insert_new_set_as.']', 'plugin_ID='.$Plugin->ID) ) );
					}
					$Form->begin_fieldset( '#'.$k, array(), $fieldset_icons );

					foreach( $set_meta['entries'] as $l_set_name => $l_set_entry )
					{
						$l_value = isset($set_value[$k][$l_set_name]) ? $set_value[$k][$l_set_name] : NULL;
						display_settings_fieldset_field( $set_name.'['.$k.']['.$l_set_name.']', $l_set_entry, $Plugin, $Form, $set_type, $set_target, $l_value );
					}
					$insert_new_set_as = $k+1;
					$Form->end_fieldset();
				}
			}
			if( ! isset( $set_meta['max_number'] ) || $set_meta['max_number'] > count($set_value) )
			{ // no max_number defined or not reached: display link to add a new set
				echo action_icon( sprintf( T_('Add a new set of &laquo;%s&raquo;'), $set_label), 'new', regenerate_url( 'action', array('action=add_settings_set', 'set_path='.$set_name.'['.$insert_new_set_as.']', 'plugin_ID='.$Plugin->ID) ), T_('New set') );
			}
			$Form->end_fieldset();

			break;

		case 'password':
			$params['type'] = 'password'; // same as text input, but type=password

		case 'integer':
		case 'text':
			// Default: "text input"
			if( isset($set_meta['size']) )
			{
				$size = (int)$set_meta['size'];
			}
			else
			{ // Default size:
				$size = 15;
			}
			if( isset($set_meta['maxlength']) )
			{
				$params['maxlength'] = (int)$set_meta['maxlength'];
			}
			else
			{ // do not use size as maxlength, if not given!
				$params['maxlength'] = '';
			}

			$Form->text_input( 'edit_plugin_'.$Plugin->ID.'_set_'.$set_name, $set_value, $size, $set_label, $params );
			break;

		default:
			debug_die( 'Unsupported type ['.$set_meta['type'].'] from GetDefaultSettings()!' );
	}
}



/**
 * Set Plugin settings from params.
 *
 * This gets used when saving a user profile (PluginUserSettings) or plugin settings (PluginSettings).
 *
 * @param Plugin
 * @param Plugins An object derived from Plugins, probably either {@link $Plugins} or {@link $Plugins_admin}.
 * @param string Type of Settings (either 'Settings' or 'UserSettings').
 * @param mixed Target (User object for 'UserSettings')
 */
function set_Settings_for_Plugin_from_Request( & $Plugin, & $use_Plugins, $set_type, $set_target = NULL )
{
	global $Request, $Messages;

	$method = 'GetDefault'.$set_type;

	foreach( $Plugin->$method() as $l_name => $l_meta )
	{
		if( isset($l_meta['layout']) )
		{ // a layout "setting"
			continue;
		}

		$l_param_default = '';
		if( isset($l_meta['type']) && $l_meta['type'] == 'array' )
		{ // this settings has a type
			$l_param_type = $l_meta['type'];
		}
		elseif( isset($l_meta['type']) && $l_meta['type'] == 'checkbox' )
		{
			$l_param_type = 'integer';
			$l_param_default = 0;
		}
		else
		{
			$l_param_type = 'string';
		}
		$l_value = $Request->param( 'edit_plugin_'.$Plugin->ID.'_set_'.$l_name, $l_param_type, $l_param_default );

		if( isset($l_meta['type']) && $l_meta['type'] == 'integer' && ! preg_match( '~^\d+$~', $l_value ) )
		{
			$Request->param_error( 'edit_plugin_'.$Plugin->ID.'_set_'.$l_name, sprintf( T_('The value for %s must be numeric.'), $l_name ), T_('The value must be numeric.') );
			continue;
		}

		// Check valid pattern:
		if( isset($l_meta['valid_pattern']) )
		{
			$param_pattern = is_array($l_meta['valid_pattern']) ? $l_meta['valid_pattern']['pattern'] : $l_meta['valid_pattern'];
			if( ! preg_match( $param_pattern, $l_value ) )
			{
				$param_error = is_array($l_meta['valid_pattern']) ? $l_meta['valid_pattern']['error'] : sprintf(T_('The value is invalid. It must match the regular expression &laquo;%s&raquo;.'), $param_pattern);
				$Request->param_error( 'edit_plugin_'.$Plugin->ID.'_set_'.$l_name, $param_error );
				continue;
			}
		}

		// Check valid range:
		if( isset($l_meta['valid_range']) )
		{
			if( (isset($l_meta['valid_range']['min']) && $l_value < $l_meta['valid_range']['min'])
			    || (isset($l_meta['valid_range']['max']) && $l_value > $l_meta['valid_range']['max']) )
			{
				if( isset($l_meta['valid_range']['error']) )
				{
					$param_error = $l_meta['valid_range']['error'];
				}
				else
				{
					if( isset($l_meta['valid_range']['min']) && isset($l_meta['valid_range']['max']) )
					{
						$param_error = sprintf(T_('The value is invalid. It must be in the range from %s to %s.'), $l_meta['valid_range']['min'], $l_meta['valid_range']['max']);
					}
					elseif( isset($l_meta['valid_range']['max']) )
					{
						$param_error = sprintf(T_('The value is invalid. It must be smaller than %s.'), $l_meta['valid_range']['max']);
					}
					else
					{
						$param_error = sprintf(T_('The value is invalid. It must be greater than %s.'), $l_meta['valid_range']['min']);
					}
				}

				$Request->param_error( 'edit_plugin_'.$Plugin->ID.'_set_'.$l_name, $param_error );
				continue;
			}
		}

		// Ask the plugin if it's ok (through PluginSettingsValidateSet() / PluginUserSettingsValidateSet()):
		$tmp_params = array( 'name' => $l_name, 'value' => & $l_value, 'meta' => $l_meta );
		if( $set_type == 'UserSettings' )
		{
			global $current_User;
			$tmp_params['User'] = $set_target;
		}
		if( $error = $use_Plugins->call_method( $Plugin->ID, 'Plugin'.$set_type.'ValidateSet', $tmp_params ) )
		{ // skip this
			$Request->param_error( 'edit_plugin_'.$Plugin->ID.'_set_'.$l_name, $error );
			continue;
		}

		// Set the setting:
		if( $set_type == 'UserSettings' )
		{
			$Plugin->UserSettings->set( $l_name, $l_value, $set_target->ID );
		}
		else
		{
			$Plugin->Settings->set( $l_name, $l_value );
		}
	}

}


/**
 * Get a link to a help page (with icon).
 *
 * @param Plugin
 * @param string Target; one of the following:
 *         - anchor to {@link $help_url} ("#anchor")
 *         - absolute link to some URL, e.g. "http://example.com/example.php"
 *         - empty for {@link $help_url}, then also the "www" icon gets used
 * @return string The html A tag, linking to the help.
 */
function get_plugin_help_link( $Plugin, $target = '' )
{
	$title = '';
	$icon = 'help';
	$word = '';
	$link_attribs = array( 'target' => 'help_plugin_'.$Plugin->ID ); // TODO: use JS popup instead?

	if( empty($target) )
	{
		$url = ! empty( $Plugin->help_url ) ? $Plugin->help_url : 'http://manual.b2evolution.net/Plugins/'.$Plugin->classname;
		$title = T_('Homepage of the plugin');
		$icon = 'www';
	}
	elseif( substr($target, 0, 1) == '#' )
	{ // anchor
		$help_url = ! empty( $Plugin->help_url ) ? $Plugin->help_url : 'http://manual.b2evolution.net/Plugins/'.$Plugin->classname;
		$url = $help_url.$target;
	}
	elseif( preg_match( '~^https?://~', $target ) )
	{ // absolute URL (strict match to allow other formats eventually later)
		$url = $target;
	}

	return action_icon( $title, $icon, $url, $word, $link_attribs );
}


/**
* Display a link to open the Plugin's README.html file in a JS popup, if available.
 *
 * @param Plugin
 * @param string Word to be used after action icon
 * @return string Either the HTML A-tag or empty, if no README.html available
 */
function get_plugin_README_link( $Plugin, $word = '' )
{
	if( ! get_plugin_help_file($Plugin) )
	{
		return '';
	}

	global $admin_url;

	return action_icon( T_('Local documentation of the plugin'), 'help',
			url_add_param( $admin_url, 'ctrl=plugins&amp;action=disp_help_plain&amp;plugin_ID='.$Plugin->ID ),
			$word, array( 'use_js_popup'=>true, 'id'=>'anchor_help_plugin_'.$Plugin->ID ) );
}


/**
 * Get the help file for a Plugin ID. README.LOCALE.html will take
 * precedence above the general (english) README.html.
 *
 * @todo Handle encoding of files (to $io_charset)
 *
 * @return false|string
 */
function get_plugin_help_file( $Plugin )
{
	global $default_locale, $plugins_path, $current_User;

	if( ! $current_User->check_perm( 'options', 'view', false ) )
	{ // README gets displayed through plugins controller, which requires these perms
		// TODO: Catch "disp_help" and "disp_help_plain" messages in plugins.php before general perms check!?
		return false;
	}

	// Get the language. We use $default_locale because it does not have to be activated ($current_locale)
	$lang = substr( $default_locale, 0, 2 );

	$help_dir = $plugins_path.$Plugin->classname.'/';

	// Try help for the user's locale:
	$help_file = $help_dir.'README.'.$lang.'.html';

	if( ! file_exists($help_file) )
	{ // Fallback: README.html
		$help_file = $help_dir.'README.html';

		if( ! file_exists($help_file) )
		{
			return false;
		}
	}

	return $help_file;
}


/* {{{ Revision log:
 * $Log$
 * Revision 1.11  2006/04/05 19:44:00  blueyed
 * Do not display README-link, if user has no "view-options" perms.
 *
 * Revision 1.10  2006/04/05 19:16:35  blueyed
 * Refactored/cleaned up help link handling: defaults to online-manual-pages now.
 *
 * Revision 1.7  2006/04/04 22:12:34  blueyed
 * Fixed setting usersettings for other users
 *
 * Revision 1.6  2006/03/19 00:11:57  blueyed
 * help icon for fieldsets
 *
 * Revision 1.5  2006/03/18 19:39:19  blueyed
 * Fixes for pluginsettings; added "valid_range"
 *
 * Revision 1.4  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.3  2006/03/11 01:59:00  blueyed
 * Added Plugin::forget_events()
 *
 * Revision 1.2  2006/03/01 01:07:43  blueyed
 * Plugin(s) polishing
 *
 * Revision 1.1  2006/02/27 16:57:12  blueyed
 * PluginUserSettings - allows a plugin to store user related settings
 *
 * }}}
 */
?>