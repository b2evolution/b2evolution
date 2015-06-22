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
global $legend_statuses, $legend_icons;

if( ! is_array( $legend_statuses ) )
{ // Init this array only first time
	$legend_statuses = array();
}
if( ! is_array( $legend_icons ) )
{ // Init this array only first time
	$legend_icons = array();
}

$comments_number = generic_ctp_number( $Item->ID, 'comments', get_inskin_statuses( $Item->get_blog_ID(), 'comment' ) );

$status_icon = 'fa-comments';
$status_title = '';
$status_alt = T_('No new posts');
$legend_icons['topic_default'] = 1;
if( $Item->is_featured() || $Item->is_intro() )
{ // Special icon for featured & intro posts
	$status_icon = 'fa-bullhorn';
	$status_title = '<strong>'.T_('Sticky').':</strong> ';
	$legend_icons['topic_sticky'] = 1;
}
elseif( $Item->comment_status == 'closed' || $Item->comment_status == 'disabled' || $Item->is_locked() )
{ // The post is closed for comments
	$status_icon = 'fa-lock';
	$status_alt = T_('This topic is locked: you cannot edit posts or make replies.');
	$legend_icons['topic_locked'] = 1;
}
elseif( $comments_number > 25 )
{ // Popular topic
	$status_icon = 'fa-star';
	$legend_icons['topic_popular'] = 1;
}
?>
		<article class="container group_row posts_panel">	
			<div class="ft_status__ft_title col-lg-8 col-md-8 col-sm-6 col-xs-12">		
			<div class="ft_status_topic"><i class="icon fa <?php echo $status_icon; ?>" title="<?php echo $status_alt; ?>"></i></div>
				<div class="ft_title"><?php
					echo '<div class="posts_panel_title_wrapper">';
					echo '<div class="cell1"><div class="wrap">';
					echo $status_title;
					$Item->load_Blog();
					if( $Item->Blog->get_setting( 'track_unread_content' ) )
					{ // Display icon about unread status
						$Item->display_unread_status();
					}
					// Title:
					$Item->title( array(
							'link_class'      => 'topictitle ellipsis'.( $Item->get_read_status() != 'read' ? ' unread' : '' ),
							'post_navigation' => $params['post_navigation'],
						) );
					echo '</div></div>';
					if( $Skin->enabled_status_banner( $Item->status ) )
					{ // Status:
						$Item->format_status( array(
								'template' => '<div class="cell2"><div class="evo_status evo_status__$status$ badge">$status_title$</div></div>',
							) );
						$legend_statuses[] = $Item->status;
					}
					echo '</div>';
						$Item->excerpt( array(
							'before' => '<div class="small ellipsis">',
							'after'  => '</div>',
							) );
					?>
				</div>
				<div class="ft_author_info ellipsis">
					<?php
					// Author info: (THIS HAS DOFFERENT RWD MOVES FROM WHAT'S ABOVE, so it should be in a different div)
					echo T_('Started by');
					$Item->author( array( 'link_text' => 'login', 'after' => '' ) );
					echo ', '.mysql2date( 'D M j, Y H:i', $Item->datecreated );
					echo '</div>';
					// Super small screen size Author info:
					echo '<div class="ft_author_info shrinked ellipsis">'.T_('By');
					$Item->author( array( 'link_text' => 'login', 'after' => '' ) );
					echo ', '.mysql2date( 'M j, Y', $Item->datecreated );
				?>
				</div>
			</div>
			<div class="ft_count col-lg-1 col-md-1 col-sm-2 col-xs-2"><?php
				if( $comments_number == 0 && $Item->comment_status == 'disabled' )
				{ // The comments are disabled
					echo T_('n.a.');
				}
				else if( $latest_Comment = & $Item->get_latest_Comment() )
				{
					printf( T_('%s replies'), '<div><a href="'.$latest_Comment->get_permanent_url().'" title="'.T_('View latest comment').'">'.$comments_number.'</a></div>' );
				}
			?>
			</div>
			<div class="ft_date col-lg-3 col-md-3 col-sm-4"><?php
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
							'after'       => '',
							'after_user'  => '',
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
					echo ' <a href="'.$Item->get_permanent_url().'" title="'.T_('View latest post').'" class="icon_latest_reply"><i class="fa fa-arrow-right"></i>&nbsp;<i class="fa fa-file-o"></i></a>';
				}
			?></div>
			
			<!-- This is shrinked date that applies on lower screen res -->
			<div class="ft_date_shrinked item_list"><?php
				if( $latest_Comment = & $Item->get_latest_Comment() )
				{ // Display info about last comment
					$latest_Comment->date('m/j/y ');
					$latest_Comment->author2( array(
							'link_text'   => 'login'
						) );

					echo ' <a href="'.$latest_Comment->get_permanent_url().'" title="'.T_('View latest post').'" class="icon_latest_reply"><i class="fa fa-arrow-right"></i>&nbsp;<i class="fa fa-file-o"></i></a>';
				}
				else
				{ // No comments, Display info of post
					echo $Item->get_mod_date( 'm/j/y' );
					echo $Item->author( array(
							'link_text' => 'login',
						) );
					echo ' <a href="'.$Item->get_permanent_url().'" title="'.T_('View latest post').'" class="icon_latest_reply"><i class="fa fa-arrow-right"></i>&nbsp;<i class="fa fa-file-o"></i></a>';
				}
			?></div>	
		</article>