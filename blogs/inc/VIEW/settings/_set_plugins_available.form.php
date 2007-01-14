<?php
/**
 * This file implements the UI view for the available plugins.
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

$Table = & new Table();

$Table->title = T_('Plugins available for installation');

$Table->global_icon( T_('Cancel install!'), 'close', regenerate_url(), T_('Cancel'), 3, 4 );

$Table->cols = array(
		array( 'th' => T_('Plugin') ),
		array( 'th' => T_('Description') ),
		array( 'th' => T_('Version') ),
		array( 'th' => T_('Help'),
					 'td_class' => 'nowrap' ),
		array( 'th' => T_('Actions'),
					 'td_class' => 'nowrap' ),
	);

$Table->display_init();

$Table->display_list_start();

// TITLE / COLUMN HEADERS:
$Table->display_head();

// BODY START:
$Table->display_body_start();

if( empty($AvailablePlugins) || ! is_a( $AvailablePlugins, 'Plugins_admin_no_DB' ) )
{ // (may have been instantiated for action 'info')
	load_class('_misc/_plugins_admin_no_db.class.php');
	$AvailablePlugins = & new Plugins_admin_no_DB(); // do not load registered plugins/events from DB
	$AvailablePlugins->discover();
}

// Sort the plugins by group
$AvailablePlugins->sort('group');
// Grouping
$current_group = false; // False so it does the header once
$current_sub_group = '';

$number_of_groups = count($AvailablePlugins->get_plugin_groups());

while( $loop_Plugin = & $AvailablePlugins->get_next() )
{

	if( $loop_Plugin->group !== $current_group && $number_of_groups )
	{ // Reason why $current_group is false
		$current_group = $loop_Plugin->group;
		$current_sub_group = '';
		?>
		<tr class="group">
			<td colspan="5" class="first"><?php
			if( $current_group == '' || $current_group == 'Un-Grouped' )
			{
				echo T_('Un-Classified');
			}
			else
			{
				echo $current_group;
			}
			?></td>
		</tr>
		<?php
	}

	if( $loop_Plugin->sub_group != $current_sub_group )
	{
		$current_sub_group = $loop_Plugin->sub_group;
		?>
		<tr class="PluginsSubGroup">
			<th colspan="5"><?php echo $current_sub_group; ?></th>
		</tr>
		<?php
	}

	// fp> TODO: support for table.grouped tr.PluginsSubGroup td.firstcol (maybe... subgroups seem crazy anyway - where does it stop?).
	$Table->display_line_start();

		$Table->display_col_start();
	  ?>
			<strong><a title="<?php echo T_('Display info') ?>" href="<?php echo regenerate_url( 'action,plugin_class', 'action=info&amp;plugin_class='.$loop_Plugin->classname) . '">'
	    .format_to_output($loop_Plugin->name); ?></a></strong>
		<?php
		$Table->display_col_end();

		$Table->display_col_start();
			echo format_to_output($loop_Plugin->short_desc);
			/*
			// Available events:
			$registered_events = implode( ', ', $AvailablePlugins->get_registered_events( $loop_Plugin ) );
			if( empty($registered_events) )
			{
				$registered_events = '-';
			}
			echo '<span class="advanced_info notes"><br />'.T_('Registered events:').' '.$registered_events.'</span>';
			*/
		$Table->display_col_end();

		$Table->display_col_start();
			$clean_version = preg_replace( array('~^(CVS\s+)?\$'.'Revision:\s*~i', '~\s*\$$~'), '', $loop_Plugin->version );

			echo format_to_output($clean_version);
		$Table->display_col_end();

		$Table->display_col_start();
			echo action_icon( T_('Display info'), 'info', regenerate_url( 'action,plugin_class', 'action=info&amp;plugin_class='.$loop_Plugin->classname ) );
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
		$Table->display_col_end();

		$Table->display_col_start();
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
		$Table->display_col_end();

	$Table->display_line_end();

	flush();
	// free memory:
	$AvailablePlugins->unregister($loop_Plugin);
}

// BODY END:
$Table->display_body_end();


$Table->display_list_end();

/*
 * $Log$
 * Revision 1.10  2007/01/14 08:21:01  blueyed
 * Optimized "info", "disp_help" and "disp_help_plain" actions by refering to them through classname, which makes Plugins::discover() unnecessary
 *
 * Revision 1.9  2007/01/13 22:38:13  fplanque
 * normalized
 *
 * Revision 1.8  2007/01/11 02:25:06  fplanque
 * refactoring of Table displays
 * body / line / col / fadeout
 *
 * Revision 1.7  2007/01/09 22:42:23  fplanque
 * tfoot
 *
 * Revision 1.6  2007/01/09 00:49:04  blueyed
 * todo
 *
 * Revision 1.5  2007/01/09 00:29:52  blueyed
 * Fixed HTML: wrong "</foot></tfoot>"
 *
 * Revision 1.4  2007/01/08 23:44:19  fplanque
 * inserted Table widget
 * WARNING: this has nothing to do with ComponentWidgets...
 * (except that I'm gonna need the Table Widget when handling the ComponentWidgets :>
 *
 * Revision 1.3  2007/01/07 18:42:35  fplanque
 * cleaned up reload/refresh icons & links
 *
 * Revision 1.2  2006/12/20 23:46:01  blueyed
 * Part of last change to _set_plugins.form.php has been lost while splitting
 *
 * Revision 1.1  2006/12/20 23:07:23  blueyed
 * Moved list of available plugins to separate sub-screen/form
 *
 */
?>
