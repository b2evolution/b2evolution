<?php
/**
 * This file implements the plugin settings form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
?>

<form class="fform" name="form" action="b2options.php" method="post">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="tab" value="<?php echo $tab; ?>" />
	
	<fieldset>
		<legend><?php echo T_('Rendering plug-ins') ?></legend>
		<table class="thin">
			<tr>
				<th><?php echo T_('Plug-in') ?></th>
				<th><?php echo T_('Apply') ?></th>
				<th><?php echo T_('Description') ?></th>
			</tr>
			<?php
			$Renderer->restart();	 // make sure iterator is at start position
			while( $loop_RendererPlugin = $Renderer->get_next() )
			{
			?>
			<tr>
				<td><?php	$loop_RendererPlugin->name(); ?></td>
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
		<table class="thin">
			<tr>
				<th><?php echo T_('Plug-in') ?></th>
				<th><?php echo T_('Description') ?></th>
			</tr>
			<?php
			$Toolbars->restart();	 // make sure iterator is at start position
			while( $loop_ToolbarPlugin = $Toolbars->get_next() )
			{
			?>
			<tr>
				<td><?php	$loop_ToolbarPlugin->name(); ?></td>
				<td><?php	$loop_ToolbarPlugin->short_desc(); ?></td>
			</tr>
			<?php
			}
			?>
		</table>
	</fieldset>
	
	<?php if( $current_User->check_perm( 'options', 'edit' ) )
	{ ?>
	<fieldset>
		<fieldset>
			<div <?php echo ( $tab == 'regional' ) ? 'style="text-align:center"' : 'class="input"'?>>
				<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search" />
				<input type="reset" value="<?php echo T_('Reset') ?>" class="search" />
			</div>
		</fieldset>
	</fieldset>
	<?php } ?>

</form>
