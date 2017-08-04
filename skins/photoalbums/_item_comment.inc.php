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
 * @subpackage photoalbums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $Skin;

// Default params:
$params = array_merge( array(
		'comment_start'        => '<div class="bComment">',
		'comment_end'          => '</div>',
		'link_to'              => 'userurl>userpage', // 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
		'author_link_text'     => 'auto', // avatar_name | avatar_login | only_avatar | name | login | nickname | firstname | lastname | fullname | preferredname
		'before_image'         => '<div class="image_block">',
		'before_image_legend'  => '<div class="image_legend">',
		'after_image_legend'   => '</div>',
		'after_image'          => '</div>',
		'image_size'           => 'fit-400x320',
		'Comment'              => NULL, // This object MUST be passed as a param!
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
	if( $Skin->enabled_status_banner( $Comment->status ) && $Comment->ID > 0 )
	{ // Don't display status for previewed comments
		$Comment->format_status( array(
				'template' => '<div class="comment_status floatright"><span class="note status_$status$" data-toggle="tooltip" data-placement="top" title="$tooltip_title$"><span>$status_title$</span></span></div>',
			) );
	}
?>
	<div class="bCommentTitle">
	<?php
		switch( $Comment->get( 'type' ) )
		{
			case 'comment': // Display a comment:
				if( empty( $Comment->ID ) )
				{ // PREVIEW comment
					echo T_('PREVIEW Comment from:').'<br />';
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
			case 'pingback': // Display a pingback:
				$Comment->author( '', '#', '', '#', 'htmlbody', true, $params['author_link_text'] );
				break;
		}
	?>
	</div>
	<div class="bCommentText">
		<?php
			$Comment->avatar();
			$Comment->rating();
			$Comment->content( 'htmlbody', false, true, $params );
		?>
	</div>
	<div class="bCommentSmallPrint">
		<?php
			$commented_Item = & $Comment->get_Item();
			$Comment->edit_link( '', '', '#', '#', 'permalink_right', '&amp;', true, $Comment->get_permanent_url() ); /* Link to backoffice for editing */
			$Comment->delete_link( '', '', '#', '#', 'permalink_right', false, '&amp;', true, false, '#', $commented_Item->get_permanent_url() ); /* Link to backoffice for deleting */

			if( ! empty( $Comment->ID ) )
			{ // Get comment permanent url
				$comment_permanent_url = $Comment->get_permanent_url();
			}

			if( ! empty( $comment_permanent_url ) )
			{ // Use a linked date/time if it is available
				echo '<a href="'.$comment_permanent_url.'" class="datetime">';
			}
			$Comment->date(); echo ' @ '; $Comment->time( '#short_time' );
			if( ! empty( $comment_permanent_url ) )
			{ // Use a linked date/time if it is available
				echo '</a>';
			}

			$Comment->reply_link(); /* Link for replying to the Comment */
			$Comment->vote_helpful( '', '', '&amp;', true, true );
		?>
	</div>
<?php
	echo $params['comment_end'];
?>
<!-- ========== END of a COMMENT/TB/PB ========== -->