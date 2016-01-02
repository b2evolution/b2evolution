<?php
/**
 * This file implements the Item history view to compare two revisions
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url;

global $edited_Item, $Revision_1, $Revision_2;

global $revisions_difference_title, $revisions_difference_content;

$post_statuses = get_visibility_statuses();

$Form = new Form( NULL, 'history', 'post', 'compact' );

$Form->global_icon( T_('Cancel comparing!'), 'close', regenerate_url( 'action', 'action=history' ) );

$Form->begin_form( 'fform', sprintf( T_('Difference between revisions for: %s'), $edited_Item->get_title() ) );

?>
<table border="0" width="100%" cellpadding="0" cellspacing="4" class="diff">
	<col class="diff-marker" />
	<col class="diff-content" />
	<col class="diff-marker" />
	<col class="diff-content" />
	<tr>
		<td colspan="2" class="diff-otitle">
			<p><?php
				$iver_editor_user_link = get_user_identity_link( NULL, $Revision_1->iver_edit_user_ID );
				printf( T_('Revision #%s as of %s by %s'),
					$Revision_1->iver_ID == 0 ? '(<b>'.T_('Current version').'</b>)' : $Revision_1->iver_ID,
					mysql2localedatetime( $Revision_1->iver_edit_datetime, 'Y-m-d', 'H:i:s' ),
					( empty( $iver_editor_user_link ) ? T_( 'Deleted user' ) : $iver_editor_user_link ) );
			?>
			(<a href="<?php echo url_add_param( $admin_url, 'ctrl=items&amp;action=history_details&amp;p='.$edited_Item->ID.'&amp;r='.$Revision_1->iver_ID ) ?>" title="<?php echo T_('View this revision') ?>"><?php echo T_('View'); ?></a>)
			</p>
			<div class="center"><small><?php echo T_('Status').': '.$post_statuses[ $Revision_1->iver_status ]; ?></small></div>
		</td>
		<td colspan="2" class="diff-ntitle">
			<p><?php
				$iver_editor_user_link = get_user_identity_link( NULL, $Revision_2->iver_edit_user_ID );
				printf( T_('Revision #%s as of %s by %s'),
					$Revision_2->iver_ID == 0 ? '(<b>'.T_('Current version').'</b>)' : $Revision_2->iver_ID,
					mysql2localedatetime( $Revision_2->iver_edit_datetime, 'Y-m-d', 'H:i:s' ),
					( empty( $iver_editor_user_link ) ? T_('(deleted user)') : $iver_editor_user_link ) );
			?>
			(<a href="<?php echo url_add_param( $admin_url, 'ctrl=items&amp;action=history_details&amp;p='.$edited_Item->ID.'&amp;r='.$Revision_2->iver_ID ) ?>" title="<?php echo T_('View this revision') ?>"><?php echo T_('View'); ?></a>)
			</p>
			<div class="center"><small<?php echo $Revision_1->iver_status != $Revision_2->iver_status ? ' style="color:#F00;font-weight:bold"' : ''; ?>><?php echo T_('Status').': '.$post_statuses[ $Revision_2->iver_status ]; ?></small></div>
		</td>
	</tr>
<?php
	if( !empty( $revisions_difference_title ) )
	{	// Display title difference
		echo $revisions_difference_title;
	}
	else
	{	// No title difference
	?>
	<tr>
		<td colspan="2" class="diff-title-deletedline"><?php echo $Revision_1->iver_title ?></td>
		<td colspan="2" class="diff-title-addedline"><?php echo $Revision_2->iver_title ?></td>
	</tr>
	<?php
	}
?>
	<tr><td colspan="4">&nbsp;</td></tr>
<?php
if( !empty( $revisions_difference_content ) )
{	// Dispay content difference
	echo $revisions_difference_content;
}
else
{	// No content difference
	echo '<tr><td colspan="4" class="center red"><b>';
	echo T_('No difference between contents of the selected revisions');
	echo '</b></td></tr>';
}
?>
</table>
<?php

$Form->end_form();

?>