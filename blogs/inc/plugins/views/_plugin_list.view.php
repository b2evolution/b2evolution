<?php
/**
 * This file implements the UI view for the plugin settings.
 *
 * @todo dh> Move plugin's group name to DB setting?!
 * fp> this would actually make more sense than renaming the name and code of plugins, but it's still complexity we don't really need.
 * fp> When you reach that level of needs you're already a plugin maniac/developper and you hack the plugin php code anyway.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @author fplanque: Francois PLANQUE.
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id: _plugin_list.view.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var User
 */
global $current_User;
/**
 * @var Plugins
 */
global $admin_Plugins;

global $Session;

$fadeout_id = $Session->get('fadeout_id');

load_funcs('plugins/_plugin.funcs.php');

$SQL = new SQL();
$SQL->SELECT( 'plug_status, plug_ID, plug_priority, plug_code' );
$SQL->FROM( 'T_plugins' );

$Results = new Results( $SQL->get(), 'plug_', '--A' /* by name */, 0 /* no limit */ );

$Results->Cache = & $admin_Plugins;

/*
// TODO: dh> make this an optional view (while also removing the "group" col then)?
//           It's nice to have, but does not allow sorting by priority really..
// fp> Yes, 90% of the time, seing a grouped list is what we'd need most. (sorted by priority within each group by default)
// in the remaining 10% we need an overall priority view because a plugin is not in the right group or more problematic: in multiple groups
// BTW, when does the priority apply besides for rendering? I think we should document that at the bottom of the screen. ("Apply" needs an short explanation too).
// BTW, "category" or "class" or "family" would be a better name for plugin "group"s. "group" sounds arbitrary. I think this should convey the idea of a "logical grouping" by "main" purpose of the plugin.
//
$Results->group_by_obj_prop = 'group';
$Results->grp_cols[] = array(
		'td' => '% ( empty( {Obj}->group ) && $this->current_group_count[0] > 1 ? T_(\'Un-Grouped\') : {Obj}->group ) %',
		'td_colspan' => 0,
	);
*/

$Results->title = T_('Installed plugins').get_manual_link('installed_plugins');

if( count( $admin_Plugins->get_plugin_groups() ) )
{
	/*
	 * Grouping params:
	 */
	$Results->group_by_obj_prop = 'group';


	/*
	 * Group columns:
	 */
	$Results->grp_cols[] = array(
			'td_colspan' => 0,
			'td' => '% {Obj}->group %',
		);
}

/*
 * STATUS TD:
 */
function plugin_results_td_status( $plug_status, $plug_ID )
{
	global $admin_Plugins;

	if( $plug_status == 'enabled' )
	{
		return get_icon('enabled', 'imgtag', array('title'=>T_('The plugin is enabled.')) );
	}
	elseif( $plug_status == 'broken' )
	{
		return get_icon('warning', 'imgtag', array(
			'title' => T_('The plugin is broken.')
				.// Display load error from Plugins::register() (if any):
				( isset( $admin_Plugins->plugin_errors[$plug_ID] )
					&& ! empty($admin_Plugins->plugin_errors[$plug_ID]['register'])
					? ' '.$admin_Plugins->plugin_errors[$plug_ID]['register']
					: '' )
			) );
	}
	elseif( $plug_status == 'install' )
	{
		return get_icon('disabled', 'imgtag', array('title'=>T_('The plugin is not installed completely.')) );
	}
	else
	{
		return get_icon('disabled', 'imgtag', array('title'=>T_('The plugin is disabled.')) );
	}
}
$Results->cols[] = array(
		'th' => /* TRANS: shortcut for enabled */ T_('En'),
		'th_title' => T_('Enabled'),
		'order' => 'plug_status',
		'td' => '%plugin_results_td_status( \'$plug_status$\', $plug_ID$ )%',
		'td_class' => 'center',
	);

/*
 * PLUGIN NAME TD:
 */
function plugin_results_td_name( $Plugin )
{
	global $current_User;
	$r = '<strong>'.$Plugin->name.'</strong>';

	if( $current_User->check_perm( 'options', 'edit', false ) )
	{ // Wrap in "edit settings" link:
		$r = '<a href="'.regenerate_url( '', 'action=edit_settings&amp;plugin_ID='.$Plugin->ID )
			.'" title="'.T_('Edit plugin settings!').'">'.$r.'</a>';
	}
	return $r;
}
function plugin_results_name_order_callback( $a, $b, $order )
{
	$r = strcasecmp( $a->name, $b->name );
	if( $order == 'DESC' ) { $r = -$r; }
	return $r;
}
$Results->cols[] = array(
		'th' => T_('Plugin'),
		'order_objects_callback' => 'plugin_results_name_order_callback',
		'td' => '% plugin_results_td_name( {Obj} ) %',
	);

/*
 * PRIORITY TD:
 */
$Results->cols[] = array(
		'th' => T_('Priority'),
		'order' => 'plug_priority',
		'td' => '$plug_priority$',
		'td_class' => 'right',
	);

/*
 * PLUGIN DESCRIPTION TD:
 */
function plugin_results_desc_order_callback( $a, $b, $order )
{
	$r = strcasecmp( $a->short_desc, $b->short_desc );
	if( $order == 'DESC' ) { $r = -$r; }
	return $r;
}
$Results->cols[] = array(
		'th' => T_('Description'),
		'td' => '% {Obj}->short_desc %',
		'order_objects_callback' => 'plugin_results_desc_order_callback',
	);

/*
 * PLUGIN CODE TD:
 */
$Results->cols[] = array(
		'th' => /* TRANS: Code of a plugin */ T_('Code'),
		'th_title' => T_('The code to call the plugin by code (SkinTag) or as Renderer.'),
		'order' => 'plug_code',
		'td' => '% {Obj}->code %',
	);

/*
 * HELP TD:
 */
function plugin_results_td_help( $Plugin )
{
	return action_icon( T_('Display info'), 'info', regenerate_url( 'action,plugin_class', 'action=info&amp;plugin_class='.$Plugin->classname ) )
		// Help icons, if available:
		.$Plugin->get_help_link('$help_url');
}
$Results->cols[] = array(
		'th' => T_('Help'),
		'td_class' => 'nowrap',
		'td' => '% plugin_results_td_help( {Obj} ) %',
	);

/*
 * ACTIONS TD:
 */
function plugin_results_td_actions($Plugin)
{
	global $dispatcher;

	$r = '';
	if( $Plugin->status == 'enabled' )
	{
		$r .= action_icon( T_('Disable the plugin!'), 'deactivate', $dispatcher.'?ctrl=plugins&amp;action=disable_plugin&amp;plugin_ID='.$Plugin->ID.'&amp;'.url_crumb('plugin') );
	}
	elseif( $Plugin->status != 'broken' )
	{
		$r .= action_icon( T_('Enable the plugin!'), 'activate', $dispatcher.'?ctrl=plugins&amp;action=enable_plugin&amp;plugin_ID='.$Plugin->ID.'&amp;'.url_crumb('plugin') );
	}
	$r .= $Plugin->get_edit_settings_link();
	$r .= action_icon( T_('Un-install this plugin!'), 'delete', $dispatcher.'?ctrl=plugins&amp;action=uninstall&amp;plugin_ID='.$Plugin->ID.'&amp;'.url_crumb('plugin') );
	return $r;
}
if( $current_User->check_perm( 'options', 'edit', false ) )
{
	$Results->cols[] = array(
			'th' => T_('Actions'),
			'td' => '% plugin_results_td_actions( {Obj} ) %',
			'td_class' => 'shrinkwrap',
		);
}

// Action icons:

if( $current_User->check_perm( 'options', 'edit' ) )
{ // Display action link to reload plugins:
	$Results->global_icon( T_('Reload events and codes for installed plugins.'), 'reload', regenerate_url( 'action', 'action=reload_plugins' ).'&amp;'.url_crumb('plugin'), T_('Reload plugins'), 3, 4 );
}

$Results->global_icon( T_('Install new plugin...'), 'new', regenerate_url( 'action', 'action=list_available' ), T_('Install new'), 3, 4 );



// if there happened something with a plugin, apply fadeout to the row:
$highlight_fadeout = empty($fadeout_id) /* may be error string */ ? array() : array( 'plug_ID'=>array($fadeout_id) );

$Results->display( NULL, $highlight_fadeout );

unset($Results); // free memory

//Flush fadeout
$Session->delete( 'fadeout_id');

?>