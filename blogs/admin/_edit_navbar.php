<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */
?>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td>
		<table cellpadding="0" cellspacing="0" border="0"><tr>
		<?php	if($previousXend > 0) { ?>
		<td>
			<form name="nextXposts" method="post" action="<?php echo regenerate_url( array('poststart','postend'), array('poststart='.$previousXstart,'postend='.$previousXend), $pagenow); ?>">
				<input type="submit" name="submitprevious" class="search" value="< <?php printf( T_('Previous %d'), $posts ) ?>" />
			</form>
		</td>
		<?php	}
		if($nextXstart <= $MainList->get_total_num_posts()) { ?>
		<td>
		
			<form name="nextXposts" method="post" action="<?php echo regenerate_url( array('poststart','postend'), array('poststart='.$nextXstart,'postend='.$nextXend), $pagenow); ?>">
				<input type="submit" name="submitnext" class="search" value="<?php printf( T_('Next %d'), $posts ) ?> >" />
			</form>
		</td>
		<?php	}	?>
		</tr></table>
		</td>
		<td>&nbsp;</td>

		<td align="right">
			<form name="showXfirstlastposts" method="get">
				<input type="hidden" name="blog" value="<?php echo $blog ?>">
				<input type="text" name="poststart" value="<?php echo $poststart ?>" style="width:40px;" /?>
				<?php /* TRANS: x TO y OF z */ echo T_(' to ') ?>
				<input type="text" name="postend" value="<?php echo $postend ?>" style="width:40px;" /?>
				<?php /* TRANS: x TO y OF z */ echo T_(' of ') ?> <?php echo $MainList->get_total_num_posts() ?>
				<select name="order">
					<option value="DESC" <?php
					$i = $order;
					if ($i == "DESC")
					echo ' selected="selected"';
					?>><?php echo T_('from the end') ?></option>
					<option value="ASC" <?php
					if ($i == "ASC")
					echo ' selected="selected"';
					?>><?php echo T_('from the start') ?></option>
				</select>
				<input type="submit" name="submitXtoX" class="search" value="<?php echo T_('OK') ?>" />
			</form>
		</td>
	</tr>
</table>
