<?php
/**
 * This template generates an RSS 0.92 feed for the requested blog's latest comments
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * See {@link http://backend.userland.com/rss092}
 *
 * @package evoskins
 * @subpackage rss
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$Post_ID = NULL;
if( isset($Item) )
{	// Comments for a specific Item:
  $Post_ID = $Item->ID;
}

$CommentList = new CommentList2( $Blog );

// Filter list:
$CommentList->set_filters( array(
		'types' => array( 'comment' ),
		'statuses' => array ( 'published' ),
		'post_ID' => $Post_ID,
		'order' => 'DESC',
		'comments' => $Blog->get_setting('posts_per_feed'),
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
			<description><?php echo make_rel_links_abs( $Comment->get_content('entityencoded') ); ?></description>
			<link><?php $Comment->permanent_url() ?></link>
		</item>
		<?php } // End of comment loop. ?>
  </channel>
</rss>
