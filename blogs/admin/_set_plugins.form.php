<?php
/**
 * This file implements the UI view for the plugin settings.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );
?>
<fieldset>
	<legend><?php echo T_('Installed plug-ins') ?></legend>
	<table class="grouped" cellspacing="0">
		<thead>
		<tr>
			<th class="firstcol"><?php echo T_('Plug-in') ?></th>
			<th></th>
			<th><?php echo T_('Priority') ?></th>
			<th><?php echo T_('Apply') ?></th>
			<th class="lastcol"><?php echo T_('Description') ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		$Plugins->restart();	 // make sure iterator is at start position
		while( $loop_Plugin = $Plugins->get_next() )
		{
		?>
		<tr>
			<td class="firstcol"><?php	$loop_Plugin->name(); ?></td>
			<td><a href="plugins.php?action=uninstall&amp;plugin_ID=<?php echo $loop_Plugin->ID ?>" title="<?php echo T_('Un-install this plugin!') ?>"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Prune (stats) */ T_('Prune') ?>"  title="<?php echo T_('Un-install this plugin!') ?>" /></a></td>
			<td class="right"><?php	echo $loop_Plugin->priority; ?></td>
			<td><?php	echo $loop_Plugin->apply_when; ?></td>
			<td class="lastcol"><?php	$loop_Plugin->short_desc(); ?></td>
		</tr>
		<?php
		}
		?>
	</table>
</fieldset>

<fieldset>
	<legend><?php echo T_('Available plug-ins') ?></legend>
	<table class="grouped" cellspacing="0">
		<tr>
			<th class="firstcol"><?php echo T_('Plug-in') ?></th>
			<th><?php echo T_('Actions') ?></th>
			<th class="lastcol"><?php echo T_('Description') ?></th>
		</tr>
		<?php
		$AvailablePlugins->restart();	 // make sure iterator is at start position
		while( $loop_Plugin = $AvailablePlugins->get_next() )
		{
		?>
		<tr>
			<td class="firstcol">
				<?php	$loop_Plugin->name(); ?>
			</td>
			<td>
				[<a href="plugins.php?action=info&amp;plugin=<?php echo urlencode($loop_Plugin->classname) ?>">?</a>]
				[<a href="plugins.php?action=install&amp;plugin=<?php echo urlencode($loop_Plugin->classname) ?>"><?php
					 echo T_('Install');
					 if( $registrations = $Plugins->count_regs($loop_Plugin->classname) )
					 {	// This plugin is already installed
							echo ' #'.($registrations+1);
					 }
					 ?></a>]
			</td>
			<td class="lastcol"><?php	$loop_Plugin->short_desc(); ?></td>
		</tr>
		<?php
		}
		?>
		</tbody>
	</table>
</fieldset>
