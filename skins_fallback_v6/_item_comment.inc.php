<?php
/**
 * This is the template that displays a single comment
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
		'comment_start'         => '<article class="evo_comment panel panel-default">',
		'comment_end'           => '</article>',
		'comment_post_display'	=> false,	// Do we want ot display the title of the post we're referring to?
		'comment_post_before'   => '<h3 class="evo_comment_post_title">',
		'comment_post_after'    => '</h3>',
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
		'author_link_text'      => 'auto', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
		'before_image'          => '<figure class="evo_image_block">',
		'before_image_legend'   => '<figcaption class="evo_image_legend">',
		'after_image_legend'    => '</figcaption>',
		'after_image'           => '</figure>',
		'image_size'            => 'fit-1280x720',
		'image_class'           => 'img-responsive',
		'Comment'               => NULL, // This object MUST be passed as a param!
	), $params );

/**
 * @var Comment
 */
$Comment = & $params['Comment'];

// Load comment's Item object:
$Comment->get_Item();


$Comment->anchor();
echo $params['comment_start'];

// Post title
if( $params['comment_post_display'] )
{
	echo $params['comment_post_before'];
	echo T_('In response to').': ';
	$Comment->Item->title( array(
			'link_type' => 'permalink',
		) );
	echo $params['comment_post_after'];
}

// Title
echo $params['comment_title_before'];
switch( $Comment->get( 'type' ) )
{
	case 'comment': // Display a comment:
	case 'meta': // Display a meta comment:
		if( $Comment->is_meta() )
		{	// Meta comment:
			echo '<span class="badge badge-info">'.$Comment->get_inlist_order().'</span> ';
		}

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

// Status
if( $Comment->status != 'published' )
{ // display status of comment (typically an angled banner in the top right corner):
	$Comment->format_status( array(
			'template' => '<div class="evo_status evo_status__$status$ badge pull-right" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
		) );
}

echo $params['comment_title_after'];

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
$Comment->edit_link( '', '', '#', '#', 'permalink_right', '&amp;', true, $Comment->get_permanent_url() ); /* Link to backoffice for editing */
$Comment->delete_link( '', '', '#', '#', 'permalink_right', false, '&amp;', true, false, '#', $commented_Item->get_permanent_url() ); /* Link to backoffice for deleting */

$Comment->date(); echo ' @ '; $Comment->time( '#short_time' );
$Comment->reply_link(); /* Link for replying to the Comment */
$Comment->vote_helpful( '', '', '&amp;', true, true );

echo $params['comment_info_after'];

echo $params['comment_end'];
?>