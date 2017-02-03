<?php
/**
 * This file implements the UI view for the available plugins.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
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

global $dispatcher;

$Table = new Table();

$Table->title = T_('Plugins available for installation').get_manual_link('plugins_available_for_installation');

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

if( empty($AvailablePlugins) || ! ( $AvailablePlugins instanceof Plugins_admin_no_DB ) )
{ // (may have been instantiated for action 'info')
	load_class( 'plugins/model/_plugins_admin_no_db.class.php', 'Plugins_admin_no_DB' );
	$AvailablePlugins = new Plugins_admin_no_DB(); // do not load registered plugins/events from DB
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
				echo T_('Unclassified');
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
			<strong><a title="<?php echo T_('Display info') ?>" href="<?php echo regenerate_url( 'action,plugin_class', 'action=info&amp;plugin_class='.$loop_Plugin->classname) ?>">
	    <?php echo format_to_output($loop_Plugin->name); ?></a></strong>
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

		// HELP COL:
		$Table->display_col_start();
			echo action_icon( T_('Display info'), 'info', regenerate_url( 'action,plugin_class', 'action=info&amp;plugin_class='.$loop_Plugin->classname ) );
			// Help icons, if available:
			$help_icons = array();
			if( $help_external = $loop_Plugin->get_help_link() )
			{
				$help_icons[] = $help_external;
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
				[<a href="<?php echo $dispatcher ?>?ctrl=plugins&amp;action=install&amp;plugin=<?php echo rawurlencode($loop_Plugin->classname).'&amp;'.url_crumb('plugin') ?>"><?php
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

	evo_flush();
	// free memory:
	$AvailablePlugins->unregister($loop_Plugin);
}

// BODY END:
$Table->display_body_end();


$Table->display_list_end();


// Note about how to make plugins available for installation.
// It should make clear that the above list are not all available plugins (e.g. through an online channel)!
global $plugins_path;
echo '<p>';
echo T_('The above plugins are those already installed into your "plugins" directory.');
echo "</p>\n<p>";
printf( T_('You can find more plugins online at %s or other channels.'), '<a href="http://plugins.b2evolution.net/">plugins.b2evolution.net</a>');
echo "</p>\n<p>";
printf( T_('To make a plugin available for installation, extract it into the "%s" directory on the server.'),
	rel_path_to_base($plugins_path) );
echo '</p>';

?>