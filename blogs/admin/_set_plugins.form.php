<?php
/**
 * This file implements the UI view for the plugin settings.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );
?>

<form class="fform" name="form" action="b2options.php" method="post">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="tab" value="<?php echo $tab; ?>" />

	<fieldset>
		<legend><?php echo T_('Installed plug-ins') ?></legend>
		<table class="grouped" cellspacing="0">
			<thead>
			<tr>
				<th class="firstcol"><?php echo T_('Plug-in') ?></th>
				<th></th>
				<th><?php echo T_('Priority') ?></th>
				<th><?php echo T_('Apply') ?></th>
				<th><?php echo T_('Description') ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			$Plug->restart();	 // make sure iterator is at start position
			while( $loop_Plugin = $Plug->get_next() )
			{
			?>
			<tr>
				<td class="firstcol"><?php	$loop_Plugin->name(); ?></td>
				<td><a href="b2options.php?tab=plugins&amp;action=uninstall&amp;plugin_ID=<?php echo $loop_Plugin->ID ?>" title="<?php echo T_('Un-install this plugin!') ?>"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Prune (stats) */ T_('Prune') ?>"  title="<?php echo T_('Un-install this plugin!') ?>" /></a></td>
				<td class="right"><?php	echo $loop_Plugin->priority; ?></td>
				<td><?php	echo $loop_Plugin->apply_when; ?></td>
				<td><?php	$loop_Plugin->short_desc(); ?></td>
			</tr>
			<?php
			}
			?>
		</table>
	</fieldset>

	<?php

	// Discover additional plugins.
	$AvailablePlugins = & new Plug();
	$AvailablePlugins->discover();

	?>

 	<fieldset>
		<legend><?php echo T_('Available plug-ins') ?></legend>
		<table class="grouped" cellspacing="0">
			<tr>
				<th class="firstcol"><?php echo T_('Plug-in') ?></th>
				<th><?php echo T_('Install') ?></th>
				<th><?php echo T_('Description') ?></th>
			</tr>
			<?php
			$AvailablePlugins->restart();	 // make sure iterator is at start position
			while( $loop_Plugin = $AvailablePlugins->get_next() )
			{
			?>
			<tr>
				<td class="firstcol"><?php	$loop_Plugin->name(); ?></td>
				<td>[<a href="b2options.php?tab=plugins&amp;action=install&amp;plugin=<?php echo urlencode($loop_Plugin->classname) ?>"><?php
						 echo T_('Install');
						 if( $registrations = $Plug->count_regs($loop_Plugin->classname) )
						 {	// This plugin is already installed
								echo ' #'.($registrations+1);
						 }
						 ?></a>]</td>
				<td><?php	$loop_Plugin->short_desc(); ?></td>
			</tr>
			<?php
			}
			?>
			</tbody>
		</table>
	</fieldset>


	<?php
	if( $current_User->check_perm( 'options', 'edit' ) )
	{
		form_submit();
	}
	?>

</form>