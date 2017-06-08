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
 * @subpackage basic
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
		'comment_start'        => '<div class="bComment">',
		'comment_end'          => '</div>',
		'Comment'              => NULL, // This object MUST be passed as a param!
	), $params );

/**
 * @var Comment
 */
$Comment = & $params['Comment'];

?>
<!-- ========== START of a COMMENT/TB/PB ========== -->
<?php $Comment->anchor() ?>
<?php echo $params['comment_start']; ?>
<h5>
<?php
	switch( $Comment->get( 'type' ) )
	{
		case 'comment': // Display a comment:
			echo T_('Comment from:') ?>
			<?php $Comment->author( '', '#', '', '#', 'htmlbody', false, 'auto' ) ?>
			<?php $Comment->author_url( '', ' &middot; ', '' ) ?>
			<?php break;

		case 'trackback': // Display a trackback:
			echo T_('Trackback from:') ?>
			<?php $Comment->author( '', '#', '', '#', 'htmlbody', true, 'auto' ) ?>
			<?php break;

		case 'pingback': // Display a pingback:
			echo T_('Pingback from:') ?>
			<?php $Comment->author( '', '#', '', '#', 'htmlbody', true, 'auto' ) ?>
			<?php break;
	}

	$Comment->edit_link( ' &middot; ', ' ', '#', '#', '', '&amp;', true, $Comment->get_permanent_url() ); // Link to backoffice for editing

	if( $Comment->status != 'published' )
	{
		echo ' &middot; '.T_('Status').': '.$Comment->get_status();
	}
?>
</h5>
<blockquote>
	<small><?php $Comment->date() ?> @ <?php $Comment->time( '#short_time' ) ?></small>
	<div><?php $Comment->content() ?></div>
	<?php $Comment->reply_link(); /* Link for replying to the Comment */ ?>
</blockquote>
<?php echo $params['comment_end']; ?>
<!-- ========== END of a COMMENT/TB/PB ========== -->