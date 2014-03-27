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
 * @subpackage glossyblue
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
		'comment_start'        => '<div class="bComment">',
		'comment_end'          => '</div>',
		'Comment'              => NULL, // This object MUST be passed as a param!
	), $params );

/* This variable is for alternating comment background */
global $glossyblue_oddcomment;

/**
 * @var Comment
 */
$Comment = & $params['Comment'];

?>
<!-- ========== START of a COMMENT/TB/PB ========== -->
<?php
	echo str_replace( 'class=""', 'class="'.$glossyblue_oddcomment.'"', $params['comment_start'] );
	if( $Comment->status != 'published' )
	{
		$Comment->status( 'styled' );
	}
	$Comment->anchor();
?>

	<?php
		switch( $Comment->get( 'type' ) )
		{
			case 'comment': // Display a comment:
				$Comment->permanent_link( array(
					'before'    => '',
					'after'     => ' ',
					'text' => '&#167; ',
					'nofollow' => true,
				) );
				$Comment->author( '', '', '', '&#174;', 'htmlbody', true, 'preferredname' );
				$Comment->msgform_link( $Blog->get('msgformurl') );

				$commented_Item = & $Comment->get_Item();
				echo ' '.T_('said on :').' <small class="commentmetadata">';
				$Comment->date() ?> @ <?php $Comment->time( 'H:i' );
				$Comment->edit_link( '', '', get_icon( 'edit' ), '#', '', '&amp;', true, rawurlencode( $Comment->get_permanent_url() ) );
				$Comment->delete_link( '', '', get_icon( 'delete' ), '#', '', false, '&amp;', true, false, '#', rawurlencode( $commented_Item->get_permanent_url() ) );
				echo '</small>';
				break;

			case 'trackback': // Display a trackback:
				$Comment->permanent_link( T_('Trackback') );
				echo ' '.T_('from:').' ';
				$Comment->author( '', '#', '', '#', 'htmlbody', true, 'preferredname' );
				break;

			case 'pingback': // Display a pingback:
				$Comment->permanent_link( T_('Pingback') );
				echo ' '.T_('from:').' ';
				$Comment->author( '', '#', '', '#', 'htmlbody', true, 'preferredname' );
				break;
		}
	?>

	<?php $Comment->rating(); ?>
	<div class="bCommentText">
		<?php $Comment->content() ?>
		<?php $Comment->reply_link( '<br />' ); /* Link for replying to the Comment */ ?>
	</div>
	<div class="bCommentSmallPrint">



	</div>
<?php
	echo $params['comment_end'];

	/* Changes every other comment to a different class */
	$glossyblue_oddcomment = 'alt' == $glossyblue_oddcomment ? '' : 'alt';
?>
<!-- ========== END of a COMMENT/TB/PB ========== -->