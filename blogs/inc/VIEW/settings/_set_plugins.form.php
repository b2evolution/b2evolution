<?php
/**
 * This file implements the UI view for the plugin settings.
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
?>


<fieldset>
	<legend><?php echo T_('Installed plugins') ?></legend>
	<?php
	// if there happened something with a plugin_ID, apply fadeout to the row:
	$highlight_fadeout = empty($edit_Plugin) || ! is_object($edit_Plugin) /* may be error string */ ? array() : array( 'plug_ID'=>array($edit_Plugin->ID) );

	$Results->display( NULL, $highlight_fadeout );
	unset($Results); // free memory

	if( $current_User->check_perm( 'options', 'edit' ) )
	{ // Display action link to reload plugins:
		?>
		<p class="center">
			<a href="admin.php?ctrl=plugins&amp;action=reload_plugins"><?php echo T_('Reload events and codes for installed plugins.') ?></a>
		</p>
		<?php
	}
	?>
</fieldset>


<?php
echo '<p class="center"><a href="'.regenerate_url( 'action', 'action=list_available' ).'">'.T_('Display available plugins.').'</a></p>';


/*
 * $Log$
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
 *
 * Revision 1.34  2006/09/10 21:56:54  smpdawg
 * Fixed parse error
 *
 * Revision 1.33  2006/09/10 19:54:52  blueyed
 * Added CVS Id line
 *
 * Revision 1.32  2006/09/10 19:23:28  blueyed
 * Removed Plugin::code(), ::name(), ::short_desc() and ::long_desc(); Fixes for mt-import.php
 *
 * Revision 1.31  2006/08/05 15:26:06  blueyed
 * Fixed possible E_NOTICE
 *
 * Revision 1.30  2006/07/23 23:01:55  blueyed
 * cleanup
 *
 * Revision 1.29  2006/07/17 01:53:12  blueyed
 * added param to UserSettings::param_Request
 *
 * Revision 1.28  2006/07/10 22:53:38  blueyed
 * Grouping of plugins added, based on a patch from balupton
 *
 * Revision 1.27  2006/07/03 23:35:24  blueyed
 * Performance: Only load AvailablePlugins if used!
 *
 * Revision 1.26  2006/07/03 23:24:57  blueyed
 * info / feedback
 *
 * Revision 1.25  2006/06/26 21:43:18  blueyed
 * small enhancements
 *
 * Revision 1.24  2006/06/22 19:53:06  blueyed
 * minor
 *
 * Revision 1.23  2006/06/20 23:24:14  blueyed
 * Added "order_callback" support for Results; made "name" and "desc" columns in Plugins list sortable
 *
 * Revision 1.22  2006/06/20 00:16:54  blueyed
 * Transformed Plugins table into Results object, so some columns are sortable.
 *
 * Revision 1.21  2006/06/05 23:15:00  blueyed
 * cleaned up plugin help links
 *
 * Revision 1.20  2006/05/30 23:14:54  blueyed
 * Re-enabled internal help, because it has been fixed; link name of available Plugins also to "info" action, because easier to click
 *
 * Revision 1.19  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.18  2006/05/04 06:44:45  blueyed
 * Display version with available plugins.
 *
 * Revision 1.17  2006/05/02 01:47:58  blueyed
 * Normalization
 *
 * Revision 1.16  2006/04/27 19:11:12  blueyed
 * Cleanup; handle broken plugins more decent
 *
 * Revision 1.15  2006/04/19 20:13:52  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.14  2006/04/18 19:37:37  fplanque
 * minor
 *
 * Revision 1.13  2006/04/18 15:16:37  blueyed
 * todo
 *
 * Revision 1.12  2006/04/13 01:36:27  blueyed
 * Added interface method to edit Plugin settings (gets already used by YouTube Plugin)
 *
 * Revision 1.11  2006/04/13 01:23:19  blueyed
 * Moved help related functions back to Plugin class
 *
 * Revision 1.10  2006/04/11 22:28:58  blueyed
 * cleanup
 *
 * Revision 1.9  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>
