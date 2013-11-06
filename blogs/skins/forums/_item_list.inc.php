<?php
/**
 * This is the template that displays the item block in list
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Default params:
$params = array_merge( array(
		'display_column_forum' => false,
		'post_navigation' => 'same_category', // In this skin, it makes no sense to navigate in any different mode than "same category"
	), $params );

global $Item;

/**
 * @var array Save all statuses that used on this page in order to show them in the footer legend
 */
global $legend_statuses;

if( !is_array( $legend_statuses ) )
{	// Init this array only first time
	$legend_statuses = array();
}

$comments_number = generic_ctp_number( $Item->ID, 'comments', get_inskin_statuses() );

$status_icon = 'folder.gif';
$status_title = '';
$status_alt = T_('No new posts');
if( $Item->is_featured() || $Item->is_intro() )
{	// Special icon for featured & intro posts
	$status_icon = 'folder_sticky.gif';
	$status_title = '<strong>'.T_('Sticky').':</strong> ';
}
elseif( $Item->comment_status == 'closed' || $Item->comment_status == 'disabled' || $Item->is_locked() )
{	// The post is closed for comments
	$status_icon = 'folder_lock.gif';
	$status_alt = T_('This topic is locked: you cannot edit posts or make replies.');
}
elseif( $comments_number > 25 )
{	// Popular topic
	$status_icon = 'folder_hot.gif';
}
?>
		<tr>
			<td class="status"><img src="img/<?php echo $status_icon; ?>" width="19" height="18" alt="<?php echo $status_alt; ?>" title="<?php echo $status_alt; ?>" /></td>
			<?php
				if( $params['display_column_forum'] )
				{	// Display main category
			?>
			<td class="left"><?php $Item->main_category( 'htmlbody', array( 'display_link' => true, 'link_class' => 'forumlink' ) ); ?></td>
			<?php } ?>
			<td class="left"><?php
				echo $status_title;
				$Item->title( array(
						'link_class'      => 'topictitle',
						'post_navigation' => $params['post_navigation'],
					) );
				if( $Skin->enabled_status_banner( $Item->status ) )
				{
					$Item->status( array( 'format' => 'styled' ) );
					$legend_statuses[] = $Item->status;
				}
			?></td>
			<td class="row2 font10"><?php
				if( $comments_number == 0 && $Item->comment_status == 'disabled' )
				{	// The comments are disabled
					echo T_('n.a.');
				}
				else
				{
					echo $comments_number;
				}
			?></td>
			<td class="row3 font11"><?php $Item->author(); ?></td>
			<td class="row2 font10"><?php
				if( $latest_Comment = & $Item->get_latest_Comment() )
				{	// Display info about last comment
					$latest_Comment->date('D M j, Y H:i');
					$latest_Comment->author2( array(
							'before' => '<br />',
							'before_user' => '<br />',
							'after' => ' ',
							'after_user' => ' ',
						) );

					echo ' <a href="'.$latest_Comment->get_permanent_url().'"><img src="img/icon_latest_reply.gif" alt="'.T_('View latest post').'" title="'.T_('View latest post').'" /></a>';
				}
				else
				{	// No comments, Display info of post
					echo $Item->get_mod_date( 'D M j, Y H:i' );
					echo $Item->author( array(
							'before' => '<br />',
						) );
					echo '<a href="'.$Item->get_permanent_url().'"><img src="img/icon_latest_reply.gif" alt="'.T_('View latest post').'" title="'.T_('View latest post').'" /></a>';
				}
			?></td>
		</tr>
<?php

/*
 * $Log$
 * Revision 1.2  2013/11/06 08:05:44  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>