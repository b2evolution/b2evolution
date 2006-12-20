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

		$count = 0;
		while( $loop_Plugin = & $AvailablePlugins->get_next() )
		{

			if( $loop_Plugin->group !== $current_group && $number_of_groups )
			{ // Reason why $current_group is false
				$current_group = $loop_Plugin->group;
				$current_sub_group = '';
				$count = 0;
				?>
				<tr class="PluginsGroup">
					<th colspan="5"><?php
					if( $current_group == '' || $current_group == 'Un-Grouped' )
					{
						echo T_('Un-Classified');
					}
					else
					{
						echo $current_group;
					}
					?></th>
				</tr>
				<?php
			}

			if( $loop_Plugin->sub_group != $current_sub_group )
			{
				$current_sub_group = $loop_Plugin->sub_group;
				$count = 0;
				?>
				<tr class="PluginsSubGroup">
					<th colspan="5"><?php echo $current_sub_group; ?></th>
				</tr>
				<?php
			}

		?>
		<tr class="<?php echo (($count++ % 2) ? 'odd' : 'even');
			echo ( $current_sub_group != '' ? ' PluginsSubGroup' : ' PluginsGroup' ); ?>">

			<td class="firstcol">
				<strong><a title="<?php echo T_('Display info') ?>" href="<?php echo regenerate_url( 'action,plugin_ID', 'action=info&amp;plugin_ID='.$loop_Plugin->ID) . '">'
        .format_to_output($loop_Plugin->name); ?></a></strong>
			</td>
			<td>
				<?php
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
				?>
			</td>
			<td>
				<?php
				$clean_version = preg_replace( array('~^(CVS\s+)?\$'.'Revision:\s*~i', '~\s*\$$~'), '', $loop_Plugin->version );

				echo format_to_output($clean_version);
				?>
			</td>
			<td class="nowrap">
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
			<td class="nowrap lastcol">
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
		// free memory:
		$AvailablePlugins->unregister($loop_Plugin);
		}
		?>
		</tbody>
	</table>
</fieldset>


<?php
/*
 * $Log$
 * Revision 1.2  2006/12/20 23:46:01  blueyed
 * Part of last change to _set_plugins.form.php has been lost while splitting
 *
 * Revision 1.1  2006/12/20 23:07:23  blueyed
 * Moved list of available plugins to separate sub-screen/form
 *
 */
?>
