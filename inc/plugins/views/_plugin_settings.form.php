<?php
/**
 * Form to edit settings of a plugin.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
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

global $edited_plugin_name, $edited_plugin_shortdesc, $edited_plugin_priority, $edited_plugin_code;
global $admin_url;

load_funcs('plugins/_plugin.funcs.php');


$Form = new Form( NULL, 'pluginsettings_checkchanges' );

// Restore defaults button:
$Form->global_icon( T_('Restore defaults'), 'reload', regenerate_url( 'action,plugin_class', 'action=default_settings&amp;plugin_ID=' . $edit_Plugin->ID . '&amp;crumb_plugin=' . get_crumb( 'plugin' ) ), T_('Restore defaults'),5,4,
	array(
			'onclick'=>'if (!confirm(\''.TS_('Are you sure you want to restore the default settings? This cannot be undone!').'\')) { cancelClick(event); }',
		)
	);

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
		$Form->info( T_('Help'), $edit_Plugin->get_help_link('$help_url') );
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
$Form->end_fieldset();


// --------------------------- EVENTS ---------------------------
$Form->begin_fieldset( T_('Plugin events').' ('.T_('Advanced').') '.get_icon( 'collapse', 'imgtag', array( 'id' => 'clickimg_pluginevents' ) ),
											 array('legend_params' => array( 'onclick' => 'toggle_clickopen(\'pluginevents\')') )
											 );
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
		array( 'type' => 'submit', 'name' => 'actionArray[update_settings]', 'value' => T_('Save Changes!'), 'class' => 'SaveButton' ),
		) );
}
$Form->end_form();

?>