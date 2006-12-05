<?php
/**
 * Functions for Plugin handling.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * Recursive helper function to display a field of the plugin's settings (by manipulating a Form).
 *
 * This gets used for PluginSettings ("Edit plugin") and PluginUserSettings ("Edit user settings").
 *
 * @todo dh> Allow to move setting sets up and down (order). Control goes into /inc/CONTROL/settings/plugins.php.
 *
 * @param string Settings path, e.g. 'locales[0]' or 'setting'
 * @param array Meta data for this setting.
 * @param Plugin (by reference)
 * @param Form (by reference)
 * @param string Settings type ('Settings' or 'UserSettings')
 * @param mixed Target (User object for 'UserSettings')
 * @param mixed Value to really use (used for recursion into array type settings)
 */
function display_plugin_settings_fieldset_field( $set_name, $set_meta, & $Plugin, & $Form, $set_type, $set_target = NULL, $use_value = NULL )
{
	global $debug;
	global $htsrv_url;
	static $has_array_type;

	if( ! empty($set_meta['no_edit']) )
	{ // this setting is not editable
		return;
	}

	$params = array();

	if( $use_value === NULL )
	{ // outermost level
		$has_array_type = false; // for adding a note about JS
		$outer_most = true;
	}
	else
	{
		$outer_most = false;
	}

	// Passthrough some attributes to elements:
	foreach( $set_meta as $k => $v )
	{
		if( in_array( $k, array( 'id', 'onchange', 'onclick', 'onfocus', 'onkeyup', 'onkeydown', 'onreset', 'onselect', 'cols' ) ) )
		{
			$params[$k] = $v;
		}
	}

	if( isset($set_meta['note']) )
	{
		$params['note'] = $set_meta['note'];
	}

	if( ! isset($set_meta['type']) ||  $set_meta['type'] == 'html_input' )
	{
		$set_meta['type'] = 'text';
	}

	if( $set_meta['type'] == 'html_textarea' )
	{
		$set_meta['type'] = 'textarea';
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
		$help_icon = $Plugin->get_help_link( $help_target );
	}

	$set_label = isset($set_meta['label']) ? $set_meta['label'] : '';

	if( ! empty($set_meta['disabled']) )
	{
		$params['disabled'] = 'disabled';
	}


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

	if( ! empty($help_icon) )
	{ // Append help icon to note:
		if( empty($params['note']) )
		{
			$params['note'] = $help_icon;
		}
		else
		{
			$params['note'] .= ' '.$help_icon;
		}
	}


	if( isset($use_value) )
	{
		$set_value = $use_value;
	}
	elseif( ( $set_value = get_param('edit_plugin_'.$Plugin->ID.'_set_'.$set_name) ) !== NULL )
	{ // use value provided with Request!
		if( is_array($set_value) )
		{
			handle_array_keys_in_plugin_settings($set_value);
		}
	}
	else
	{
		if( $set_type == 'UserSettings' )
		{ // NOTE: this assumes we come here only on recursion or with $use_value set..!
			$set_value = $Plugin->UserSettings->get( $set_name, $set_target->ID );
			$error_value = $Plugin->PluginUserSettingsValidateSet( $tmp_params = array(
				'name' => $set_name,
				'value' => & $set_value,
				'meta' => $set_meta,
				'User' => $set_target,
				'action' => 'display' ) );
		}
		else
		{ // NOTE: this assumes we come here only on recursion or with $use_value set..!
			$set_value = $Plugin->$set_type->get( $set_name );
			$error_value = $Plugin->PluginSettingsValidateSet( $tmp_params = array(
				'name' => $set_name,
				'value' => & $set_value,
				'meta' => $set_meta,
				'action' => 'display' ) );
		}

		if( $error_value )
		{ // add error
			param_error( 'edit_plugin_'.$Plugin->ID.'_set_'.$set_name, NULL, $error_value ); // only add the error to the field
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

		case 'select_blog':
			$BlogCache = & get_Cache( 'BlogCache' );
			$Form->select_input_object( 'edit_plugin_'.$Plugin->ID.'_set_'.$set_name, $set_value, $BlogCache, $set_label, $params );
			break;

		case 'select_group':
			$GroupCache = & get_Cache( 'GroupCache' );
			$Form->select_input_object( 'edit_plugin_'.$Plugin->ID.'_set_'.$set_name, $set_value, $GroupCache, $set_label, $params );
			break;

		case 'select_user':
			$UserCache = & get_Cache( 'UserCache' );
			$UserCache->load_all();
			if( ! isset($params['loop_object_method']) )
			{
				$params['loop_object_method'] = 'get_preferred_name';
			}
			$Form->select_input_object( 'edit_plugin_'.$Plugin->ID.'_set_'.$set_name, $set_value, $UserCache, $set_label, $params );
			break;

		case 'array':
			$has_array_type = true;

			if( substr_count( $set_name, '[' ) % 2 )
			{ // this refers to a specific array type set (with index pos at the end), e.g. when adding a field through AJAX:
				$pos_last_bracket = strrpos($set_name, '[');
				$k_nb = substr( $set_name, $pos_last_bracket+1, -1 );
				$disp_arrays = array( '' => $set_value ); // empty key..
				$set_name = substr($set_name, 0, $pos_last_bracket);
			}
			else
			{ // display all values hold in this set:
				$disp_whole_set = true;
				$disp_arrays = $set_value;
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
				$k_nb = 0;
			}


			$user_ID = $set_type == 'UserSettings' ? $set_target->ID : '';
			if( is_array( $set_value ) && ! empty($set_value) )
			{ // Display value of the setting. It may be empty, if there's no set yet.
				foreach( $disp_arrays as $k => $v )
				{
					$fieldset_icons = array();
					if( ! isset($set_meta['min_count']) || count($set_value) > $set_meta['min_count'] )
					{ // provide icon to remove this set
						$fieldset_icons[] = action_icon(
								T_('Delete set!'),
								'delete',
								regenerate_url( 'action', array('action=del_settings_set&amp;set_path='.$set_name.'['.$k.']'.( $set_type == 'UserSettings' ? '&amp;user_ID='.$user_ID : '' ), 'plugin_ID='.$Plugin->ID) ),
								'',
								5, 0, /* icon/text prio */
								// attach onclick event to remove the whole fieldset (AJAX):
								array(
									'onclick' => "
										var oThis = this;
										\$.get('{$htsrv_url}async.php', {
												action: 'del_plugin_sett_set',
												plugin_ID: '{$Plugin->ID}',
												user_ID: '$user_ID',
												set_type: '$set_type',
												set_path: '{$set_name}[$k]'
											},
											function(r, status) {
												if( r == 'OK' )
												{
													\$(oThis).parents('fieldset:first').remove();
												}
										} );
										return false;",
									)
								);
					}
					$Form->begin_fieldset( '#'.$k_nb, array('class'=>'bordered'), $fieldset_icons );

					if( isset($set_meta['key']) )
					{ // KEY FOR THIS ENTRY:
						if( ! strlen($k) && isset($set_meta['key']['defaultvalue']) )
						{ // key is not given/set and we have a default:
							$l_value = $set_meta['key']['defaultvalue'];
						}
						else
						{
							$l_value = $k;
						}
						display_plugin_settings_fieldset_field( $set_name.'['.$k_nb.'][__key__]', $set_meta['key'], $Plugin, $Form, $set_type, $set_target, $l_value );
					}

					foreach( $set_meta['entries'] as $l_set_name => $l_set_entry )
					{
						$l_value = isset($set_value[$k][$l_set_name]) ? $set_value[$k][$l_set_name] : NULL;
						display_plugin_settings_fieldset_field( $set_name.'['.$k_nb.']['.$l_set_name.']', $l_set_entry, $Plugin, $Form, $set_type, $set_target, $l_value );
					}
					$Form->end_fieldset();
					$k_nb++;
				}
			}

			// TODO: fix this for AJAX callbacks, when removing and re-adding items (dh):
			if( ! isset( $set_meta['max_number'] ) || $set_meta['max_number'] > ($k_nb) )
			{ // no max_number defined or not reached: display link to add a new set
				$set_path = $set_name.'['.$k_nb.']';

				echo '<div>';
				echo action_icon(
					sprintf( T_('Add a new set of &laquo;%s&raquo;'), $set_label),
					'new',
					regenerate_url( 'action', array('action=add_settings_set', 'set_path='.$set_path.( $set_type == 'UserSettings' ? '&amp;user_ID='.get_param('user_ID') : '' ), 'plugin_ID='.$Plugin->ID) ),
					T_('New set'),
					5, 1, /* icon/text prio */
					array('onclick'=> "
						var oThis = this;
						\$.get('{$htsrv_url}async.php', {
								action: 'add_plugin_sett_set',
								plugin_ID: '{$Plugin->ID}',
								set_type: '$set_type',
								set_path: '$set_path'
							},
							function(r, status) {
								\$(oThis).parent('div').html(r);
							}
						);
						return false;")
					);
				echo '</div>';
			}

			if( ! empty($disp_whole_set) )
			{ // close the surrounding fieldset:
				$Form->end_fieldset();
			}

			break;

		case 'password':
			$params['type'] = 'password'; // same as text input, but type=password

		case 'float':
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

	if( $outer_most && $has_array_type )
	{ // Note for Non-Javascript users:
		echo '<script type="text/javascript"></script><noscript>';
		echo '<p class="note">'.T_('Note: before adding a new set you have to save any changes.').'</p>';
		echo '</noscript>';
	}
}


/**
 * Helper method for "add_settings_set" and "delete_settings_set" action.
 *
 * Walks the given settings path and either inits the target entry or unsets it ($init_value=NULL).
 *
 * @param Plugin
 * @param string Settings type ("Settings" or "UserSettings")
 * @param string The settings path, e.g. 'setting[0]foo[1]'. (Is used as array internally for recursion.)
 * @param mixed The initial value of the setting, typically array() - NULL to unset it (action "delete_settings_set" uses it)
 * @return array|false
 */
function _set_setting_by_path( & $Plugin, $set_type, $path, $init_value = array() )
{
	$r = get_plugin_settings_node_by_path( $Plugin, $set_type, $path, true );
	if( $r === false )
	{
		return false;
	}

	// Make return value handier. Note: list() would copy and destroy the references (setting and set_node)!
	$set_name = & $r['set_name'];
	$set_node = & $r['set_node'];
	$set_meta = & $r['set_meta'];
	$set_parent = & $r['set_parent'];
	$set_key  = & $r['set_key'];
	$setting  = & $r['setting'];
	#pre_dump( $r );

	#if( isset($set_node) && $init_value !== NULL )
	#{ // Setting already exists (and we do not want to delete), e.g. page reload!
	#	return false;
	#	/*
	#	while( isset($l_setting[ $path[0] ]) )
	#	{ // bump the index until not set
	#		$path[0]++;
	#	}
	#	*/
	#}
	#else
	if( is_null($init_value) )
	{ // NULL is meant to unset it
		unset($set_parent[$set_key]);
	}
	else
	{ // Init entries:
		// destroys reference: $set_node = $init_value;

		// Copy meta entries:
		foreach( $set_meta['entries'] as $k => $v )
		{
			if( isset( $v['defaultvalue'] ) )
			{ // set to defaultvalue
				$set_node[$k] = $v['defaultvalue'];
			}
			else
			{
				if( isset($v['type']) && $v['type'] == 'array' )
				{
					$set_node[$k] = array();
				}
				else
				{
					$set_node[$k] = '';
				}
			}
		}
	}

	// Set it into $Plugin->Settings or $Plugin->UserSettings:
	$Plugin->$set_type->set( $set_name, $setting );

	return $setting;
}


/**
 * Get a node from settings by path (e.g. "locales[0][questions]")
 *
 * @param Plugin
 * @param string Settings type ("Settings" or "UserSettings")
 * @param string The settings path, e.g. 'setting[0]foo[1]' or even 'setting[]'. (Is used as array internally for recursion.)
 * @return array Array(
 *          - 'set_name': setting name (string); key of the first level
 *          - 'set_node': selected setting node, may be NULL (by reference)
 *          - 'set_meta': meta info (from GetDefault[User]Settings()) for selected node (array)
 *          - 'set_parent': parent node (by reference)
 *          - 'set_key': key in parent node (by reference)
 *          - 'setting': whole settings (array)
 */
function get_plugin_settings_node_by_path( & $Plugin, $set_type, $path, $create = false )
{
	// Init:
	if( ! preg_match( '~^\w+(\[\w+\])+$~', $path ) )
	{
		debug_die( 'Invalid path param!' );
	}

	$path = preg_split( '~(\[|\]\[?)~', $path, -1 ); // split by "[" and "][", so we get an array with setting name and index alternating
	$foo = array_pop($path); // remove last one
	if( ! empty($foo) )
		debug_die('Assertion failed!');

	$set_name = $path[0];

	$setting = $Plugin->$set_type->get($set_name);  // $Plugin->Settings or $Plugin->UserSettings

	// meta info for this setting:
	$method = 'GetDefault'.$set_type; // GetDefaultSettings or GetDefaultUserSettings
	$defaults = $Plugin->$method( $tmp_params = array('for_editing'=>true) );
	if( ! isset($defaults[ $set_name ]) )
	{
		//debug_die( 'Invalid setting ('.$set_name.') - no meta data!' );
		return false;
	}

	$found_node = & $setting;
	$defaults_node = & $defaults;
	$set_meta = $defaults[$set_name];
	$set_parent = NULL;
	$set_key = NULL;

	$count = 0;
	while( count($path) )
	{
		$count++;

		$loop_name = array_shift($path);

		if( $count > 1 )
		{
			$set_parent = & $found_node[$loop_name];
			$set_key = NULL;
			$defaults_node = & $defaults_node['entries'][$loop_name];
			$found_node = & $found_node[$loop_name];
		}
		else
		{
			$defaults_node = & $defaults_node[$loop_name];
			$set_parent = & $setting;
		}

		if( count($path) )
		{ // has an index => array
			$loop_index = array_shift($path);

			#$set_parent = & $set_parent[$loop_name];
			$set_key = $loop_index;

			if( $set_key === '' )
			{ // []-syntax: append entry
				if( $create && ! count($path) )
				{ // only create, if at the end
					$found_node[] = array();
				}
				$found_node = & $found_node[ array_pop(array_keys($found_node)) ];
			}
			else
			{ // specific key:
				if( ! isset($found_node[$loop_index]) )
				{
					$found_node[$loop_index] = array();
				}
				$found_node = & $found_node[$loop_index];
			}
		}
	}

	#echo '<h1>RETURN</h1>'; pre_dump( $set_parent, $set_key );
	return array(
		'set_name' => $set_name,
		'set_node' => & $found_node,
		'set_meta' => $defaults_node,
		'set_parent' => & $set_parent,
		'set_key' => & $set_key,
		'setting' => & $setting );
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
	global $Messages;

	$method = 'GetDefault'.$set_type;
	$tmp_params = array('for_editing' => true);

	foreach( $Plugin->$method($tmp_params) as $l_name => $l_meta )
	{
		if( isset($l_meta['layout']) )
		{ // a layout "setting"
			continue;
		}

		if( ! empty($l_meta['disabled']) || ! empty($l_meta['no_edit']) )
		{ // the setting is disabled
			continue;
		}

		$l_param_default = '';
		if( isset($l_meta['type']) )
		{
			switch( $l_meta['type'] )
			{
				case 'array':
					// this settings has a type
					$l_param_type = $l_meta['type'];
					break;

				case 'checkbox':
					$l_param_type = 'integer';
					$l_param_default = 0;
					break;

				case 'html_input':
				case 'html_textarea':
					$l_param_type = 'html';
					break;
			}
		}
		else
		{
			$l_param_type = 'string';
		}

		// Get the value:
		$l_value = param( 'edit_plugin_'.$Plugin->ID.'_set_'.$l_name, $l_param_type, $l_param_default );

		if( isset($l_meta['type']) && $l_meta['type'] == 'array' )
		{ // make keys (__key__) in arrays unique and remove them
			handle_array_keys_in_plugin_settings($l_value);
		}

		if( ! validate_plugin_settings_from_param('edit_plugin_'.$Plugin->ID.'_set_'.$l_name, $l_value, $l_meta) )
		{
			continue;
		}

		// Ask the plugin if it's ok (through PluginSettingsValidateSet() / PluginUserSettingsValidateSet()):
		$tmp_params = array(
			'name' => $l_name,
			'value' => & $l_value,
			'meta' => $l_meta,
			'action' => 'set',
			);
		if( $set_type == 'UserSettings' )
		{
			global $current_User;
			$tmp_params['User'] = $set_target;
		}
		if( $error = $use_Plugins->call_method( $Plugin->ID, 'Plugin'.$set_type.'ValidateSet', $tmp_params ) )
		{ // skip this
			param_error( 'edit_plugin_'.$Plugin->ID.'_set_'.$l_name, $error );
			continue;
		}

		// Update the param value, because a plugin might have changed it (through reference):
		$GLOBALS['edit_plugin_'.$Plugin->ID.'_set_'.$l_name] = $l_value;

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
 * Validates settings according to their meta info recursively.
 *
 * @todo Init "checkbox" values in "array" type settings (they do not get send) (dh)
 * @param string Param name
 * @param array Meta info
 * @return boolean
 */
function validate_plugin_settings_from_param( $param_name, $value, $meta )
{
	global $Messages;

	if( is_array($value) && isset($meta['entries']) )
	{
		$r = true;
		if(isset($meta['key']))
		{ // validate keys:
			foreach( array_keys($value) as $k )
			{
				if( ! validate_plugin_settings_from_param($param_name.'['.$k.'][__key__]', $k, $meta['key']) )
				{
					$r = false;
				}
			}
		}

		// Check max_count/min_count
		// dh> TODO: find a way to link it to the form's fieldset (and add an "error" class to it)
		if( isset($meta['max_count']) && count($value) > $meta['max_count'] )
		{
			$r = false;
			$label = isset($meta['label']) ? $meta['label'] : $param_name;
			$Messages->add( sprintf( T_('Too many entries in the "%s" set. It must have %d at most.'), $label, $meta['max_count'] ), 'error' );
		}
		elseif( isset($meta['min_count']) && count($value) < $meta['min_count'] )
		{
			$r = false;
			$label = isset($meta['label']) ? $meta['label'] : $param_name;
			$Messages->add( sprintf( T_('Too few entries in the "%s" set. It must have %d at least.'), $label, $meta['min_count'] ), 'error' );
		}

		foreach( $meta['entries'] as $mk => $mv )
		{
			foreach( $value as $vk => $vv )
			{
				if( ! isset($vv[$mk]) )
					continue;

				if( ! validate_plugin_settings_from_param($param_name.'['.$vk.']['.$mk.']', $vv[$mk], $mv) )
				{
					$r = false;
				}
			}
		}
		return $r;
	}


	if( isset($meta['type']) )
	{
		switch( $meta['type'] )
		{
			case 'integer':
				if( ! preg_match( '~^[-+]?\d+$~', $value ) )
				{
					param_error( $param_name, sprintf( T_('The value for &laquo;%s&raquo; must be numeric.'), $meta['label'] ), T_('The value must be numeric.') );
					return false;
				}
				break;

			case 'float':
				if( ! preg_match( '~^[-+]?\d+(\.\d+)?$~', $value ) )
				{
					param_error( $param_name, sprintf( T_('The value for &laquo;%s&raquo; must be numeric.'), $meta['label'] ), T_('The value must be numeric.') );
					return false;
				}
				break;

			case 'select':
				if( ! in_array( $value, array_keys($meta['options']) ) )
				{
					param_error( $param_name, sprintf( T_('Invalid option &laquo;%s&raquo;.'), $value ) );
					return false;
				}
				break;

			case 'select_blog':
			case 'select_group':
			case 'select_user':
				if( ! strlen($value) )
				{
					if( empty($meta['allow_none']) )
					{ // empty is not ok
						param_error( $param_name, sprintf( T_('Invalid option &laquo;%s&raquo;.'), $value ) );
						return false;
					}
				}
				else
				{ // Try retrieving the value from the corresponding Cache:
					switch( $meta['type'] )
					{
						case 'select_blog':
							$Cache = & get_Cache( 'BlogCache' );
							break;

						case 'select_group':
							$Cache = & get_Cache( 'GroupCache' );
							break;

						case 'select_user':
							$Cache = & get_Cache( 'UserCache' );
							break;
					}
					if( ! $Cache->get_by_ID($value, false, false) )
					{
						param_error( $param_name, sprintf( T_('Invalid option &laquo;%s&raquo;.'), $value ) );
						return false;
					}
				}
				break;
		}
	}

	// Check maxlength:
	if( isset($meta['maxlength']) )
	{
		if( strlen($value) > $meta['maxlength'] )
		{
			param_error( $param_name, sprintf( T_('The value is too long.'), $value ) );
		}
	}

	// Check valid pattern:
	if( isset($meta['valid_pattern']) )
	{
		$param_pattern = is_array($meta['valid_pattern']) ? $meta['valid_pattern']['pattern'] : $meta['valid_pattern'];
		if( ! preg_match( $param_pattern, $value ) )
		{
			$param_error = is_array($meta['valid_pattern']) ? $meta['valid_pattern']['error'] : sprintf(T_('The value is invalid. It must match the regular expression &laquo;%s&raquo;.'), $param_pattern);
			param_error( $param_name, $param_error );
			return false;
		}
	}

	// Check valid range:
	if( isset($meta['valid_range']) )
	{
		// Transform numeric indexes into associative keys:
		if( ! isset($meta['valid_range']['min'], $meta['valid_range']['max'])
			&& isset($meta['valid_range'][0], $meta['valid_range'][1]) )
		{
			$meta['valid_range']['min'] = $meta['valid_range'][0];
			$meta['valid_range']['max'] = $meta['valid_range'][1];
		}
		if( isset($meta['valid_range'][2]) && ! isset($meta['valid_range']['error']) )
		{
			$meta['valid_range']['error'] = $meta['valid_range'][2];
		}

		if( (isset($meta['valid_range']['min']) && $value < $meta['valid_range']['min'])
				|| (isset($meta['valid_range']['max']) && $value > $meta['valid_range']['max']) )
		{
			if( isset($meta['valid_range']['error']) )
			{
				$param_error = $meta['valid_range']['error'];
			}
			else
			{
				if( isset($meta['valid_range']['min']) && isset($meta['valid_range']['max']) )
				{
					$param_error = sprintf(T_('The value is invalid. It must be in the range from %s to %s.'), $meta['valid_range']['min'], $meta['valid_range']['max']);
				}
				elseif( isset($meta['valid_range']['max']) )
				{
					$param_error = sprintf(T_('The value is invalid. It must be smaller than %s.'), $meta['valid_range']['max']);
				}
				else
				{
					$param_error = sprintf(T_('The value is invalid. It must be greater than %s.'), $meta['valid_range']['min']);
				}
			}

			param_error( $param_name, $param_error );
			return false;
		}
	}

	return true;
}


/**
 * This handles the special "__key__" index in all array type values
 * in the given array. It makes sure, that "__key__" is unique and
 * replaces the original key of the value with it.
 * @param array (by reference)
 */
function handle_array_keys_in_plugin_settings( & $a )
{
	if( ! is_array($a) )
		return;

	$new_arr = array(); // use a new array to maintain order, also for "numeric" keys

	foreach( array_keys($a) as $k )
	{
		$v = & $a[$k];

		if( is_array($v) && isset($v['__key__']) )
		{
			if( $k != $v['__key__'] )
			{
				$k = $v['__key__'];
				if( ! strlen($k) || isset($a[ $k ]) )
				{ // key already exists (or is empty):
					$c = 1;

					while( isset($a[ $k.'_'.$c ]) )
					{
						$c++;
					}
					$k = $k.'_'.$c;
				}
			}
			unset($v['__key__']);

			$new_arr[$k] = $v;
		}
		else
		{
			$new_arr[$k] = $v;
		}

		// Recurse:
		foreach( array_keys($v) as $rk )
		{
			if( is_array($v[$rk]) )
			{
				handle_array_keys_in_plugin_settings($v[$rk]);
			}
		}
	}
	$a = $new_arr;
}


/* {{{ Revision log:
 * $Log$
 * Revision 1.36  2006/12/05 01:59:12  blueyed
 * Added validation for all types of (User)Settings
 *
 * Revision 1.35  2006/12/04 22:26:06  blueyed
 * Fixed calling GetDefault(User)Settings with $params in set_Settings_for_Plugin_from_Request()
 *
 * Revision 1.34  2006/12/04 21:39:49  blueyed
 * Minor refactoring
 *
 * Revision 1.33  2006/12/01 16:47:26  blueyed
 * - Use EVO_NEXT_VERSION, which should get replaced with the next version 1.10 or 2.0 or whatever
 * - "action" param for PluginSettingsValidateSet
 * - Removed deprecated Plugin::set_param()
 *
 * Revision 1.32  2006/11/24 18:27:27  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.31  2006/11/16 23:43:40  blueyed
 * - "key" entry for array-type Plugin(User)Settings can define an input field for the key of the settings entry
 * - cleanup
 *
 * Revision 1.30  2006/11/10 17:14:20  blueyed
 * Added "select_blog" type for Plugin (User)Settings
 *
 * Revision 1.29  2006/11/10 16:37:57  blueyed
 * Fixed ID for AJAX DIV
 *
 * Revision 1.28  2006/11/09 23:40:57  blueyed
 * Fixed Plugin UserSettings array type editing; Added jquery and use it for AJAHifying Plugin (User)Settings editing of array types
 *
 * Revision 1.27  2006/11/02 15:56:53  blueyed
 * Add note about having to save the settings, before adding a new set.
 *
 * Revision 1.26  2006/10/08 22:13:05  blueyed
 * Added "float" type to Plugin Setting types.
 *
 * Revision 1.25  2006/08/20 22:25:22  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.24  2006/08/20 20:12:33  fplanque
 * param_() refactoring part 1
 *
 * Revision 1.23  2006/08/19 08:50:27  fplanque
 * moved out some more stuff from main
 *
 * Revision 1.22  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.21  2006/08/08 10:02:26  yabs
 * added "cols" to the list of params that are passed through to $Form
 *
 * Revision 1.20  2006/08/07 09:57:51  blueyed
 * doc
 *
 * Revision 1.19  2006/07/31 15:41:37  yabs
 * Modified 'allow_html' to html_input/html_textarea
 *
 * Revision 1.18  2006/07/31 06:58:02  yabs
 * Added option to plugin settings : allow_html
 *
 * Revision 1.17  2006/05/22 20:35:37  blueyed
 * Passthrough some attribute of plugin settings, allowing to use JS handlers. Also fixed submitting of disabled form elements.
 *
 * Revision 1.16  2006/04/19 20:14:03  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.15  2006/04/19 18:14:12  blueyed
 * Added "no_edit" param to GetDefault(User)Settings
 *
 * Revision 1.14  2006/04/18 17:06:14  blueyed
 * Added "disabled" to plugin (user) settings (Thanks to balupton)
 *
 * Revision 1.13  2006/04/13 01:23:19  blueyed
 * Moved help related functions back to Plugin class
 *
 * Revision 1.12  2006/04/11 22:09:08  blueyed
 * Fixed validation of negative integers (and also allowed "+" at the beginning)
 *
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