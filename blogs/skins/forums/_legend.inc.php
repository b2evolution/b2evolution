<?php
/**
 * This is the template that displays a legend
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $disp, $MainList, $legend_statuses;

$legends = array();

if( $disp == 'catdir' )
{	// If forums list is displayed
	$legends[] = array(
			array(
				'icon'  => 'folder_big.gif',
				'title' => T_('No new posts'),
			),
			array(
				'icon'  => 'folder_locked_big.gif',
				'title' => T_('Forum is locked'),
			),
		);
}

if( $disp != 'single' && isset( $MainList ) && $MainList->result_num_rows > 0 )
{	// If some topics are displayed on the current page
	$legends[] = array(
			array(
				'icon'  => 'folder.gif',
				'title' => T_('No new posts'),
			),
			array(
				'icon'  => 'folder_hot.gif',
				'title' => T_('No new posts').' [ '.T_('Popular').' ]',
			),
			array(
				'icon'  => 'folder_lock.gif',
				'title' => T_('No new posts').' [ '.T_('Locked').' ]',
			),
			array(
				'icon'  => 'folder_sticky.gif',
				'title' => T_('Sticky'),
			),
		);
}
?>

<?php
foreach( $legends as $l => $legend )
{	// Print out all legends
?>
<table class="legend" border="0" cellpadding="0" cellspacing="0">
	<tr>
	<?php
	foreach( $legend as $s => $status )
	{	// Display legend icon with description
	?>
		<th><img src="img/<?php echo $status['icon']; ?>" alt="<?php echo $status['title']; ?>" title="<?php echo $status['title']; ?>" /></th>
		<td><?php echo $status['title']; ?></td>
	<?php } ?>
	</tr>
</table>
<?php } ?>

<?php
if( !empty( $legend_statuses ) && is_logged_in() )
{	// Display legend for items statuses
	$legend_statuses = array_unique( $legend_statuses );
	$statuses = get_visibility_statuses( '', array( 'deprecated', 'redirected', 'trash' ) );
	$statuses_titles = get_visibility_statuses( 'legend-titles' );
?>
<ul class="bPosts bForums legend">
	<?php
	foreach( $statuses_titles as $status => $title )
	{
		if( in_array( $status, $legend_statuses ) )
		{	// Only statuses that exist on the page
		?>
			<li>
				<a href="http://b2evolution.net/man/visibility-statuses"><span class="note status_<?php echo $status; ?>"><span><?php echo $statuses[ $status ]; ?></span></span></a>
				<span><?php echo $title; ?></span>
			</li>
		<?php
		}
	}
	?>
</ul>
<?php
}

/*
 * $Log$
 * Revision 1.2  2013/11/06 08:05:44  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>