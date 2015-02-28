<?php
/**
 * This is the template that displays a single comment
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
		'comment_start'         => '<div class="bComment">',
		'comment_end'           => '</div>',
		'comment_title_before'  => '<div class="bCommentTitle">',
		'comment_title_after'   => '</div>',
		'comment_rating_before' => '<div class="comment_rating">',
		'comment_rating_after'  => '</div>',
		'comment_text_before'   => '<div class="bCommentText">',
		'comment_text_after'    => '</div>',
		'comment_info_before'   => '<div class="bCommentSmallPrint">',
		'comment_info_after'    => '</div>',
		'link_to'               => 'userurl>userpage', // 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
		'author_link_text'      => 'name', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
		'before_image'          => '<div class="image_block">',
		'before_image_legend'   => '<div class="image_legend">',
		'after_image_legend'    => '</div>',
		'after_image'           => '</div>',
		'image_size'            => 'fit-400x320',
		'Comment'               => NULL, // This object MUST be passed as a param!
	), $params );

/**
 * @var Comment
 */
$Comment = & $params['Comment'];

?>
<!-- ========== START of a COMMENT/TB/PB ========== -->
<?php
	$Comment->anchor();
	echo $params['comment_start'];

	// Status
	if( $Comment->status != 'published' )
	{
		$Comment->status( 'styled' );
	}

	// Title
	echo $params['comment_title_before'];
	switch( $Comment->get( 'type' ) )
	{
		case 'comment': // Display a comment:
			if( empty($Comment->ID) )
			{	// PREVIEW comment
				echo T_('PREVIEW Comment from:').' ';
			}
			else
			{	// Normal comment
				$Comment->permanent_link( array(
						'before'    => '',
						'after'     => ' '.T_('from:').' ',
						'text'      => T_('Comment'),
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
					'text' 			=> T_('Trackback'),
					'nofollow'	=> true,
				) );
			$Comment->author( '', '#', '', '#', 'htmlbody', true, $params['author_link_text'] );
			break;

		case 'pingback': // Display a pingback:
			$Comment->permanent_link( array(
					'before'    => '',
					'after'     => ' '.T_('from:').' ',
					'text' 			=> T_('Pingback'),
					'nofollow'	=> true,
				) );
			$Comment->author( '', '#', '', '#', 'htmlbody', true, $params['author_link_text'] );
			break;
	}
	echo $params['comment_title_after'];

	// Rating
	$Comment->rating( array(
			'before' => $params['comment_rating_before'],
			'after'  => $params['comment_rating_after'],
		) );

	echo $params['comment_text_before'];
	$Comment->avatar();
	$Comment->content( 'htmlbody', false, true, $params );
	echo $params['comment_text_after'];

	// Info
	echo $params['comment_info_before'];

	$commented_Item = & $Comment->get_Item();
	$Comment->edit_link( '', '', '#', '#', 'permalink_right', '&amp;', true, rawurlencode( $Comment->get_permanent_url() ) ); /* Link to backoffice for editing */
	$Comment->delete_link( '', '', '#', '#', 'permalink_right', false, '&amp;', true, false, '#', rawurlencode( $commented_Item->get_permanent_url() ) ); /* Link to backoffice for deleting */

	$Comment->date(); echo ' @ '; $Comment->time( '#short_time' );
	$Comment->reply_link(); /* Link for replying to the Comment */
	$Comment->vote_helpful( '', '', '&amp;', true, true );

	echo $params['comment_info_after'];

	echo $params['comment_end'];
?>
<!-- ========== END of a COMMENT/TB/PB ========== -->