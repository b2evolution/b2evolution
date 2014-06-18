<?php
/**
 * This template generates an RSS 0.92 feed for the requested blog's latest comments
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * See {@link http://backend.userland.com/rss092}
 *
 * @package evoskins
 * @subpackage rss
 *
 * @version $Id: comments.main.php 3157 2013-03-06 04:34:44Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// What level of detail do we want?
$feed_content = $Blog->get_setting('comment_feed_content');
if( $feed_content == 'none' )
{	// We don't want to provide this feed!
	// This will normaly have been detected earlier but just for security:
	debug_die( 'Feeds are disabled.');
}

if( !$Blog->get_setting( 'comments_latest' ) )
{ // The latest comments are disabled for current blog
	// Redirect to page with text/html mime type
	header_redirect( get_dispctrl_url( 'comments' ), 302 );
	// will have exited
}

$post_ID = NULL;
if( isset($Item) )
{	// Comments for a specific Item:
  $post_ID = $Item->ID;
}

$CommentList = new CommentList2( $Blog );

// Filter list:
$CommentList->set_filters( array(
		'types' => array( 'comment' ),
		'statuses' => array ( 'published' ),
		'post_ID' => $post_ID,
		'order' => 'DESC',
		'comments' => $Blog->get_setting('comments_per_feed'),
	) );

// Get ready for display (runs the query):
$CommentList->display_init();

headers_content_mightcache( 'application/xml' );		// In most situations, you do NOT want to cache dynamic content!

echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.'>';
?>
<!-- generator="<?php echo $app_name; ?>/<?php echo $app_version ?>" -->
<rss version="0.92">
	<channel>
		<title><?php
			$Blog->disp( 'name', 'xml' );
			// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
			request_title( array(
					'title_before'=> ' - ',
					'title_after' => '',
					'title_none'  => '',
					'glue'        => ' - ',
					'title_single_disp' => true,
					'format'      => 'xml',
				) );
			// ------------------------------ END OF REQUEST TITLE -----------------------------
		?></title>
		<link><?php $Blog->disp( 'lastcommentsurl', 'xml' ) ?></link>
		<description></description>
		<language><?php $Blog->disp( 'locale', 'xml' ) ?></language>
		<docs>http://backend.userland.com/rss092</docs>
		<?php while( $Comment = & $CommentList->get_next() )
		{ // Loop through comments:
			// Load comment's Item:
			$Comment->get_Item();
			?>
		<item>
			<title><?php echo format_to_output( T_('In response to:'), 'xml' ) ?> <?php $Comment->Item->title( array(
				'format' => 'xml',
				'link_type' => 'none',
			) ); ?></title>
			<?php
			$content = $Comment->get_content();
			if( $feed_content == 'excerpt' )
			{
				$content = excerpt($content);
			}
			?><description><?php echo format_to_output( make_rel_links_abs($content), 'entityencoded' ); ?></description>
			<link><?php $Comment->permanent_url() ?></link>
		</item>
		<?php } // End of comment loop. ?>
  </channel>
</rss>
