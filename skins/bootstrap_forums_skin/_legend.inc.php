<?php
/**
 * This is the template that displays a legend
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_forums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $disp, $MainList, $legend_statuses, $legend_icons;

$legends = array();

if( $disp == 'front' || $disp == 'posts' )
{ // If forums list is displayed
	$legends[] = array(
			'forum_default' => array(
				'icon'  => 'fa-folder big',
				'title' => T_('No new posts'),
			),
			'forum_locked' => array(
				'icon'  => 'fa-lock big',
				'title' => T_('Forum is locked'),
			),
		);
}

if( $disp != 'front' && $disp != 'single' && isset( $MainList ) && $MainList->result_num_rows > 0 )
{ // If some topics are displayed on the current page
	$legends[] = array(
			'topic_default' => array(
				'icon'  => 'fa-comments',
				'title' => T_('No new posts'),
			),
			'topic_popular' => array(
				'icon'  => 'fa-star',
				'title' => T_('No new posts').' [ '.T_('Popular').' ]',
			),
			'topic_locked' => array(
				'icon'  => 'fa-lock',
				'title' => T_('No new posts').' [ '.T_('Locked').' ]',
			),
			'topic_sticky' => array(
				'icon'  => 'fa-bullhorn',
				'title' => T_('Sticky'),
			),
		);
}

if( ! empty( $legend_icons ) || ! empty( $legend_statuses ) )
{ // Display the legend icons/badges only when at least one exists on the current page
?>
<fieldset class="legends">
	<legend class="dimmed"><?php echo T_('Legend'); ?></legend>
<?php
foreach( $legends as $l => $legend )
{ // Print out all legends
?>
	<?php
	foreach( $legend as $legend_key => $legend_data )
	{ // Display legend icon with description
		if( isset( $legend_icons, $legend_icons[ $legend_key ] ) )
		{ // Display a legend icon only if it realy exists on the current page
	?>
		<div class="legend-item">
			<i class="icon fa <?php echo $legend_data['icon']; ?>" title="<?php echo $legend_data['title']; ?>"></i>
			<?php echo $legend_data['title']; ?>
		</div>
	<?php
		}
	}
	?>
<?php } ?>

<?php
if( !empty( $legend_statuses ) && is_logged_in() )
{ // Display legend for items statuses
	$legend_statuses = array_unique( $legend_statuses );
	$statuses = get_visibility_statuses( '', array( 'deprecated', 'redirected', 'trash' ) );
	$statuses_titles = get_visibility_statuses( 'legend-titles' );
?>
<ul class="evo_posts evo_forums legend">
	<?php
	foreach( $statuses_titles as $status => $title )
	{
		if( in_array( $status, $legend_statuses ) )
		{	// Only statuses that exist on the page
		?>
			<li>
				<a href="<?php echo get_manual_url( 'visibility-statuses' ); ?>" target="_blank"><span class="note status_<?php echo $status; ?>"><span class="badge"><?php echo $statuses[ $status ]; ?></span></span></a>
				<span><?php echo $title; ?></span>
			</li>
		<?php
		}
	}
	?>
</ul>
<?php } ?>
</fieldset>
<?php } ?>