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
		<legend><?php echo T_('Rendering plug-ins') ?></legend>
		<table class="grouped" cellspacing="0">
			<tr>
				<th class="firstcol"><?php echo T_('Plug-in') ?></th>
				<th><?php echo T_('Apply') ?></th>
				<th><?php echo T_('Description') ?></th>
			</tr>
			<?php
			$Renderer->restart();	 // make sure iterator is at start position
			while( $loop_RendererPlugin = $Renderer->get_next() )
			{
			?>
			<tr>
				<td class="firstcol"><?php	$loop_RendererPlugin->name(); ?></td>
				<td><?php	echo $loop_RendererPlugin->apply_when; ?></td>
				<td><?php	$loop_RendererPlugin->short_desc(); ?></td>
			</tr>
			<?php
			}
			?>
		</table>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Toolbar plug-ins') ?></legend>
		<table class="grouped" cellspacing="0">
			<tr>
				<th class="firstcol"><?php echo T_('Plug-in') ?></th>
				<th><?php echo T_('Description') ?></th>
			</tr>
			<?php
			$Toolbars->restart();	 // make sure iterator is at start position
			while( $loop_ToolbarPlugin = $Toolbars->get_next() )
			{
			?>
			<tr>
				<td class="firstcol"><?php	$loop_ToolbarPlugin->name(); ?></td>
				<td><?php	$loop_ToolbarPlugin->short_desc(); ?></td>
			</tr>
			<?php
			}
			?>
		</table>
	</fieldset>
	
	<?php if( $current_User->check_perm( 'options', 'edit' ) )
	{ 
		form_submit();
	} 
	?>

</form>