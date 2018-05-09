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

global $revisions_difference_title, $revisions_difference_content, $revisions_difference_custom_fields, $revisions_difference_links;

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
{	// Display custom fields difference:
?>
		<tr><td colspan="4">&nbsp;</td></tr>
		<tr>
			<td colspan="4" class="diff-title-addedline"><b><?php echo T_('Custom fields').':'; ?></b></td>
		</tr>
	<?php
	foreach( $revisions_difference_custom_fields as $custom_field_label => $revisions_diff_data )
	{
		echo '<tr>';
		for( $r = 1; $r <= 2; $r++ )
		{
			$custom_field_label = $revisions_diff_data['r'.$r.'_label'];
			echo '<td colspan="2"><b>';
			if( ! $revisions_diff_data['r'.$r.'_has_field'] )
			{	// Field label when the custom field is not used by this revision:
				echo '<span class="violet" title="'.format_to_output( sprintf( T_('The field "%s" is not used in this revision'), $custom_field_label ), 'htmlattr' ).'">'.$custom_field_label.'</span>';
			}
			elseif( $revisions_diff_data['deleted'] )
			{	// Field label when nonexistent custom field is loaded from revision:
				echo '<span class="red" title="'.format_to_output( sprintf( T_('The field "%s" does not exist'), $custom_field_label ), 'htmlattr' ).'">'.$custom_field_label.'</span>';
			}
			else
			{	// Normal field label when it is used by revision and exists in DB:
				echo $custom_field_label;
			}
			echo ':</b></td>';
		}
		echo $revisions_diff_data['difference'];
	}
}

if( ! empty( $revisions_difference_links ) )
{	// Display links/attachments difference:
?>
		<tr><td colspan="4">&nbsp;</td></tr>
		<tr>
			<td colspan="4" class="diff-title-addedline"><b><?php echo T_('Images &amp; Attachments').':'; ?></b></td>
		</tr>
		<tr>
			<td colspan="4">
				<table class="table table-striped table-bordered table-condensed">
					<thead>
						<tr>
						<?php
						for( $r = 1; $r <= 2; $r++ )
						{	// Print out table headers for two revisions:
							echo '<th class="nowrap">'.T_('Icon/Type').'</th>';
							echo '<th class="nowrap" width="50%">'.T_('Destination').'</th>';
							echo '<th class="nowrap">'.T_('Order').'</th>';
							echo '<th class="nowrap">'.T_('Position').'</th>';
							if( $r == 1 )
							{	// Use ID column as separator between the compared revisions:
								echo '<th class="nowrap">'.T_('Link ID').'</th>';
							}
						}
						?>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach( $revisions_difference_links as $link_ID => $links_data )
					{
						echo '<tr>';
						for( $r = 1; $r <= 2; $r++ )
						{	// Print out differences of the compared revisions:
							if( isset( $links_data['r'.$r] ) )
							{	// If the revision has the Link/Attachment:
								$link = $links_data['r'.$r];
								foreach( $links_data['r'.$r] as $link_key => $link_value )
								{	// Print out each link propoerty:
									if( $link_key == 'file_ID' )
									{	// Skip column with file ID:
										continue;
									}
									$r2 = $r == 1 ? 2 : 1;
									$class = '';
									if( isset( $links_data['r'.$r2][ $link_key ] ) && $link_value != $links_data['r'.$r2][ $link_key ] )
									{	// Mark the different property with red background color:
										$class .= 'bg-danger';
									}
									if( $link_key == 'order' )
									{	// Order value must be aligned to the right:
										$class .= ' text-right';
									}
									elseif( $link_key == 'path' && $link_value === false )
									{	// If file was deleted:
										$link_value = '<b class="red">'.sprintf( T_('The file "%s" was deleted'), '#'.$links_data['r'.$r]['file_ID'] ).'</b>';
										$class .= ' bg-danger';
									}
									$class = trim( $class );
									echo '<td'.( empty( $class ) ? '' : ' class="'.$class.'"' ).'>'.$link_value.'</td>';
								}
							}
							else
							{	// If the revision has no the Link/Attachment:
								echo '<td colspan="4"><b class="violet">'.sprintf( T_('The attachment "%s" is not used in this revision'), '#'.$link_ID ).'</b></td>';
							}
							if( $r == 1 )
							{	// Use ID column as separator between the compared revisions:
								echo '<td class="bg-info text-right"><b>'.$link_ID.'</b></td>';
							}
						}
						echo '</tr>';
					}
					?>
					</tbody>
				</table>
			</td>
		</tr>
<?php
}
?>
</table>
<?php

$Form->end_form();

?>