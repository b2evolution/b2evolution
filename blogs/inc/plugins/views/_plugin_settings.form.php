<?php
/**
 * Form to edit settings of a plugin.
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

global $edited_plugin_name, $edited_plugin_shortdesc, $edited_plugin_priority, $edited_plugin_code, $edited_plugin_apply_rendering;
global $admin_url;

load_funcs('plugins/_plugin.funcs.php');


$Form = new Form( NULL, 'pluginsettings_checkchanges' );

// Info button:
$Form->global_icon( T_('Display info'), 'info', regenerate_url( 'action,plugin_class', 'action=info&amp;plugin_class='.$edit_Plugin->classname ) );

// Close button:
$Form->global_icon( T_('Cancel edit!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', '',
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

	$Form->add_crumb( 'plugin' );
	$Form->hidden_ctrl();
	$Form->hidden( 'plugin_ID', $edit_Plugin->ID );


// --------------------------- INFO ---------------------------
$Form->begin_fieldset( T_('Plugin info'), array( 'class' => 'clear' ) );
	// Name:
	$Form->text_input( 'edited_plugin_name', $edited_plugin_name, 25, T_('Name'), '', array('maxlength' => 255) );
	// Desc:
	$Form->text_input( 'edited_plugin_shortdesc', $edited_plugin_shortdesc, 50, T_('Short desc'), '', array('maxlength' => 255) );
	// Links to external manual (dh> has been removed from form's global_icons before by fp, but is very useful IMHO):
	if( $edit_Plugin->get_help_link('$help_url') )
	{
		$Form->info( T_('Help'), $edit_Plugin->get_help_link('$help_url').' '.$edit_Plugin->get_help_link('$readme') );
	}
$Form->end_fieldset();


// --------------------------- SETTINGS ---------------------------
if( $edit_Plugin->Settings ) // NOTE: this triggers PHP5 autoloading through Plugin::__get() and therefor the 'isset($this->Settings)' hack in Plugin::GetDefaultSettings() still works, which is good.
{
	load_funcs('plugins/_plugin.funcs.php');

	// We use output buffers here to only display the fieldset if there's content in there
	// (either from PluginSettings or PluginSettingsEditDisplayAfter).
	ob_start();
	foreach( $edit_Plugin->GetDefaultSettings( $tmp_params = array('for_editing'=>true) ) as $l_name => $l_meta )
	{
		// Display form field for this setting:
		autoform_display_field( $l_name, $l_meta, $Form, 'Settings', $edit_Plugin );
	}

	// This can be used add custom input fields or display custom output (e.g. a test link):
	$admin_Plugins->call_method( $edit_Plugin->ID, 'PluginSettingsEditDisplayAfter', $tmp_params = array( 'Form' => & $Form ) );

	$setting_contents = ob_get_contents();
	ob_end_clean();

	if( $setting_contents )
	{
		$Form->begin_fieldset( T_('Plugin settings'), array( 'class' => 'clear' ) );
		echo $setting_contents;
		$Form->end_fieldset();
	}
}


// --------------------------- VARIABLES ---------------------------
$Form->begin_fieldset( T_('Plugin variables').' ('.T_('Advanced').')', array( 'class' => 'clear' ) );
	$Form->text_input( 'edited_plugin_code', $edited_plugin_code, 15, T_('Code'), T_('The code to call the plugin by code. This is also used to link renderer plugins to items.'), array('maxlength'=>32) );
	$Form->text_input( 'edited_plugin_priority', $edited_plugin_priority, 4, T_('Priority'), '', array( 'maxlength' => 4 ) );
	$render_note = get_manual_link('Plugin/apply_rendering');
	if( empty( $edited_plugin_code ) )
	{
		$render_note .= ' '.T_('Note: The plugin code is empty, so this plugin will not work as an "opt-out", "opt-in" or "lazy" renderer.');
	}
	$Form->select_input_array( 'edited_plugin_apply_rendering', $edited_plugin_apply_rendering,
			$admin_Plugins->get_apply_rendering_values(), T_('Apply rendering'), $render_note );
$Form->end_fieldset();


// --------------------------- EVENTS ---------------------------
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


/*
 * $Log$
 * Revision 1.6  2010/02/08 17:53:55  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.5  2010/01/03 12:26:32  fplanque
 * Crumbs for plugins. This is a little bit tough because it's a non standard controller.
 * There may be missing crumbs, especially during install. Please add missing ones when you spot them.
 *
 * Revision 1.4  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.3  2008/01/21 09:35:32  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/09/12 21:00:32  fplanque
 * UI improvements
 *
 * Revision 1.1  2007/06/25 11:00:55  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.36  2007/06/19 20:41:10  fplanque
 * renamed generic functions to autoform_*
 *
 * Revision 1.35  2007/06/19 00:03:26  fplanque
 * doc / trying to make sense of automatic settings forms generation.
 *
 * Revision 1.34  2007/04/26 00:11:12  fplanque
 * (c) 2007
 *
 * Revision 1.33  2007/04/02 20:33:27  blueyed
 * Added (real) link to external manual/wiki
 *
 * Revision 1.31  2007/02/19 23:17:00  blueyed
 * Only display Plugin(User)Settings fieldsets if there is content in them.
 *
 * Revision 1.30  2007/01/23 08:57:36  fplanque
 * decrap!
 *
 * Revision 1.29  2007/01/14 08:21:01  blueyed
 * Optimized "info", "disp_help" and "disp_help_plain" actions by refering to them through classname, which makes Plugins::discover() unnecessary
 *
 * Revision 1.28  2006/12/09 01:55:36  fplanque
 * feel free to fill in some missing notes
 * hint: "login" does not need a note! :P
 *
 * Revision 1.27  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.26  2006/11/14 00:22:13  blueyed
 * doc
 *
 * Revision 1.25  2006/11/09 23:40:57  blueyed
 * Fixed Plugin UserSettings array type editing; Added jquery and use it for AJAHifying Plugin (User)Settings editing of array types
 *
 * Revision 1.24  2006/11/05 18:33:58  fplanque
 * no external links in action icons
 *
 * Revision 1.23  2006/10/30 19:00:36  blueyed
 * Lazy-loading of Plugin (User)Settings for PHP5 through overloading
 *
 * Revision 1.22  2006/10/17 18:36:47  blueyed
 * Priorize NULL for plug_name/plug_shortdesc (localization); minor fixes in this area
 */
?>