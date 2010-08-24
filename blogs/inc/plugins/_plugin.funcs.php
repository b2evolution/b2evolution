<?php
/**
 * Functions for Plugin handling.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * Used for PluginSettings ("Edit plugin") and PluginUserSettings ("Edit user settings") as well as widgets.
 *
 * @todo dh> Allow to move setting sets up and down (order). Control goes into /inc/CONTROL/settings/plugins.php.
 * @todo NOTE: fp> I'm using this outside of Plugins; I'm not sure about proper factorization yet.
 *       This should probably be an extension of the Form class. Sth like "AutoForm" ;)
 *
 * @param string Settings path, e.g. 'locales[0]' or 'setting'
 * @param array Meta data for this setting.
 * @param Form (by reference)
 * @param string Settings type ('Settings' or 'UserSettings' or 'Widget' or 'Skin')
 * @param Plugin|Widget
 * @param mixed Target (User object for 'UserSettings')
 * @param mixed Value to really use (used for recursion into array type settings)
 */
function autoform_display_field( $parname, $parmeta, & $Form, $set_type, $Obj, $set_target = NULL, $use_value = NULL )
{
	global $debug;
	global $htsrv_url;
	static $has_array_type;

	if( ! empty($parmeta['no_edit']) )
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
	foreach( $parmeta as $k => $v )
	{
		if( in_array( $k, array( 'id', 'onchange', 'onclick', 'onfocus', 'onkeyup', 'onkeydown', 'onreset', 'onselect', 'cols', 'rows', 'maxlength' ) ) )
		{
			$params[$k] = $v;
		}
	}
	if( ! empty($parmeta['multiple']) )
	{ // "multiple" attribute for "select" inputs:
		$params['multiple'] = 'multiple';
	}

	if( isset($parmeta['note']) )
	{
		$params['note'] = $parmeta['note'];
	}

	if( ! isset($parmeta['type']) ||  $parmeta['type'] == 'html_input' )
	{
		$parmeta['type'] = 'text';
	}
	elseif( $parmeta['type'] == 'html_textarea' )
	{
		$parmeta['type'] = 'textarea';
	}

	if( strpos($parmeta['type'], 'select_') === 0 )
	{ // 'allow_none' setting for select_* types
		if( isset($parmeta['allow_none']) )
		{
			$params['allow_none'] = $parmeta['allow_none'];
		}
	}

	$help_icon = NULL;
	if( isset($parmeta['help']) )
	{
		if( $parmeta['help'] === true )
		{ // link to $parname-target:
			$help_target = '#'.preg_replace( array('~\]?\[\d+\]\[~', '~\]$~'), array('_',''), $parname );
		}
		else
		{
			$help_target = $parmeta['help'];
		}
		$help_icon = $Obj->get_help_link( $help_target );
	}

	$set_label = isset($parmeta['label']) ? $parmeta['label'] : '';

	if( ! empty($parmeta['disabled']) )
	{
		$params['disabled'] = 'disabled';
	}


	// "Layout" settings:
	if( isset($parmeta['layout']) )
	{
		switch( $parmeta['layout'] )
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
	else
	{
		switch( $set_type )
		{
			case 'CollSettings':
				$set_value = $Obj->get_coll_setting( $parname, $set_target );
				$error_value = NULL;
				break;

			case 'Skin':
				$set_value = $Obj->get_setting( $parname );
				$error_value = NULL;
				break;

			case 'Widget':
				$set_value = $Obj->get_param( $parname );
				$error_value = NULL;
				break;

			case 'UserSettings':
				// NOTE: this assumes we come here only on recursion or with $use_value set..!
				$set_value = $Obj->UserSettings->get( $parname, $set_target->ID );
				$error_value = $Obj->PluginUserSettingsValidateSet( $tmp_params = array(
					'name' => $parname,
					'value' => & $set_value,
					'meta' => $parmeta,
					'User' => $set_target,
					'action' => 'display' ) );
				break;

			case 'Settings':
				// NOTE: this assumes we come here only on recursion or with $use_value set..!
				$set_value = $Obj->Settings->get( $parname );
				$error_value = $Obj->PluginSettingsValidateSet( $tmp_params = array(
					'name' => $parname,
					'value' => & $set_value,
					'meta' => $parmeta,
					'action' => 'display' ) );
				break;

			default:
				debug_die( "unhandled set_type $set_type" );
				break;
		}

		if( $error_value )
		{ // add error
			param_error( 'edit_plugin_'.$Obj->ID.'_set_'.$parname, NULL, $error_value ); // only add the error to the field
		}
	}

	// Display input element:
	$input_name = 'edit_plugin_'.$Obj->ID.'_set_'.$parname;
	if( substr($parmeta['type'], 0, 6) == 'select' && ! empty($parmeta['multiple']) )
	{ // a "multiple" select:
		$input_name .= '[]';
	}
	switch( $parmeta['type'] )
	{
		case 'checkbox':
			$Form->checkbox_input( $input_name, $set_value, $set_label, $params );
			break;

		case 'textarea':
			$textarea_rows = isset($parmeta['rows']) ? $parmeta['rows'] : 3;
			$Form->textarea_input( $input_name, $set_value, $textarea_rows, $set_label, $params );
			break;

		case 'select':
			$params['force_keys_as_values'] = true; // so that numeric keys get used as values! autoform_validate_param_value() checks for the keys only.
			$Form->select_input_array( $input_name, $set_value, $parmeta['options'], $set_label, isset($parmeta['note']) ? $parmeta['note'] : NULL, $params );
			break;

		case 'select_blog':
			$BlogCache = & get_BlogCache();
			$Form->select_input_object( $input_name, $set_value, $BlogCache, $set_label, $params );
			break;

		case 'select_group':
			$GroupCache = & get_GroupCache();
			$Form->select_input_object( $input_name, $set_value, $GroupCache, $set_label, $params );
			break;

		case 'select_user':
			$UserCache = & get_UserCache();
			$UserCache->load_all();
			if( ! isset($params['loop_object_method']) )
			{
				$params['loop_object_method'] = 'get_preferred_name';
			}
			$Form->select_input_object( $input_name, $set_value, $UserCache, $set_label, $params );
			break;

		case 'radio':
			if( ! isset($parmeta['field_lines']) )
			{
				$parmeta['field_lines'] = false;
			}
			$Form->radio( $input_name, $set_value, $parmeta['options'], $set_label, $parmeta['field_lines'], $parmeta['note'] );
			break;

		case 'array':
			$has_array_type = true;

			if( substr_count( $parname, '[' ) % 2 )
			{ // this refers to a specific array type set (with index pos at the end), e.g. when adding a field through AJAX:
				$pos_last_bracket = strrpos($parname, '[');
				$k_nb = substr( $parname, $pos_last_bracket+1, -1 );
				$disp_arrays = array( '' => $set_value ); // empty key..
				$parname = substr($parname, 0, $pos_last_bracket);
			}
			else
			{ // display all values hold in this set:
				$disp_whole_set = true;
				$disp_arrays = $set_value;
				$fieldset_title = $set_label;
				if( $debug )
				{
					$fieldset_title .= ' [debug: '.$parname.']';
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
					if( ! isset($parmeta['min_count']) || count($set_value) > $parmeta['min_count'] )
					{ // provide icon to remove this set
						$fieldset_icons[] = action_icon(
								T_('Delete set!'),
								'delete',
								regenerate_url( 'action', array('action=del_settings_set&amp;set_path='.$parname.'['.$k.']'.( $set_type == 'UserSettings' ? '&amp;user_ID='.$user_ID : '' ), 'plugin_ID='.$Obj->ID) ),
								'',
								5, 0, /* icon/text prio */
								// attach onclick event to remove the whole fieldset (AJAX):
								array(
									'onclick' => "
										var oThis = this;
										jQuery.get('{$htsrv_url}async.php', {
												action: 'del_plugin_sett_set',
												plugin_ID: '{$Obj->ID}',
												user_ID: '$user_ID',
												set_type: '$set_type',
												set_path: '{$parname}[$k]'
											},
											function(r, status) {
												if( r == 'OK' )
												{
													jQuery(oThis).parents('fieldset:first').remove();
												}
										} );
										return false;",
									)
								);
					}
					$Form->begin_fieldset( '#'.$k_nb, array('class'=>'bordered'), $fieldset_icons );

					if( isset($parmeta['key']) )
					{ // KEY FOR THIS ENTRY:
						if( ! strlen($k) && isset($parmeta['key']['defaultvalue']) )
						{ // key is not given/set and we have a default:
							$l_value = $parmeta['key']['defaultvalue'];
						}
						else
						{
							$l_value = $k;
						}
						// RECURSE:
						autoform_display_field( $parname.'['.$k_nb.'][__key__]', $parmeta['key'], $Form, $set_type, $Obj, $set_target, $l_value );
					}

					foreach( $parmeta['entries'] as $l_set_name => $l_set_entry )
					{
						$l_value = isset($set_value[$k][$l_set_name]) ? $set_value[$k][$l_set_name] : NULL;
						// RECURSE:
						autoform_display_field( $parname.'['.$k_nb.']['.$l_set_name.']', $l_set_entry, $Form, $set_type, $Obj, $set_target, $l_value );
					}
					$Form->end_fieldset();
					$k_nb++;
				}
			}

			// TODO: fix this for AJAX callbacks, when removing and re-adding items (dh):
			if( ! isset( $parmeta['max_number'] ) || $parmeta['max_number'] > ($k_nb) )
			{ // no max_number defined or not reached: display link to add a new set
				$set_path = $parname.'['.$k_nb.']';

				echo '<div>';
				echo action_icon(
					sprintf( T_('Add a new set of &laquo;%s&raquo;'), $set_label),
					'new',
					regenerate_url( 'action', array('action=add_settings_set', 'set_path='.$set_path.( $set_type == 'UserSettings' ? '&amp;user_ID='.get_param('user_ID') : '' ), 'plugin_ID='.$Obj->ID) ),
					T_('New set'),
					5, 1, /* icon/text prio */
					array('onclick'=> "
						var oThis = this;
						jQuery.get('{$htsrv_url}async.php', {
								action: 'add_plugin_sett_set',
								plugin_ID: '{$Obj->ID}',
								set_type: '$set_type',
								set_path: '$set_path'
							},
							function(r, status) {
								jQuery(oThis).parent('div').html(r);
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
			if( isset($parmeta['size']) )
			{
				$size = (int)$parmeta['size'];
			}
			else
			{ // Default size:
				$size = 15;
			}
			if( isset($parmeta['maxlength']) )
			{
				$params['maxlength'] = (int)$parmeta['maxlength'];
			}
			else
			{ // do not use size as maxlength, if not given!
				$params['maxlength'] = '';
			}

			$Form->text_input( $input_name, $set_value, $size, $set_label, '', $params ); // TEMP: Note already in params
			break;

		case 'info':
			$Form->info( $parmeta['label'], $parmeta['info'] );
			break;

		default:
			debug_die( 'Unsupported type ['.$parmeta['type'].'] from GetDefaultSettings()!' );
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
	if( ! is_array($setting) )
	{ // this may happen, if there was a non-array setting stored previously:
		// discard those!
		$setting = array();
	}

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
 * fp> WARNING: also used outside of plugins. Work in progress.
 *
 * This handled plugin specific params when saving a user profile (PluginUserSettings) or plugin settings (PluginSettings).
 *
 * @param string Settings path, e.g. 'locales[0]' or 'setting'
 * @param array Meta data for this setting.
 * @param Plugin|Widget
 * @param string Type of Settings (either 'Settings' or 'UserSettings').
 * @param mixed Target (User object for 'UserSettings')
 */
function autoform_set_param_from_request( $parname, $parmeta, & $Obj, $set_type, $set_target = NULL )
{
	if( isset($parmeta['layout']) )
	{ // a layout "setting"
		return;
	}

	if( ! empty($parmeta['disabled']) || ! empty($parmeta['no_edit']) )
	{ // the setting is disabled
		return;
	}

	$l_param_type = 'string';
	$l_param_default = '';
	if( isset($parmeta['type']) )
	{
		if( substr($parmeta['type'], 0, 6) == 'select' && ! empty($parmeta['multiple']) )
		{ // a "multiple" select:
			$l_param_type = 'array';
		}
		switch( $parmeta['type'] )
		{
			case 'array':
				// this settings has a type
				$l_param_type = $parmeta['type'];
				break;

			case 'checkbox':
				$l_param_type = 'integer';
				$l_param_default = 0;
				break;

			case 'html_input':
			case 'html_textarea':
				$l_param_type = 'html';
				break;

			default:
		}
	}

	// Get the value:
	$l_value = param( 'edit_plugin_'.$Obj->ID.'_set_'.$parname, $l_param_type, $l_param_default );
	// pre_dump( $parname, $l_value );

	if( isset($parmeta['type']) && $parmeta['type'] == 'array' )
	{ // make keys (__key__) in arrays unique and remove them
		handle_array_keys_in_plugin_settings($l_value);
	}

	if( ! autoform_validate_param_value('edit_plugin_'.$Obj->ID.'_set_'.$parname, $l_value, $parmeta) )
	{
		return;
	}

	// Validate form values:
	switch( $set_type )
	{
		case 'CollSettings':
			$error_value = NULL;
			$Obj->set_coll_setting( $parname, $l_value );
			break;

		case 'Skin':
			$error_value = NULL;
			$Obj->set_setting( $parname, $l_value );
			break;

		case 'Widget':
			$error_value = NULL;
			$Obj->set( $parname, $l_value );
			break;

		case 'UserSettings':
			// Plugin User settings:
			$error_value = $Obj->PluginUserSettingsValidateSet( $dummy = array(
				'name' => $parname,
				'value' => & $l_value,
				'meta' => $parmeta,
				'User' => $set_target,
				'action' => 'set' ) );
			// Update the param value, because a plugin might have changed it (through reference):
			$GLOBALS['edit_plugin_'.$Obj->ID.'_set_'.$parname] = $l_value;

			if( empty( $error_value ) )
			{
				$Obj->UserSettings->set( $parname, $l_value, $set_target->ID );
			}
			break;

		case 'Settings':
			// Plugin global settings:
			$error_value = $Obj->PluginSettingsValidateSet( $dummy = array(
				'name' => $parname,
				'value' => & $l_value,
				'meta' => $parmeta,
				'action' => 'set' ) );
			// Update the param value, because a plugin might have changed it (through reference):
			$GLOBALS['edit_plugin_'.$Obj->ID.'_set_'.$parname] = $l_value;

			if( empty( $error_value ) )
			{
				$Obj->Settings->set( $parname, $l_value );
			}
			break;

		default:
			debug_die( "unhandled set_type $set_type" );
			break;
	}

	if( $error_value )
	{ // A validation error has occured, record error message:
		param_error( 'edit_plugin_'.$Obj->ID.'_set_'.$parname, $error_value );
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
function autoform_validate_param_value( $param_name, $value, $meta )
{
	global $Messages;

	if( is_array($value) && isset($meta['entries']) )
	{
		$r = true;
		if(isset($meta['key']))
		{ // validate keys:
			foreach( array_keys($value) as $k )
			{
				if( ! autoform_validate_param_value($param_name.'['.$k.'][__key__]', $k, $meta['key']) )
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

				if( ! autoform_validate_param_value($param_name.'['.$vk.']['.$mk.']', $vv[$mk], $mv) )
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

			case 'radio':
				$check_value = false;
				foreach($meta['options'] as $arr)
				{
					if( ! is_array($arr) )
					{
						param_error( $param_name, sprintf( T_('Invalid option &laquo;%s&raquo;.'), $arr ) );
						return false;
					}
					if( $value == $arr[0] )
					{
						$check_value = true;
						break;
					}
				}
				if ( ! $check_value )
				{
					param_error( $param_name, sprintf( T_('Invalid option &laquo;%s&raquo;.'), $value ) );
					return false;
				}
				break;

			case 'select':
				$check_options = $value;
				if( ! is_array($check_options) )
				{ // no "multiple" select:
					$check_options = array($check_options);
				}

				foreach($check_options as $v)
				{
					if( ! in_array( $v, array_keys($meta['options']) ) )
					{
						param_error( $param_name, sprintf( T_('Invalid option &laquo;%s&raquo;.'), $v ) );
						return false;
					}
				}
				break;

			case 'select_blog':
			case 'select_group':
			case 'select_user':
				if( is_array($value) && empty($value) // empty "multiple" select
					|| ( ! is_array($value) && ! strlen($value) ) )
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
							$Cache = & get_BlogCache();
							break;

						case 'select_group':
							$Cache = & get_GroupCache();
							break;

						case 'select_user':
							$Cache = & get_UserCache();
							break;
					}

					$check_options = $value;
					if( ! is_array($check_options) )
					{ // no "multiple" select:
						$check_options = array($check_options);
					}

					foreach($check_options as $v)
					{
						if( empty($v) && ! empty($meta['allow_none']) )
						{ // empty is ok:
							continue;
						}
						if( ! $Cache->get_by_ID($v, false, false) )
						{
							param_error( $param_name, sprintf( T_('Invalid option &laquo;%s&raquo;.'), $v ) );
							return false;
						}
					}
				}
				break;
		}
	}

	// Check maxlength:
	if( isset($meta['maxlength']) )
	{
		if( evo_strlen($value) > $meta['maxlength'] )
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
					$param_error = sprintf(T_('The value is invalid. It must be smaller than or equal to %s.'), $meta['valid_range']['max']);
				}
				else
				{
					$param_error = sprintf(T_('The value is invalid. It must be greater than or equal to %s.'), $meta['valid_range']['min']);
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
	{
		return;
	}

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


/*
 * $Log$
 * Revision 1.19  2010/08/24 08:20:19  efy-asimo
 * twitter plugin oAuth
 *
 * Revision 1.18  2010/05/13 15:13:13  blueyed
 * Fix E_NOTICE with empty/optional 'note' entry in 'select' entries.
 *
 * Revision 1.17  2010/02/08 17:53:23  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.16  2010/01/27 15:20:08  efy-asimo
 * Change select list to radio button
 *
 * Revision 1.15  2010/01/26 15:49:35  efy-asimo
 * Widget param type radio
 *
 * Revision 1.14  2009/09/26 12:00:43  tblue246
 * Minor/coding style
 *
 * Revision 1.13  2009/09/25 07:32:53  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.12  2009/07/08 02:38:55  sam2kb
 * Replaced strlen & substr with their mbstring wrappers evo_strlen & evo_substr when needed
 *
 * Revision 1.11  2009/07/04 17:42:39  tblue246
 * Translation fix: Default error messages for min/max values (plugin settings) were badly worded.
 *
 * Revision 1.10  2009/05/26 19:31:59  fplanque
 * Plugins can now have Settings that are specific to each blog.
 *
 * Revision 1.9  2009/05/24 21:14:38  fplanque
 * _skin.class.php can now provide skin specific settings.
 * Demo: the custom skin has configurable header colors.
 * The settings can be changed through Blog Settings > Skin Settings.
 * Anyone is welcome to extend those settings for any skin you like.
 *
 * Revision 1.8  2009/05/23 22:49:10  fplanque
 * skin settings
 *
 * Revision 1.7  2009/03/13 02:32:33  fplanque
 * bugfix
 *
 * Revision 1.6  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.5  2008/05/30 19:54:21  blueyed
 * bugfix for corner case, if setting is not an array really
 *
 * Revision 1.4  2008/02/13 11:34:06  blueyed
 * Explicitly call jQuery(), not the shortcut ($())
 *
 * Revision 1.3  2008/01/21 09:35:32  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/09/03 23:45:56  blueyed
 * Use always the array key as value for "select" settings.
 *
 * Revision 1.1  2007/06/25 11:00:42  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.48  2007/06/19 20:40:26  fplanque
 * renamed generic functions to autoform_*
 *
 * Revision 1.47  2007/06/19 18:47:27  fplanque
 * Nuked unnecessary Param (or I'm missing something badly :/)
 *
 * Revision 1.46  2007/06/19 00:03:26  fplanque
 * doc / trying to make sense of automatic settings forms generation.
 *
 * Revision 1.45  2007/04/26 00:11:08  fplanque
 * (c) 2007
 *
 * Revision 1.44  2007/04/02 20:32:57  blueyed
 * Commented out block that caused problems
 *
 * Revision 1.43  2007/01/23 08:57:36  fplanque
 * decrap!
 *
 * Revision 1.42  2006/12/22 22:36:07  blueyed
 * Fixed selecting selected "None" option in "multiple" selects
 *
 * Revision 1.41  2006/12/22 22:29:35  blueyed
 * Support for "multiple" attribute in SELECT elements, especially for GetDefault(User)Settings plugin callback
 *
 * Revision 1.40  2006/12/10 12:36:58  blueyed
 * passthrough "rows" and "maxlength" attributes to input elements
 *
 * Revision 1.39  2006/12/09 01:55:36  fplanque
 * feel free to fill in some missing notes
 * hint: "login" does not need a note! :P
 *
 * Revision 1.38  2006/12/05 02:34:05  blueyed
 * Fix for "Minor refactoring".. :/
 *
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
 */
?>
