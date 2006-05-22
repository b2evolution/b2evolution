<?php
/**
 * Form to edit settings of a plugin.
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
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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

/**
 * @global Plugin
 */
global $edit_Plugin;

/**
 * @global Plugins_admin
 */
global $admin_Plugins;

global $edited_plugin_priority, $edited_plugin_code, $edited_plugin_apply_rendering, $admin_url;

global $inc_path;
require_once $inc_path.'_misc/_plugin.funcs.php';


$Form = & new Form( NULL, 'pluginsettings_checkchanges' );
$Form->hidden_ctrl();

// Help icons, if available:
if( $edit_Plugin->get_help_file() )
{ // README in JS popup:
	$Form->global_icon( T_('Local documentation of the plugin'), 'help',
		url_add_param( $admin_url, 'ctrl=plugins&amp;action=disp_help_plain&amp;plugin_ID='.$edit_Plugin->ID.'#'.$edit_Plugin->classname.'_settings' ), '', array('use_js_popup'=>true, 'id'=>'anchor_help_popup_'.$edit_Plugin->ID) );
}
if( ! empty( $edit_Plugin->help_url ) )
{
	$Form->global_icon( T_('Homepage of the plugin'), 'www', $edit_Plugin->help_url );
}

$Form->global_icon( T_('Cancel edit!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', '',
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

$Form->hidden( 'plugin_ID', $edit_Plugin->ID );

// PluginSettings
if( $edit_Plugin->Settings )
{
	global $inc_path;
	require_once $inc_path.'_misc/_plugin.funcs.php';

	$Form->begin_fieldset( T_('Plugin settings'), array( 'class' => 'clear' ) );

	foreach( $edit_Plugin->GetDefaultSettings() as $l_name => $l_meta )
	{
		display_settings_fieldset_field( $l_name, $l_meta, $edit_Plugin, $Form, 'Settings' );
	}

	$admin_Plugins->call_method( $edit_Plugin->ID, 'PluginSettingsEditDisplayAfter', $tmp_params = array( 'Form' => & $Form ) );

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

if( $edit_Plugin->status != 'enabled' )
{
	echo '<p class="notes">'.T_('Note: the plugin is not enabled.').'</p>';
}

echo '<p>'.T_('Warning: by disabling plugin events you change the behaviour of the plugin! Only change this, if you know what you are doing.').'</p>';

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


/* {{{ Revision log:
 * $Log$
 * Revision 1.12  2006/05/22 20:35:36  blueyed
 * Passthrough some attribute of plugin settings, allowing to use JS handlers. Also fixed submitting of disabled form elements.
 *
 * Revision 1.11  2006/04/21 16:58:11  blueyed
 * Add warning to "disable plugin events".
 *
 * Revision 1.10  2006/04/19 20:13:52  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.9  2006/04/13 01:23:19  blueyed
 * Moved help related functions back to Plugin class
 *
 * Revision 1.8  2006/04/05 19:16:34  blueyed
 * Refactored/cleaned up help link handling: defaults to online-manual-pages now.
 *
 * Revision 1.7  2006/03/15 23:35:38  blueyed
 * "broken" state support for Plugins: set/unset state, allowing to un-install and display error in "edit_settings" action
 *
 * Revision 1.6  2006/03/15 21:02:07  blueyed
 * Display event status for all plugins with $admin_Plugins
 *
 * Revision 1.5  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.4  2006/03/01 01:07:43  blueyed
 * Plugin(s) polishing
 *
 * Revision 1.3  2006/02/27 16:57:12  blueyed
 * PluginUserSettings - allows a plugin to store user related settings
 *
 * Revision 1.2  2006/02/24 23:38:55  blueyed
 * fixes
 *
 * Revision 1.1  2006/02/24 23:02:16  blueyed
 * Added _set_plugins_editsettings.form VIEW
 *
 * }}}
 */
?>