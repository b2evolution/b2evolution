<?php
	/*
	 * This template generates an RSS 2.0 feed for the requested blog
	 * (http://backend.userland.com/rss)
	 */
	$skin = '';										// We don't want this do be displayed in a skin !
	$show_statuses = array();			// Restrict to published posts
	$timestamp_min = '';					// Show past
	$timestamp_max = 'now';				// Hide future
	require dirname(__FILE__).'/../b2evocore/_blog_main.php' ;
	header("Content-type: text/xml");
	echo "<?xml version=\"1.0\"?".">";
?>
<!-- generator="b2evolution/<?php echo $b2_version ?>" -->
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:admin="http://webns.net/mvcb/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	<channel>
		<title><?php $Blog->disp( 'name', 'xml' ) ?></title>
		<link><?php $Blog->disp( 'blogurl', 'xml' ) ?></link>
		<description><?php $Blog->disp( 'shortdesc', 'xml' ) ?></description>
		<language><?php $Blog->disp( 'locale', 'xml' ) ?></language>
		<docs>http://backend.userland.com/rss</docs>
		<admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $b2_version ?>"/>
		<ttl>60</ttl>
		<?php while( $Item = $MainList->get_item() ) {	?>
		<item>
			<title><?php $Item->title( '', '', false, 'xml' ) ?></title>
			<link><?php $Item->permalink( 'single' ) ?></link>
			<pubDate><?php $Item->issue_date( 'r', true ) ?></pubDate>
			<?php // Disabled because of spambots: <author><php the_author_email( 'xml' ) ></author>?>
			<?php $Item->categories( false, '<category domain="main">', '</category>', '<category domain="alt">', '</category>', '<category domain="external">', '</category>', "\n", 'xml' ) ?>
			<guid isPermaLink="false"><?php the_ID() ?>@<?php echo $baseurl ?></guid>
			<description><?php
				$Item->url_link( '', ' ', 'xml' );
				$Item->content( 1, false, T_('[...] Read more!'), '', '', '', 'xml', $rss_excerpt_length );
			?></description>
			<content:encoded><![CDATA[<?php
				$Item->url_link( '<p>', '</p>' );
				$Item->content()
			?>]]></content:encoded>
			<comments><?php comments_link( '', 1, 1 ) ?></comments>
		</item>
		<?php } ?>
	</channel>
</rss>
<?php log_hit(); // log the hit on this page ?>