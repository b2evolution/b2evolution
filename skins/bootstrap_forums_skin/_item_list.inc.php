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
 * @subpackage bootstrap_forums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Default params:
$params = array_merge( array(
		'post_navigation' => 'same_category', // In this skin, it makes no sense to navigate in any different mode than "same category"
	), $params );

global $Item, $cat;

/**
 * @var array Save all statuses that used on this page in order to show them in the footer legend
 */
global $legend_statuses;

if( !is_array( $legend_statuses ) )
{	// Init this array only first time
	$legend_statuses = array();
}

$comments_number = generic_ctp_number( $Item->ID, 'comments', get_inskin_statuses( $Item->get_blog_ID(), 'comment' ) );

$status_icon = 'fa-comments';
$status_title = '';
$status_alt = T_('No new posts');
if( $Item->is_featured() || $Item->is_intro() )
{	// Special icon for featured & intro posts
	$status_icon = 'fa-bullhorn';
	$status_title = '<strong>'.T_('Sticky').':</strong> ';
}
elseif( $Item->comment_status == 'closed' || $Item->comment_status == 'disabled' || $Item->is_locked() )
{	// The post is closed for comments
	$status_icon = 'fa-lock';
	$status_alt = T_('This topic is locked: you cannot edit posts or make replies.');
}
elseif( $comments_number > 25 )
{	// Popular topic
	$status_icon = 'fa-star';
}
?>
		<tr>
			<td class="ft_status_topic"><i class="icon fa <?php echo $status_icon; ?>" title="<?php echo $status_alt; ?>"></i></td>
			<td class="ft_title"><?php
				echo $status_title;
				$Item->load_Blog();
				if( $Item->Blog->get_setting( 'track_unread_content' ) )
				{ // Display icon about unread status
					$Item->display_unread_status();
				}
				// Title:
				$Item->title( array(
						'link_class'      => 'topictitle'.( $Item->get_read_status() != 'read' ? ' unread' : '' ),
						'post_navigation' => $params['post_navigation'],
					) );
				if( $Skin->enabled_status_banner( $Item->status ) )
				{ // Status:
					$Item->status( array(
							'format' => 'styled',
							'class'  => 'badge',
						) );
					$legend_statuses[] = $Item->status;
				}
				if( empty( $cat ) )
				{ // Excerpt:
					$Item->excerpt( array(
						'before' => '<div class="small">',
						'after'  => '</div>',
						) );
				}
				// Author info:
				echo '<div class="ft_author_info">'.T_('Started by');
				$Item->author( array( 'link_text' => 'login', 'after' => '' ) );
				echo ', '.mysql2date( 'D M j, Y H:i', $Item->datecreated );
				echo '</div>';
			?></td>
			<td class="ft_count"><?php
				if( $comments_number == 0 && $Item->comment_status == 'disabled' )
				{ // The comments are disabled
					echo T_('n.a.');
				}
				else
				{
					printf( T_('%s replies'), '<b>'.$comments_number.'</b>' );
				}
			?></td>
			<td class="ft_date"><?php
				if( $latest_Comment = & $Item->get_latest_Comment() )
				{ // Display info about last comment
					$latest_Comment->author2( array(
								'before'      => '',
								'after'       => '',
								'before_user' => '',
								'after_user'  => '',
								'link_text'   => 'only_avatar',
								'link_class'  => 'ft_author_avatar'
							) );
					$latest_Comment->date('D M j, Y H:i');
					$latest_Comment->author2( array(
							'before'      => '<br />',
							'before_user' => '<br />',
							'after'       => ' ',
							'after_user'  => ' ',
							'link_text'   => 'login'
						) );

					echo ' <a href="'.$latest_Comment->get_permanent_url().'" title="'.T_('View latest post').'" class="icon_latest_reply"><i class="fa fa-arrow-right"></i>&nbsp;<i class="fa fa-file-o"></i></a>';
				}
				else
				{ // No comments, Display info of post
					$Item->author( array(
								'before'      => '',
								'after'       => '',
								'before_user' => '',
								'after_user'  => '',
								'link_text'   => 'only_avatar',
								'link_class'  => 'ft_author_avatar'
							) );
					echo $Item->get_mod_date( 'D M j, Y H:i' );
					echo $Item->author( array(
							'before'    => '<br />',
							'link_text' => 'login',
						) );
					echo '<a href="'.$Item->get_permanent_url().'" title="'.T_('View latest post').'" class="icon_latest_reply"><i class="fa fa-arrow-right"></i>&nbsp;<i class="fa fa-file-o"></i></a>';
				}
			?></td>
		</tr>