<?php
/**
 * Form to edit settings of a plugin.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link https://thequod.de/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * In addition, as a special exception, the copyright holders give permission to link
 * the code of this program with the PHP/SWF Charts library by maani.us (or with
 * modified versions of this library that use the same license as PHP/SWF Charts library
 * by maani.us), and distribute linked combinations including the two. You must obey the
 * GNU General Public License in all respects for all of the code used other than the
 * PHP/SWF Charts library by maani.us. If you modify this file, you may extend this
 * exception to your version of the file, but you are not obligated to do so. If you do
 * not wish to do so, delete this exception statement from your version.
 * }}
 *
 * {@internal
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

/**
 * @global Plugin
 */
global $edit_Plugin;

/**
 * @global Plugins_admin
 */
global $admin_Plugins;

global $edited_plugin_priority, $edited_plugin_code, $edited_plugin_apply_rendering, $admin_url;

/**
 * @global string Contents of the Plugin's help file, if any. We search there for matching IDs/anchors to display links to them.
 */
$plugin_help_contents = '';

if( $help_file = $edit_Plugin->get_help_file() )
{
	$plugin_help_contents = implode( '', file($help_file) );
}


$Form = & new Form( NULL, 'pluginsettings_checkchanges' );
$Form->hidden_ctrl();

// Help icons, if available:
if( ! empty( $edit_Plugin->help_url ) )
{
	$Form->global_icon( T_('Homepage of the plugin'), 'www', $edit_Plugin->help_url );
}
if( $edit_Plugin->get_help_file() )
{
	$Form->global_icon( T_('Local documentation of the plugin'), 'help', url_add_param( $admin_url, 'ctrl=plugins&amp;action=disp_help&amp;plugin_ID='.$edit_Plugin->ID ) );
}

$Form->global_icon( T_('Cancel edit!'), 'close', regenerate_url() );

$Form->begin_form( 'fform' );
$Form->hidden( 'plugin_ID', $edit_Plugin->ID );

// PluginSettings
if( $edit_Plugin->Settings )
{
	$Form->begin_fieldset( T_('Plugin settings'), array( 'class' => 'clear' ) );

	foreach( $edit_Plugin->GetDefaultSettings() as $l_name => $l_meta )
	{
		display_settings_fieldset_field( $l_name, $l_meta, $edit_Plugin, $Form );
	}

	$admin_Plugins->call_method_if_active( $edit_Plugin->ID, 'PluginSettingsEditDisplayAfter', $params = array() );

	$Form->end_fieldset();
}

// Plugin variables
$Form->begin_fieldset( T_('Plugin variables').' ('.T_('Advanced').')', array( 'class' => 'clear' ) );
$Form->text_input( 'edited_plugin_code', $edited_plugin_code, 15, T_('Code'), array('maxlength'=>32, 'note'=>'The code to call the plugin by code. This is also used to link renderer plugins to items.') );
$Form->text_input( 'edited_plugin_priority', $edited_plugin_priority, 4, T_('Priority'), array( 'maxlength' => 4 ) );
$Form->select_input_array( 'edited_plugin_apply_rendering', $admin_Plugins->get_apply_rendering_values(), T_('Apply rendering'), array(
	'value' => $edited_plugin_apply_rendering,
	'note' => empty( $edited_plugin_code )
		? T_('Note: The plugin code is empty, so this plugin will not work as an "opt-out", "opt-in" or "lazy" renderer.')
		: NULL )
	);
$Form->end_fieldset();


// (De-)Activate Events (Advanced)
$Form->begin_fieldset( T_('Plugin events').' ('.T_('Advanced')
	.') <img src="'.get_icon('expand', 'url').'" id="clickimg_pluginevents" />', array('legend_params' => array( 'onclick' => 'toggle_clickopen(\'pluginevents\')') ) );
?>

<div id="clickdiv_pluginevents">

<?php
$enabled_events = $admin_Plugins->get_enabled_events( $edit_Plugin->ID );
$supported_events = $admin_Plugins->get_supported_events();
$registered_events = $admin_Plugins->get_registered_events( $edit_Plugin );
$count = 0;
foreach( array_keys($supported_events) as $l_event )
{
	if( ! in_array( $l_event, $registered_events ) )
	{
		continue;
	}
	if( in_array( $l_event, $admin_Plugins->_supported_private_events ) )
	{
		continue;
	}
	$Form->hidden( 'edited_plugin_displayed_events[]', $l_event ); // to consider only displayed ones on update
	$Form->checkbox_input( 'edited_plugin_events['.$l_event.']', in_array( $l_event, $enabled_events ), $l_event, array( 'note' => $supported_events[$l_event] ) );
	$count++;
}
if( ! $count )
{
	echo T_( 'This plugin has no registered events.' );
}
?>

</div>

<?php
$Form->end_fieldset();
?>

<script type="text/javascript">
	<!--
	toggle_clickopen('pluginevents');
	// -->
</script>

<?php
if( $current_User->check_perm( 'options', 'edit', false ) )
{
	$Form->buttons_input( array(
		array( 'type' => 'submit', 'name' => 'actionArray[update_settings]', 'value' => T_('Save !'), 'class' => 'SaveButton' ),
		array( 'type' => 'submit', 'name' => 'actionArray[update_settings][review]', 'value' => T_('Save (and review)'), 'class' => 'SaveButton' ),
		array( 'type' => 'reset', 'value' => T_('Reset'), 'class' => 'ResetButton' ),
		array( 'type' => 'submit', 'name' => 'actionArray[default_settings]', 'value' => T_('Restore defaults'), 'class' => 'SaveButton' ),
		) );
}
$Form->end_form();


/**
 * Recursive helper function to display a field of the plugin's settings.
 *
 * @param string Settings name (key)
 * @param array Meta data for this setting. See {@link Plugin::GetDefaultSettings()}
 * @param Plugin (by reference)
 * @param Form (by reference)
 * @param mixed Value to really use (used for recursion into array type settings)
 */
function display_settings_fieldset_field( $set_name, $set_meta, & $Plugin, & $Form, $use_value = NULL )
{
	global $debug, $plugin_help_contents, $admin_Plugins, $Request;

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

	if( isset($set_meta['help']) )
	{
		if( is_string($set_meta['help']) )
		{
			$get_help_icon_params = array($set_meta['help']);
		}
		else
		{
			$get_help_icon_params = $set_meta['help'];
		}

		$params['note'] .= ' '.call_user_func_array( array( & $Plugin, 'get_help_icon'), $get_help_icon_params );
	}
	elseif( ! empty($plugin_help_contents) )
	{ // Autolink to internal help, if a matching HTML ID is in there
		// Generate HTML ID, removing array syntax 'foobar[0][foo][0][bar]' becomes 'foobar_foo_bar'
		$help_anchor = $Plugin->classname.'_'.preg_replace( array('~\]?\[\d+\]\[~', '~\]$~'), array('_',''), $set_name );
		if( strpos($plugin_help_contents, 'id="'.$help_anchor.'"') )
		{ // there's an ID for this setting in the help file
			$params['note'] .= ' '.call_user_func_array( array( & $Plugin, 'get_help_icon'), array($set_name) );
		}
	}

	$set_label = isset($set_meta['label']) ? $set_meta['label'] : '';


	// "Layout" settings:
	if( isset($set_meta['layout']) )
	{
		switch( $set_meta['layout'] )
		{
			case 'begin_fieldset':
				$fieldset_title = $set_label;
				if( $debug )
				{
					$fieldset_title .= ' [debug: '.$set_name.']';
				}
				$Form->begin_fieldset( $fieldset_title );
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


	if( isset($use_value) )
	{
		$set_value = $use_value;
	}
	else
	{
		$set_value = $Plugin->Settings->get( $set_name );

		if( $error_value = $admin_Plugins->call_method( $Plugin->ID, 'PluginSettingsValidateSet', $tmp_params = array( 'name' => $set_name, 'value' => & $set_value, 'meta' => $set_meta ) ) );
		{ // add error
			$Request->param_error( 'edited_plugin_set_'.$set_name, NULL, $error_value ); // only add the error to the field
		}

	}

	// Display input element:
	switch( $set_meta['type'] )
	{
		case 'checkbox':
			$Form->checkbox_input( 'edited_plugin_set_'.$set_name, $set_value, $set_label, $params );
			break;

		case 'textarea':
			$textarea_rows = isset($set_meta['rows']) ? $set_meta['rows'] : 3;
			$Form->textarea_input( 'edited_plugin_set_'.$set_name, $set_value, $textarea_rows, $set_label, $params );
			break;

		case 'select':
			$params['value'] = $set_value;
			$Form->select_input_array( 'edited_plugin_set_'.$set_name, $set_meta['options'], $set_label, $params );
			break;

		case 'select_group':
			global $GroupCache;
			$Form->select_input_object( 'edited_plugin_set_'.$set_name, $set_value, $GroupCache, $set_label, $params );
			break;

		case 'select_user':
			global $UserCache;
			$UserCache->load_all();
			if( ! isset($params['loop_object_method']) )
			{
				$params['loop_object_method'] = 'get_preferred_name';
			}
			$Form->select_input_object( 'edited_plugin_set_'.$set_name, $set_value, $UserCache, $set_label, $params );
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

						display_settings_fieldset_field( $set_name.'['.$k.']['.$l_set_name.']', $l_set_entry, $Plugin, $Form, $l_value );
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

			$Form->text_input( 'edited_plugin_set_'.$set_name, $set_value, $size, $set_label, $params );
			break;

		default:
			debug_die( 'Unsupported type ['.$set_meta['type'].'] from GetDefaultSettings()!' );
	}
}


/* {{{ Revision log:
 * $Log$
 * Revision 1.2  2006/02/24 23:38:55  blueyed
 * fixes
 *
 * Revision 1.1  2006/02/24 23:02:16  blueyed
 * Added _set_plugins_editsettings.form VIEW
 *
 * }}}
 */
?>