<?php
/**
 * This is the template that displays a single comment
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );
global $comment_template_counter, $cat;

// Default params:
$params = array_merge( array(
		'comment_start'         => '<article class="evo_comment panel panel-default">',
		'comment_end'           => '</article>',
		'comment_post_display'	=> true,	// Do we want ot display the title of the post we're referring to?
		'comment_post_before'   => '<h4 class="evo_comment_post_title">',
		'comment_post_after'    => '</h4>',
		'comment_title_before'  => '<div class="panel-heading"><h4 class="evo_comment_title panel-title">',
		'comment_title_after'   => '</h4></div><div class="panel-body">',
		'comment_avatar_before' => '<span class="evo_comment_avatar">',
		'comment_avatar_after'  => '</span>',
		'comment_rating_before' => '<div class="evo_comment_rating">',
		'comment_rating_after'  => '</div>',
		'comment_text_before'   => '<div class="evo_comment_text">',
		'comment_text_after'    => '</div>',
		'comment_info_before'   => '<footer class="evo_comment_footer clear text-muted"><small>',
		'comment_info_after'    => '</small></footer></div>',
		'link_to'               => 'userurl>userpage', // 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
		'author_link_text'      => 'name', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
		'before_image'          => '<div class="image_block">',
		'before_image_legend'   => '<div class="image_legend">',
		'after_image_legend'    => '</div>',
		'after_image'           => '</div>',
		'image_size'            => 'fit-400x320',
		'Comment'               => NULL, // This object MUST be passed as a param!
	), $params );
	
// In this skin, it makes no sense to navigate in any different mode than "same category"
// Use the category from param
$current_cat = param( 'cat', 'integer', 0 );
if( $current_cat == 0 )
{ // Use main category by default because the category wasn't set
	$current_cat = $Item->main_cat_ID;
}

if( ! isset( $comment_template_counter ) )
{
$comment_template_counter = isset( $params['comment_number'] ) ? $params['comment_number'] : 1;
if( $disp == 'single' || $disp == 'post' )
	{ // Increase a number, because Item has 1st number
		$comment_template_counter++;
	}
}
/**
 * @var Comment
 */
$Comment = & $params['Comment'];

// Load comment's Item object:
$Comment->get_Item();


$Comment->anchor();
echo $params['comment_start'];

// Status
if( $Comment->status != 'published' )
{	// display status of comment (typically an angled banner in the top right corner):
	$Comment->status( 'styled' );
}

// Title
echo $params['comment_title_before'];
switch( $Comment->get( 'type' ) )
{
	// ON *DISP = COMMENTS* SHOW THE FOLLOWING TITLE FOR EACH COMMENT
	case $disp == 'comments': // Display a comment:
	?><a href="<?php echo $Comment->get_permanent_url(); ?>" class="permalink">#<?php echo $comment_template_counter; ?></a> <?php
		if( empty($Comment->ID) )
		{	// PREVIEW comment
			echo '<span class="evo_comment_type_preview">'.T_('PREVIEW Comment from:').'</span> ';
		}
		else
		{	// Normal comment
			$Comment->permanent_link( array(
					'before'    => '',
					'after'     => ' '.T_('from:').' ',
					'text'      => T_('Comment'),
					'class'		=> 'evo_comment_type',
					'nofollow'  => true,
				) );
		}

		$Comment->author2( array(
				'before'       => ' ',
				'after'        => '#',
				'before_user'  => '',
				'after_user'   => '#',
				'format'       => 'htmlbody',
				'link_to'      => $params['link_to'],		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
				'link_text'    => $params['author_link_text'],
			) );
			
		// Post title
		if( $params['comment_post_display'] )
		{
			echo $params['comment_post_before'];
			echo T_(' in response to:').' ';
			$Comment->Item->title( array(
					'link_type' => 'permalink',
				) );
			echo $params['comment_post_after'];
		}

		if( ! $Comment->get_author_User() )
		{ // Display action icon to message only if this comment is from a visitor
			$Comment->msgform_link( $Blog->get( 'msgformurl' ) );
		}
		break;
		
	// ON *DISP = SINGLE* SHOW THE FOLLOWING TITLE FOR EACH COMMENT
	case 'comment': // Display a comment:
		?><a href="<?php echo $Comment->get_permanent_url(); ?>" class="permalink">#<?php echo $comment_template_counter; ?></a> <?php
		if( empty($Comment->ID) )
		{	// PREVIEW comment
			echo '<span class="evo_comment_type_preview">'.T_('PREVIEW Comment from:').'</span> ';
		}
		else
		{	// Normal comment
			$Comment->permanent_link( array(
					'before'    => '',
					'after'     => ' '.T_('from:').' ',
					'text'      => T_('Comment'),
					'class'		=> 'evo_comment_type',
					'nofollow'  => true,
				) );
		}

		$Comment->author2( array(
				'before'       => ' ',
				'after'        => '#',
				'before_user'  => '',
				'after_user'   => '#',
				'format'       => 'htmlbody',
				'link_to'      => $params['link_to'],		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
				'link_text'    => $params['author_link_text'],
			) );

		if( ! $Comment->get_author_User() )
		{ // Display action icon to message only if this comment is from a visitor
			$Comment->msgform_link( $Blog->get( 'msgformurl' ) );
		}
		break;

	case 'trackback': // Display a trackback:
		$Comment->permanent_link( array(
				'before'    => '',
				'after'     => ' '.T_('from:').' ',
				'text' 		=> T_('Trackback'),
				'class'		=> 'evo_comment_type',
				'nofollow'	=> true,
			) );
		$Comment->author( '', '#', '', '#', 'htmlbody', true, $params['author_link_text'] );
		break;

	case 'pingback': // Display a pingback:
		$Comment->permanent_link( array(
				'before'    => '',
				'after'     => ' '.T_('from:').' ',
				'text' 		=> T_('Pingback'),
				'class'		=> 'evo_comment_type',
				'nofollow'	=> true,
			) );
		$Comment->author( '', '#', '', '#', 'htmlbody', true, $params['author_link_text'] );
		break;
}
echo $params['comment_title_after'];
if( $Skin->enabled_status_banner( $Item->status ) )
	{
		$Item->status( array(
				'format' => 'styled',
				'class'  => 'badge',
		 ) );
		$legend_statuses[] = $Item->status;
	}
// Avatar:
echo $params['comment_avatar_before'];
$Comment->avatar();
echo $params['comment_avatar_after'];

// Rating:
$Comment->rating( array(
		'before' => $params['comment_rating_before'],
		'after'  => $params['comment_rating_after'],
	) );

// Text:
echo $params['comment_text_before'];
$Comment->content( 'htmlbody', false, true, $params );
echo $params['comment_text_after'];

// Info:
echo $params['comment_info_before'];
	$commented_Item = & $Comment->get_Item();
	$Comment->date(); echo ' @ '; $Comment->time( '#short_time' );
echo $params['comment_info_after'];

/* ======================== START OF COMMENT FOOTER ======================== */
?>
<div class="panel-footer small clearfix">
		<a href="<?php
		if( $disp == 'comments' )
		{	// We are displaying a comment in the Latest comments page:
			echo $Blog->get('lastcommentsurl');
		}
		else
		{	// We are displaying a comment under a post/topic:
			echo $Item->get_permanent_url();
		}
		?>#skin_wrapper" class="to_top postlink"><?php echo T_('Back to top'); ?></a>
	<?php
	$Comment->reply_link(); /* Link for replying to the Comment */
	$Comment->vote_helpful( '', '', '&amp;', true, true );
		echo '<div class="floatright">';
			$Item->edit_link( array(
					'before' => '',
					'after'  => '',
					'title'  => T_('Edit this topic'),
					'text'   => '#',
					'class'  => button_class( 'text' ),
				) );
			echo ' <span class="'.button_class( 'group' ).'">';
			// Set redirect after publish to the same category view of the items permanent url
			$redirect_after_publish = $Item->add_navigation_param( $Item->get_permanent_url(), 'same_category', $current_cat );
			$Item->next_status_link( array( 'before' => ' ', 'class' => button_class( 'text' ), 'post_navigation' => 'same_category', 'nav_target' => $current_cat ), true );
			$Item->next_status_link( array( 'class' => button_class( 'text' ), 'before_text' => '', 'post_navigation' => 'same_category', 'nav_target' => $current_cat ), false );
			$Item->delete_link( '', '', '#', T_('Delete this topic'), button_class( 'text' ), false, '#', TS_('You are about to delete this post!\\nThis cannot be undone!'), get_caturl( $current_cat ) );
			echo '</span>';
			echo '</div>';?>
</div>

<?php echo $params['comment_end'];

$comment_template_counter++;
?>