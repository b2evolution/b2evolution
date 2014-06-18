<?php
/**
 * This is the template that displays a single comment
 *
 * This file is not meant to be called directly.
 *
 * @package evoskins
 * @subpackage terrafirma
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
    'comment_start'  => '<div class="bComment">',
    'comment_end'    => '</div>',
    'Comment'        => NULL, // This object MUST be passed as a param!
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
	<div class="bCommentTitle">
	<?php
		if( $Comment->status != 'published' )
		{
			$Comment->status( 'styled' );
		}

		if( !empty($Comment->author_user_ID) && $Comment->author_user_ID == $Item->Author->ID )
		{	// This comment was posted by the author
			// Di special color?
		}


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
							'after'     => ' ',
							'text' 	    => '&#035;',
							'nofollow'	=> true,
						) );
				}
				$Comment->author( '', '', '', '#', 'htmlbody', true, 'preferredname' );
				$Comment->msgform_link( $Blog->get('msgformurl') );
				break;

			case 'trackback': // Display a trackback:
				$Comment->permanent_link( array(
						'before'    => '',
						'after'     => ' ',
						'text' 	    => T_('Trackback: '),
						'nofollow'	=> true,
					) );
				$Comment->author( '', '#', '', '#', 'htmlbody', true, 'preferredname' );
				break;

			case 'pingback': // Display a pingback:
				$Comment->permanent_link( array(
						'before'    => '',
						'after'     => ' ',
						'text' 	    => T_('Pingback: '),
						'nofollow'	=> true,
					) );
				$Comment->author( '', '#', '', '#', 'htmlbody', true, 'preferredname' );
				break;
		}
	?>
	<em>on <?php $Comment->date() ?> at <?php $Comment->time( 'H:i' ) ?>

	<?php
		$comment_Item = & $Comment->get_Item();
		$Comment->edit_link( ' &nbsp; ', ' ', '#', '#', '', '&amp;', true, rawurlencode( $Comment->get_permanent_url() ) ); /* Link for editing */
		$Comment->delete_link( ' &nbsp; ', ' ', '#', '#', '', false, '&amp;', true, false, '#', rawurlencode( $comment_Item->get_permanent_url() ) ); /* Link for deleting */
	?>
	</em>

	<?php
		$Comment->rating();
	?>
	</div>
	<div class="bCommentText">
		<?php
			$Comment->content();
		?>
		<?php $Comment->reply_link( '<br />' ); /* Link for replying to the Comment */ ?>
	</div>
<?php
  echo $params['comment_end'];
?>
<!-- ========== END of a COMMENT/TB/PB ========== -->
