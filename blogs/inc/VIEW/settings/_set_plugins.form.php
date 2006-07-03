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
 * @author fplanque: Francois PLANQUE.
 * @author blueyed: Daniel HAHLER.
 *
 * @todo link to help urls
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
/**
 * @var Plugins_no_DB
 */
global $AvailablePlugins;
/**
 * @var UserSettings
 */
global $UserSettings;

global $inc_path, $edit_Plugin;
require_once $inc_path.'_misc/_plugin.funcs.php';


// Store/retrieve order from UserSettings:
$UserSettings->param_Request( 'results_plug_order', 'string', '--A', true );


$Results = new Results( '
	SELECT plug_status, plug_ID, plug_priority, plug_code, plug_apply_rendering FROM T_plugins',
	'plug_', '--A', 0 /* no limit */ );

$Results->Cache = & $admin_Plugins;

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
$Results->cols[0] = array(
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
$Results->cols[1] = array(
		'th' => T_('Plugin'),
		'order_callback' => 'plugin_results_name_order_callback',
		'td' => '% plugin_results_td_name( {Obj} ) %',
	);

/*
 * PRIORITY TD:
 */
$Results->cols[2] = array(
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
$Results->cols[3] = array(
		'th' => T_('Apply'),
		'th_title' => T_('When should rendering apply?'),
		'order' => 'plug_apply_rendering',
		'td' => '%plugin_results_td_apply_rendering( \'$plug_apply_rendering$\' )%',
	);

/*
 * PLUGIN CODE TD:
 */
$Results->cols[4] = array(
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
$Results->cols[5] = array(
		'th' => T_('Description'),
		'td' => '% {Obj}->short_desc %',
		'order_callback' => 'plugin_results_name_order_callback',
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
$Results->cols[6] = array(
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
	$Results->cols[7] = array(
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
	$highlight_fadeout = empty($edit_Plugin) ? array() : array( 'plug_ID'=>array($edit_Plugin->ID) );

	$Results->display( NULL, $highlight_fadeout );
	?>
	<p class="center">
		<a href="admin.php?ctrl=plugins&amp;action=reload_plugins"><?php echo T_('Reload events and codes for installed plugins.') ?></a>
	</p>
</fieldset>


<?php
// "Show available plugins" if currently hidden
if( ! $UserSettings->get('plugins_disp_avail') )
{
	echo '<p class="center"><a href="'.regenerate_url( 'plugins_disp_avail', 'plugins_disp_avail=1' ).'">'.T_('Display available plugins.').'</a></p>';
	return;
}

if( empty($AvailablePlugins) || ! is_a( $AvailablePlugins, 'Plugins_no_DB' ) ) // may have been instantiated for action='info'
{
	// Discover available plugins:
	$AvailablePlugins = & new Plugins_no_DB(); // do not load registered plugins/events from DB
	$AvailablePlugins->discover();
	$AvailablePlugins->sort('name');
}
?>

<fieldset>
	<legend><?php echo T_('Available plugins') ?></legend>
	<div class="right_icons"><?php
	// "Hide available plugins":
	echo action_icon( T_('Hide available plugins'), 'close', regenerate_url( 'plugins_disp_avail', 'plugins_disp_avail=0' ) )
	?></div>

	<table class="grouped" cellspacing="0">
		<tbody>
		<tr>
			<th class="firstcol"><?php echo T_('Plugin') ?></th>
			<th><?php echo T_('Description') ?></th>
			<th><?php echo T_('Version') ?></th>
			<th><?php echo T_('Help') ?></th>
			<th class="lastcol"><?php echo T_('Actions') ?></th>
		</tr>
		<?php
		$AvailablePlugins->restart();	 // make sure iterator is at start position
		$count = 0;
		while( $loop_Plugin = & $AvailablePlugins->get_next() )
		{
		?>
		<tr class="<?php echo (($count++ % 2) ? 'odd' : 'even') ?>">
			<td class="firstcol">
				<strong><a title="<?php echo T_('Display info') ?>" href="<?php echo regenerate_url( 'action,plugin_ID', 'action=info&amp;plugin_ID='.$loop_Plugin->ID ) ?>"><?php $loop_Plugin->name(); ?></a></strong>
			</td>
			<td>
				<?php
				$loop_Plugin->short_desc();
				/*
				// Available events:
				$registered_events = implode( ', ', $AvailablePlugins->get_registered_events( $loop_Plugin ) );
				if( empty($registered_events) )
				{
					$registered_events = '-';
				}
				echo '<span class="advanced_info notes"><br />'.T_('Registered events:').' '.$registered_events.'</span>';
				*/
				?>
			</td>
			<td>
				<?php
				$clean_version = preg_replace( array('~^(CVS\s+)?\$'.'Revision:\s*~i', '~\s*\$$~'), '', $loop_Plugin->version );

				echo format_to_output($clean_version);
				?>
			</td>
			<td>
				<?php
				echo action_icon( T_('Display info'), 'info', regenerate_url( 'action,plugin_ID', 'action=info&amp;plugin_ID='.$loop_Plugin->ID ) );
				// Help icons, if available:
				$help_icons = array();
				if( $help_external = $loop_Plugin->get_help_link() )
				{
					$help_icons[] = $help_external;
				}
				if( $help_internal = $loop_Plugin->get_help_link('$readme') )
				{
					$help_icons[] = $help_internal;
				}
				if( ! empty($help_icons) )
				{
					echo ' '.implode( ' ', $help_icons );
				}
				?>
			</td>
			<td class="lastcol">
				<?php
				$registrations = $admin_Plugins->count_regs($loop_Plugin->classname);

				if( $current_User->check_perm( 'options', 'edit', false )
				    && ( ! isset( $loop_Plugin->number_of_installs )
				         || $registrations < $loop_Plugin->number_of_installs ) )
				{ // number of installations are not limited or not reached yet and user has "edit options" perms
					?>
					[<a href="admin.php?ctrl=plugins&amp;action=install&amp;plugin=<?php echo rawurlencode($loop_Plugin->classname) ?>"><?php
						echo T_('Install');
						if( $registrations )
						{	// This plugin is already installed
							echo ' #'.($registrations+1);
						}
						?></a>]
					<?php
				}
				?>
			</td>
		</tr>
		<?php
		flush();
		}
		?>
		</tbody>
	</table>
</fieldset>


<?php
/*
 * $Log$
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
