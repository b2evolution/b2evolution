<?php
/**
 * This file implements the Item history view to compare two revisions
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url;

global $edited_Item, $Revision_1, $Revision_2;

global $revisions_difference_title, $revisions_difference_content, $revisions_difference_custom_fields;

$post_statuses = get_visibility_statuses();

$Form = new Form( NULL, 'history', 'post', 'compact' );

$Form->global_icon( T_('Cancel comparing!'), 'close', regenerate_url( 'action', 'action=history' ) );

$Form->begin_form( 'fform', sprintf( T_('Difference between revisions for: %s'), $edited_Item->get_title() ) );

$version_titles = array(
		'proposed' => T_('Proposed change #%s as of %s by %s'),
		'current'  => T_('Current version as of %s by %s'),
		'archived' => T_('Archived version #%s as of %s by %s'),
	);
?>
<table border="0" width="100%" cellpadding="0" cellspacing="4" class="diff">
	<col class="diff-marker" />
	<col class="diff-content" />
	<col class="diff-marker" />
	<col class="diff-content" />
	<tr>
		<td colspan="2" class="diff-otitle">
			<p><?php echo get_item_version_title( $Revision_1 ); ?></p>
			<div class="center"><small><?php echo T_('Status').': '.$post_statuses[ $Revision_1->iver_status ]; ?></small></div>
		</td>
		<td colspan="2" class="diff-ntitle">
			<p><?php echo get_item_version_title( $Revision_2 ); ?></p>
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

if( ! empty( $revisions_difference_custom_fields ) )
{	// Dispay custom fields difference:
?>
		<tr><td colspan="4">&nbsp;</td></tr>
		<tr>
			<td colspan="4" class="diff-title-addedline"><b><?php echo T_('Custom fields').':'; ?></b></td>
		</tr>
	<?php
	foreach( $revisions_difference_custom_fields as $custom_field_label => $revisions_difference_custom_field )
	{
		echo '<tr><td colspan="2"><b>'.$custom_field_label.':</b></td><td colspan="2"><b>'.$custom_field_label.':</b></td></tr>';
		echo $revisions_difference_custom_field;
	}
}
?>
</table>
<?php

$Form->end_form();

?>