<?php
/**
 * This file implements the UI view for the plugin settings.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
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

global $inc_path;
require_once $inc_path.'_misc/_plugin.funcs.php';

?>
<fieldset class="clear"><!-- "clear" to fix Konqueror (http://bugs.kde.org/show_bug.cgi?id=117509) -->
	<legend><?php echo T_('Installed plugins') ?></legend>
	<table class="grouped" cellspacing="0">
		<thead>
		<tr>
			<th class="firstcol"><?php echo T_('Enabled') ?></th>
			<th><?php echo T_('Plugin') ?></th>
			<th><?php echo T_('Priority') ?></th>
			<th title="<?php echo T_('When should rendering apply?') ?>"><?php echo T_('Apply') ?></th>
			<th class="advanced_info" title="<?php echo T_('The code to call the plugin by code (SkinTag) or as Renderer.') ?>"><?php echo /* TRANS: Code of a plugin */ T_('Code') ?></th>
			<th><?php echo T_('Description') ?></th>
			<th><?php echo T_('Help') ?></th>
			<?php
			if( $current_User->check_perm( 'options', 'edit', false ) )
			{ ?>
				<th class="lastcol"><?php echo T_('Actions') ?></th>
				<?php
			} ?>
		</tr>
		</thead>
		<tbody>
		<?php
		$apply_rendering_values = $admin_Plugins->get_apply_rendering_values(true); // with descs

		$admin_Plugins->restart();	 // make sure iterator is at start position
		$count = 0;
		while( $loop_Plugin = & $admin_Plugins->get_next() )
		{
		?>
		<tr class="<?php echo (($count++ % 2) ? 'odd' : 'even') ?>">
			<td class="firstcol shrinkwrap">
			<?php
			if( $loop_Plugin->status == 'enabled' )
			{
				echo get_icon('enabled', 'imgtag', array('title'=>T_('The plugin is enabled.')) );
			}
			elseif( $loop_Plugin->status == 'broken' )
			{
				echo get_icon('warning', 'imgtag', array('title'=>T_('The plugin is broken.')) );
			}
			else
			{
				echo get_icon('disabled', 'imgtag', array('title'=>T_('The plugin is disabled.')) );
			}
			?>
			</td>
			<td>
				<a href="admin.php?ctrl=plugins&amp;action=edit_settings&amp;plugin_ID=<?php echo $loop_Plugin->ID ?>" title="<?php echo T_('Edit plugin settings!') ?>">
				<?php	$loop_Plugin->name(); ?>
				</a>
			</td>
			<td class="right"><?php echo $loop_Plugin->priority; ?></td>
			<td><span title="<?php echo format_to_output( $apply_rendering_values[$loop_Plugin->apply_rendering], 'htmlattr' ) ?>"><?php echo $loop_Plugin->apply_rendering; ?></span></td>
			<td>
				<?php $loop_Plugin->code() ?>
			</td>
			<td>
				<?php $loop_Plugin->short_desc(); ?>
			</td>
			<td class="nowrap">
				<?php
				echo action_icon( T_('Display info'), 'info', regenerate_url( 'action,plugin_ID', 'action=info&amp;plugin_ID='.$loop_Plugin->ID ) );
				// Help icons, if available:
				echo get_plugin_help_link($loop_Plugin)
					.' '.get_plugin_README_link($loop_Plugin);
				?>
			</td>
			<?php
			if( $current_User->check_perm( 'options', 'edit', false ) )
			{ ?>
				<td class="lastcol shrinkwrap">
					<?php
					if( $loop_Plugin->status == 'enabled' )
					{
						echo action_icon( T_('Disable the plugin!'), 'deactivate', 'admin.php?ctrl=plugins&amp;action=disable_plugin&amp;plugin_ID='.$loop_Plugin->ID );
					}
					elseif( $loop_Plugin->status != 'broken' )
					{
						echo action_icon( T_('Enable the plugin!'), 'activate', 'admin.php?ctrl=plugins&amp;action=enable_plugin&amp;plugin_ID='.$loop_Plugin->ID );
					}

					echo action_icon( T_('Edit plugin settings!'), 'edit', 'admin.php?ctrl=plugins&amp;action=edit_settings&amp;plugin_ID='.$loop_Plugin->ID );

					echo action_icon( T_('Un-install this plugin!'), 'delete', 'admin.php?ctrl=plugins&amp;action=uninstall&amp;plugin_ID='.$loop_Plugin->ID );
					?>
				</td>
				<?php
			} ?>
		</tr>
		<?php
		}
		?>
		</tbody>
	</table>
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
				<?php $loop_Plugin->name(); ?>
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
				echo action_icon( T_('Display info'), 'info', regenerate_url( 'action,plugin_ID', 'action=info&amp;plugin_ID='.$loop_Plugin->ID ) );
				// Help icons, if available:
				$help_icons = array();
				if( $help_external = get_plugin_help_link($loop_Plugin) )
				{
					$help_icons[] = $help_external;
				}
				if( $help_internal = get_plugin_README_link($loop_Plugin) )
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
				    && ( ! isset( $loop_Plugin->nr_of_installs )
				         || $registrations < $loop_Plugin->nr_of_installs ) )
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
 * Revision 1.9  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>
?>
