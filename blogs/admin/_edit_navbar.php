<?php
/**
 * Displays the post browsing navigation bar
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 *
 * TODO: make it a method of ItemList, usable in real blogs, too
 * TODO: links to result's pages, not only stoopid 'forward'/'backward'
 * ...FP: change buttons into parameter-links 
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );
?>
<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td>
		<table cellpadding="0" cellspacing="0" border="0"><tr>
		<?php	if($previousXend > 0) { ?>
		<td>
			<form name="nextXposts" method="post" action="<?php echo regenerate_url( array('poststart','postend'), array('poststart='.$previousXstart,'postend='.$previousXend), $pagenow ); ?>">
				<input type="submit" name="submitprevious" class="search" value="&lt; <?php 
				if( $MainList->what_to_show == 'days' )
					printf( T_('Next %d days'), $posts );
				else printf( T_('Previous %d'), $posts )
				?>" />
			</form>
		</td>
		<?php	}
		if($nextXstart <= $MainList->get_total_num_posts()) { ?>
		<td>
			<form name="nextXposts" method="post" action="<?php echo regenerate_url( array('poststart','postend'), array('poststart='.$nextXstart,'postend='.$nextXend), $pagenow ); ?>">
				<input type="submit" name="submitnext" class="search" value="<?php
					if( $MainList->what_to_show == 'days' )
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
			<form action="b2browse.php" name="showXfirstlastposts" method="get">
				<input type="hidden" name="blog" value="<?php echo $blog ?>" />
				<?php
				if( $what_to_show == 'days' )
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
				<input type="submit" name="submitXtoX" class="search" value="<?php echo T_('OK') ?>" />
			</form>
		</td>
	</tr>
</table>