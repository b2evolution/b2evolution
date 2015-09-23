<?php
/**
 * This is the template that displays the item block in list
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Default params:
$params = array_merge( array(
		'display_column_forum' => false,
		'post_navigation' => 'same_category', // In this skin, it makes no sense to navigate in any different mode than "same category"
	), $params );

global $Item, $cat;

/**
 * @var array Save all statuses that used on this page in order to show them in the footer legend
 */
global $legend_statuses, $legend_icons;

if( !is_array( $legend_statuses ) )
{	// Init this array only first time
	$legend_statuses = array();
}
if( ! is_array( $legend_icons ) )
{ // Init this array only first time
	$legend_icons = array();
}

// Calculate what comments has the Item:
$comments_number = generic_ctp_number( $Item->ID, 'comments', get_inskin_statuses( $Item->get_blog_ID(), 'comment' ) );

$status_icon = 'topic';
$status_title = '';
$status_alt = T_('No new posts');
if( $Item->is_featured() || $Item->is_intro() )
{ // Special icon for featured & intro posts
	$status_icon = 'topicSticky';
	$status_title = '<strong>'.T_('Sticky').':</strong> ';
	$legend_icons['topic_sticky'] = 1;
}
elseif( $Item->comment_status == 'closed' || $Item->comment_status == 'disabled' || $Item->is_locked() )
{ // The post is closed for comments
	$status_icon = 'topicLocked';
	$status_alt = T_('This topic is locked: you cannot edit posts or make replies.');
	$legend_icons['topic_locked'] = 1;
}
elseif( $comments_number > 25 )
{ // Popular topic is when coummnets number is more than 25
	$status_icon = 'folder_hot.gif';
	$legend_icons['topic_popular'] = 1;
}
else
{ // Default topic
	$legend_icons['topic_default'] = 1;
}
?>
		<tr>
			<td class="status">
			<?php
				if( strpos( $status_icon, '.gif' ) !== false )
				{ // The animated icon
			?>
				<img src="img/<?php echo $status_icon; ?>" width="19" height="18" alt="<?php echo $status_alt; ?>" title="<?php echo $status_alt; ?>" />
			<?php
				}
				else
				{ // Static icon
			?>
				<span class="ficon <?php echo $status_icon; ?>" title="<?php echo $status_alt; ?>"></span>
			<?php } ?>
			</td>
			<?php
				if( $params['display_column_forum'] )
				{	// Display main category
			?>
			<td class="left"><?php $Item->main_category( 'htmlbody', array( 'display_link' => true, 'link_class' => 'forumlink' ) ); ?></td>
			<?php } ?>
			<td class="left"><?php
				echo $status_title;
				$Item->load_Blog();
				if( $Item->Blog->get_setting( 'track_unread_content' ) )
				{
					$Item->display_unread_status();
					// Update legend array to display the unread status icons in footer legend:
					switch( $Item->get_read_status() )
					{
						case 'new':
							$legend_icons['topic_new'] = 1;
							break;
						case 'updated':
							$legend_icons['topic_updated'] = 1;
							break;
					}
				}
				// Title:
				$Item->title( array(
						'link_class'      => 'topictitle',
						'post_navigation' => $params['post_navigation'],
					) );
				if( $Skin->enabled_status_banner( $Item->status ) )
				{ // Status:
					$Item->format_status( array(
							'template' => '<div class="floatright"><span class="note status_$status$"><span>$status_title$</span></span></div>',
						) );
					$legend_statuses[] = $Item->status;
				}
				if( empty( $cat ) )
				{ // Excerpt:
					$Item->excerpt();
				}
			?></td>
			<td class="row2"><?php
				if( $comments_number == 0 && $Item->comment_status == 'disabled' )
				{	// The comments are disabled
					echo T_('n.a.');
				}
				else
				{
					echo $comments_number;
				}
			?></td>
			<td class="row3"><?php $Item->author( array( 'link_text' => 'login' ) ); ?></td>
			<td class="row2 font10"><?php
				if( $latest_Comment = & $Item->get_latest_Comment() )
				{	// Display info about last comment
					$latest_Comment->date('D M j, Y H:i');
					$latest_Comment->author2( array(
							'before'      => '<br />',
							'before_user' => '<br />',
							'after'       => ' ',
							'after_user'  => ' ',
							'link_text'   => 'login'
						) );

					echo ' <a href="'.$latest_Comment->get_permanent_url().'"><span class="ficon latestReply" title="'.T_('View latest post').'"></span></a>';
				}
				else
				{	// No comments, Display info of post
					echo $Item->get_mod_date( 'D M j, Y H:i' );
					echo $Item->author( array(
							'before'    => '<br />',
							'link_text' => 'login',
						) );
					echo '<a href="'.$Item->get_permanent_url().'"><span class="ficon latestReply" title="'.T_('View latest post').'"></span></a>';
				}
			?></td>
		</tr>