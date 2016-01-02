<?php
/**
 * This is the template that displays a legend
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage pureforums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Blog, $disp, $MainList, $legend_statuses, $legend_icons;

$legends = array();

if( $disp == 'front' || $disp == 'posts' )
{	// If forums list is displayed
	$legends[] = array(
			'forum_default' => array(
				'icon'  => 'catBig',
				'title' => T_('Forum (contains several topics)'),
			),
			'forum_locked' => array(
				'icon'  => 'catBigLocked',
				'title' => T_('Forum is locked'),
			),
			'forum_sub' => array(
				'icon'  => 'catBig',
				'title' => T_('Sub-forum (contains several topics)'),
			),
		);
}

if( $disp != 'front' && $disp != 'single' && isset( $MainList ) && $MainList->result_num_rows > 0 )
{	// If some topics are displayed on the current page
	$legends[] = array(
			'topic_default' => array(
				'icon'  => 'topic',
				'title' => T_('Discussion topic'),
			),
			'topic_popular' => array(
				'icon'  => 'folder_hot.gif',
				'title' => T_('Popular topic'),
			),
			'topic_locked' => array(
				'icon'  => 'topicLocked',
				'title' => T_('Locked topic'),
			),
			'topic_sticky' => array(
				'icon'  => 'topicSticky',
				'title' => T_('Sticky topic / Announcement'),
			),
		);

	if( $Blog->get_setting( 'track_unread_content' ) )
	{	// If tracking of unread content is enabled:
		$legends[] = array(
				'topic_new' => array(
					'system_icon' => 'bullet_orange',
					'title'       => T_('New topic'),
				),
				'topic_updated' => array(
					'system_icon' => 'bullet_brown',
					'title'       => T_('Updated topic'),
				),
			);
	}
}
?>

<?php
foreach( $legends as $l => $legend )
{	// Print out all legends
if( ! empty( $legend_icons ) && count( array_intersect_key( $legend, $legend_icons ) ) )
{
?>
<table class="legend" border="0" cellpadding="0" cellspacing="0">
	<tr>
<?php
	foreach( $legend as $legend_key => $status )
	{	// Display legend icon with description
		if( isset( $legend_icons, $legend_icons[ $legend_key ] ) )
		{	// Display a legend icon only if it realy exists on the current page
	?>
		<th>
		<?php
			if( isset( $status['system_icon'] ) )
			{	// Use system icon:
				echo get_icon( $status['system_icon'], 'imgtag', array( 'title' => $status['title'] ) ).' ';
			}
			elseif( strpos( $status['icon'], '.gif' ) !== false )
			{ // The animated icon
		?>
			<img src="img/<?php echo $status['icon']; ?>" width="19" height="18" alt="<?php echo $status['title']; ?>" title="<?php echo $status['title']; ?>" />
		<?php
			}
			else
			{ // Static icon
		?>
			<span class="ficon <?php echo $status['icon']; ?>" title="<?php echo $status['title']; ?>"></span>
		<?php } ?>
		</th>
		<td><?php echo $status['title']; ?></td>
	<?php }
		} ?>
	</tr>
</table>
<?php
	}
}
?>

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
				<a href="<?php echo get_manual_url( 'visibility-statuses' ); ?>" target="_blank"><span class="note status_<?php echo $status; ?>"><span><?php echo $statuses[ $status ]; ?></span></span></a>
				<span><?php echo $title; ?></span>
			</li>
		<?php
		}
	}
	?>
</ul>
<?php } ?>