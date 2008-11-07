<?php
/**
 * This template generates an RSS 2.0 feed for the requested blog's latest comments
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * See {@link http://backend.userland.com/rss}
 *
 * @package evoskins
 * @subpackage rss
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


if( isset($Item) )
{	// Comments for a specific Item:
  $CommentList = & new CommentList( $Blog, "'comment'", array('published'), $Item->ID,
  																	'', 'DESC', '', $Blog->get_setting('posts_per_feed') );
	$selfurl = format_to_output( $Item->get_feedback_feed_url( '_rss2' ), 'xmlattr' );
}
else
{	// Comments for the blog:
  $CommentList = & new CommentList( $Blog, "'comment'", array('published'), '',
  																	'',	'DESC',	'',	$Blog->get_setting('posts_per_feed') );
	$selfurl = format_to_output( $Blog->get_comment_feed_url( '_rss2' ), 'xmlattr' );
}

header_content_type( 'application/xml' );	// Sets charset!

echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.'>';

?>
<!-- generator="<?php echo $app_name ?>/<?php echo $app_version ?>" -->
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:admin="http://webns.net/mvcb/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">
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
		<atom:link rel="self" type="application/rss+xml" href="<?php echo $selfurl; ?>" />
		<description></description>
		<language><?php $Blog->disp( 'locale', 'xml' ) ?></language>
		<docs>http://backend.userland.com/rss</docs>
		<admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $app_version ?>"/>
		<ttl>60</ttl>
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
			<pubDate><?php $Comment->time( 'r', true ); ?></pubDate>
			<dc:creator><?php $Comment->author( '', '#', '', '#', 'xml' ); ?></dc:creator>
			<guid isPermaLink="false">c<?php $Comment->ID() ?>@<?php echo $baseurl ?></guid>
			<description><?php echo make_rel_links_abs( $Comment->get_content('entityencoded') ); ?></description>
			<content:encoded><![CDATA[<?php echo make_rel_links_abs( $Comment->get_content() ); ?>]]></content:encoded>
			<link><?php $Comment->permanent_url() ?></link>
		</item>
		<?php
		} /* End of comment loop. */
	?>
	</channel>
</rss>
