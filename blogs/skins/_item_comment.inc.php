<?php
/**
 * This is the template that displays a single comment
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
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
							'text' 			=> T_('Comment'),
							'nofollow'	=> true,
						) );
				}
				$Comment->author();
				$Comment->msgform_link( $Blog->get('msgformurl') );
				$Comment->author_url( '', ' &middot; ', '' );
				break;

			case 'trackback': // Display a trackback:
				$Comment->permanent_link( array(
						'before'    => '',
						'after'     => ' '.T_('from:').' ',
						'text' 			=> T_('Trackback'),
						'nofollow'	=> true,
					) );
				$Comment->author( '', '#', '', '#', 'htmlbody', true );
				break;

			case 'pingback': // Display a pingback:
				$Comment->permanent_link( array(
						'before'    => '',
						'after'     => ' '.T_('from:').' ',
						'text' 			=> T_('Pingback'),
						'nofollow'	=> true,
					) );
				$Comment->author( '', '#', '', '#', 'htmlbody', true );
				break;
		}
	?>
	</div>
	<?php $Comment->rating(); ?>
	<div class="bCommentText">
		<?php $Comment->content() ?>
	</div>
	<div class="bCommentSmallPrint">
		<?php
			$Comment->edit_link( '', '', '#', '#', 'permalink_right' ); /* Link to backoffice for editing */
			$Comment->delete_link( '', '', '#', '#', 'permalink_right' ); /* Link to backoffice for deleting */
		?>

		<?php $Comment->date() ?> @ <?php $Comment->time( 'H:i' ) ?>
	</div>
<?php
  echo $params['comment_end'];
?>
<!-- ========== END of a COMMENT/TB/PB ========== -->
<?php

/*
 * $Log$
 * Revision 1.4  2008/10/03 22:00:47  blueyed
 * Indent fixes
 *
 * Revision 1.3  2008/01/21 09:35:42  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/12/22 17:24:35  fplanque
 * cleanup
 *
 * Revision 1.1  2007/12/22 16:41:05  fplanque
 * Modular feedback template.
 *
 */
?>
