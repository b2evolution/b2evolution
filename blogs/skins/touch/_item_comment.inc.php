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
 * @subpackage touch
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
		'comment_start'        => '<div class="bComment">',
		'comment_end'          => '</div>',
		'link_to'              => 'userurl>userpage', // 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
		'author_link_text'     => 'preferredname',
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
?>
	<div class="comtop">
	<?php
		$Comment->avatar( 'crop-top-32x32', 'avatar' );
		$Comment->author2( array(
				'before'       => '<div class="com-author">',
				'after'        => '',
				'before_user'  => '<div class="com-author">',
				'after_user'   => '',
				'format'       => 'htmlbody',
				'link_to'		   => $params['link_to'],		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
				'link_text'    => $params['author_link_text'],
			) );
		$Comment->msgform_link( $Blog->get('msgformurl') );
		echo '</div>';
	?>
		<div class="comdater">
			<?php $Comment->date() ?> @ <?php $Comment->time( 'H:i' ) ?>
		</div>
	</div>
	<div class="combody">
		<?php
			if( $Comment->status != 'published' )
			{
				$Comment->status( 'styled' );
			}
			$Comment->rating();
			$Comment->content( 'htmlbody', false, true, $params );
		?>
		<div class="clearer"></div>
		<?php if( is_logged_in() ) { ?>
		<div class="comactions">
			<?php
				$comment_Item = & $Comment->get_Item();
				$Comment->vote_helpful( '', '', '&amp;', true, true );
				$Comment->edit_link( '', '', '#', '#', 'permalink_right', '&amp;', true, rawurlencode( $Comment->get_permanent_url() ) ); /* Link to backoffice for editing */
				$Comment->delete_link( '', '', '#', '#', 'permalink_right', false, '&amp;', true, false, '#', rawurlencode( $comment_Item->get_permanent_url() ) ); /* Link to backoffice for deleting */
				$Comment->reply_link(); /* Link for replying to the Comment */
			?>
		</div>
		<div class="clearer"></div>
		<?php } ?>
	</div>
<?php
	echo $params['comment_end'];
?>
<!-- ========== END of a COMMENT/TB/PB ========== -->