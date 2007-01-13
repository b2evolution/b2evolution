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
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author blueyed: Daniel HAHLER.
 *
 * @todo link to help urls
 * @version $Id$
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

global $inc_path, $edit_Plugin;
require_once $inc_path.'_misc/_plugin.funcs.php';


$Results = new Results( '
	SELECT plug_status, plug_ID, plug_priority, plug_code, plug_apply_rendering FROM T_plugins',
	'plug_', '-A-' /* by name */, NULL /* no limit */ );

$Results->Cache = & $admin_Plugins;

/*
// TODO: dh> make this an optional view (while also removing the "group" col then)?
//           It's nice to have, but does not allow sorting by priority really..
// fp> Yes, 90% pf the time, seing a grouped list is what we'd need most. (sorted by priority within each group by default)
// in the remaining 10% we need an overall priority view because a plugin is not in the right group or more problematic: in multiple groups
// BTW, when does the priority apply besides for rendering? I think we should document that at the bottom of the screen. ("Apply" needs an short explanation too).
// BTW, "category" or "class" or "family" would be a better name for plugin "group"s. "group" sounds arbitrary. I think this shoudl convey the idea of a "logical grouping" by "main" purpose of the plugin.
//
$Results->group_by_obj_prop = 'group';
$Results->grp_cols[] = array(
		'td' => '% ( empty( {Obj}->group ) && $this->current_group_count[0] > 1 ? T_(\'Un-Grouped\') : {Obj}->group ) %',
		'td_colspan' => 0,
	);
*/

$Results->title = T_('Installed plugins');

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

if( count($admin_Plugins->get_plugin_groups()) )
{
	/*
	 * PLUGIN GROUP TD:
	 */
	function plugin_results_group_order_callback( $a, $b, $order )
	{
		global $admin_Plugins;

		$r = $admin_Plugins->sort_Plugin_group( $a->ID, $b->ID );
		if( $order == 'DESC' ) { $r = -$r; }
		return $r;
	}
	$Results->cols[] = array(
			'th' => T_('Group'),
			'order_objects_callback' => 'plugin_results_group_order_callback',
			'td' => '% {Obj}->group %',
		);
}

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
 * APPLY RENDERING TD:
 */
$apply_rendering_values = $admin_Plugins->get_apply_rendering_values(true); // with descs
function plugin_results_td_apply_rendering($apply_rendering)
{
	global $admin_Plugins, $apply_rendering_values;

	return '<span title="'.format_to_output( $apply_rendering_values[$apply_rendering], 'htmlattr' )
			.'">'.$apply_rendering.'</span>';
}
$Results->cols[] = array(
		'th' => T_('Apply'),
		'th_title' => T_('When should rendering apply?'),
		'order' => 'plug_apply_rendering',
		'td' => '%plugin_results_td_apply_rendering( \'$plug_apply_rendering$\' )%',
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
		'order_objects_callback' => 'plugin_results_name_order_callback',
	);

/*
 * HELP TD:
 */
function plugin_results_td_help( $Plugin )
{
	return action_icon( T_('Display info'), 'info', regenerate_url( 'action,plugin_ID', 'action=info&amp;plugin_ID='.$Plugin->ID ) )
		// Help icons, if available:
		.$Plugin->get_help_link('$help_url')
		.' '.$Plugin->get_help_link('$readme');
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
	$r = '';
	if( $Plugin->status == 'enabled' )
	{
		$r .= action_icon( T_('Disable the plugin!'), 'deactivate', 'admin.php?ctrl=plugins&amp;action=disable_plugin&amp;plugin_ID='.$Plugin->ID );
	}
	elseif( $Plugin->status != 'broken' )
	{
		$r .= action_icon( T_('Enable the plugin!'), 'activate', 'admin.php?ctrl=plugins&amp;action=enable_plugin&amp;plugin_ID='.$Plugin->ID );
	}
	$r .= $Plugin->get_edit_settings_link();
	$r .= action_icon( T_('Un-install this plugin!'), 'delete', 'admin.php?ctrl=plugins&amp;action=uninstall&amp;plugin_ID='.$Plugin->ID );
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
	$Results->global_icon( T_('Reload events and codes for installed plugins.'), 'reload', regenerate_url( 'action', 'action=reload_plugins' ), T_('Reload plugins'), 3, 4 );
}

$Results->global_icon( T_('Install new plugin...'), 'new', regenerate_url( 'action', 'action=list_available' ), T_('Install new'), 3, 4 );



// if there happened something with a plugin_ID, apply fadeout to the row:
$highlight_fadeout = empty($edit_Plugin) || ! is_object($edit_Plugin) /* may be error string */ ? array() : array( 'plug_ID'=>array($edit_Plugin->ID) );

$Results->display( NULL, $highlight_fadeout );

unset($Results); // free memory


/*
 * $Log$
 * Revision 1.47  2007/01/13 22:28:13  fplanque
 * doc
 *
 * Revision 1.46  2007/01/13 19:37:39  blueyed
 * Removed grouping by group from installed plugins. It should be an alternative view probably, if it is useful at all
 *
 * Revision 1.45  2007/01/13 19:19:58  blueyed
 * Group by plugin group; removed the "Group" column therefor
 *
 * Revision 1.44  2007/01/07 18:42:35  fplanque
 * cleaned up reload/refresh icons & links
 *
 * Revision 1.43  2006/12/20 23:07:23  blueyed
 * Moved list of available plugins to separate sub-screen/form
 *
 * Revision 1.42  2006/12/16 04:07:10  fplanque
 * visual cleanup
 *
 * Revision 1.41  2006/12/05 05:41:42  fplanque
 * created playground for skin management
 *
 * Revision 1.40  2006/11/30 05:43:39  blueyed
 * Moved Plugins::discover() to Plugins_admin::discover(); Renamed Plugins_no_DB to Plugins_admin_no_DB (and deriving from Plugins_admin)
 *
 * Revision 1.39  2006/11/30 00:30:33  blueyed
 * Some minor memory optimizations regarding "Plugins" screen
 *
 * Revision 1.38  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.37  2006/11/05 18:21:08  fplanque
 * This is about the 4th time I fix the CSS for the plugins list :(
 *
 * Revision 1.36  2006/10/26 21:24:14  blueyed
 * Do not display "reload events" links, if no perms
 *
 * Revision 1.35  2006/10/08 19:50:10  blueyed
 * Re-enabled sorting plugins by name, group and desc again
 */
?>
