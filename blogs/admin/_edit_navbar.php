<?php
/**
 * This file displays the post browsing navigation bar.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 *
 * TODO: make it a method of ItemList, usable in real blogs, too
 * TODO: links to result's pages, not only stoopid 'forward'/'backward'
 * ...FP: change buttons into parameter-links
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );
?>
<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td>
		<table cellpadding="0" cellspacing="0" border="0"><tr>
		<?php	if($previousXend > 0)
		{ // TODO: get rid of tsk_ID here (fplanque). ?>
		<td>
			<form name="nextXposts" method="post" action="<?php echo regenerate_url( array('poststart','postend','tsk_ID'), array('poststart='.$previousXstart,'postend='.$previousXend) ); ?>">
				<input type="submit" name="submitprevious" class="search" value="&lt; <?php
				if( $MainList->unit == 'days' )
					printf( T_('Next %d days'), $posts );
				else printf( T_('Previous %d'), $posts )
				?>" />
			</form>
		</td>
		<?php	}
		if($nextXstart <= $MainList->get_total_num_posts()) { ?>
		<td>
			<form name="nextXposts" method="post" action="<?php echo regenerate_url( array('poststart','postend','tsk_ID'), array('poststart='.$nextXstart,'postend='.$nextXend) ); ?>">
				<input type="submit" name="submitnext" class="search" value="<?php
					if( $MainList->unit == 'days' )
						printf( T_('Previous %d days'), $posts );
					else printf( T_('Next %d'), $posts );
					?> &gt;" />
			</form>
		</td>
		<?php	}	?>
    <td></td>
		</tr></table>
		</td>
		<td>&nbsp;</td>
		<td align="right">
			<?php
				$Form = & new Form( $pagenow, 'showXfirstlastposts', 'get', 'none' );
				$Form->begin_form( '' );
				$Form->hidden( 'tab', $tab );
				$Form->hidden( 'blog', $blog );
				if( $MainList->unit == 'days' )
				{
					// TODO: dropdown / Javascript calendar?
					echo date_i18n( locale_datefmt(), $MainList->limitdate_end )
					.' './* TRANS: x TO y OF z */ T_(' to ').' '
					.date_i18n( locale_datefmt(), $MainList->limitdate_start );
				}
				else
				{ ?>
				<input type="text" name="poststart" value="<?php echo $poststart ?>" size="4" maxlength="10" />
				<?php /* TRANS: x TO y OF z */ echo T_(' to ') ?>
				<input type="text" name="postend" value="<?php echo $postend ?>" size="4" maxlength="10" />
				<?php /* TRANS: x TO y OF z */ echo T_(' of ') ?> <?php echo $MainList->get_total_num_posts() ?>
				<?php } ?>

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
			<?php
				$Form->submit( array( 'submitXtoX', T_('OK'), 'search' ) );
				$Form->end_form();
			?>
		</td>
	</tr>
</table>