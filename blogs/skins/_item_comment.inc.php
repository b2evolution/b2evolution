<?php
/**
 * This is the template that displays a single comment
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
    'comment_start'  => '<div class="bComment">',
    'comment_end'    => '</div>',
		'link_to'		     => 'userurl>userpage',		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
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
				$Comment->author2( array(
						'before'       => ' ',
						'after'        => '#',
						'before_user'  => '',
						'after_user'   => '#',
						'format'       => 'htmlbody',
						'link_to'		   => $params['link_to'],		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
						'link_text'    => 'preferredname',
					) );

				$Comment->msgform_link( $Blog->get('msgformurl') );
				// $Comment->author_url( '', ' &middot; ', '' );
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
		<?php
			$Comment->avatar();
			$Comment->content();
		?>
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
 * Revision 1.9  2010/11/07 18:50:45  fplanque
 * Added Comment::author2() with skins v2 style params.
 *
 * Revision 1.8  2010/02/08 17:56:10  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.7  2009/09/29 00:19:12  blueyed
 * Link author of comments by default, instead of displaying the URL separately. Document template function call. Leave old call to author_url commented.
 *
 * Revision 1.6  2009/09/16 21:29:31  sam2kb
 * Display user/visitor avatar in comments
 *
 * Revision 1.5  2009/03/08 23:57:56  fplanque
 * 2009
 *
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
