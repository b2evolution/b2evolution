<?php
	/**
	 * This template generates an RSS 1.0 (RDF) feed for the requested blog's latest posts
	 *
	 * See {@link http://web.resource.org/rss/1.0/}
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package xmlsrv
	 */
	$skin = '';													 // We don't want this do be displayed in a skin !
	$show_statuses = array();			// Restrict to published posts
	$timestamp_min = '';								// Show past
	$timestamp_max = 'now';							// Hide future
	/**
	 * Initialize everything:
	 */
	require dirname(__FILE__).'/../evocore/_blog_main.inc.php' ;
	header("Content-type: application/xml");
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?".">";
?>
<!-- generator="<?php echo $app_name; ?>/<?php echo $app_version ?>" -->
<rdf:RDF xmlns="http://purl.org/rss/1.0/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"					xmlns:admin="http://webns.net/mvcb/" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel rdf:about="<?php $Blog->disp( 'blogurl', 'xmlattr' ) ?>">
	<title><?php
		$Blog->disp( 'name', 'xml' );
		single_cat_title( ' - ', 'xml' );
		single_month_title( ' - ', 'xml' );
		single_post_title( ' - ', 'xml' );
	?></title>
	<link><?php $Blog->disp( 'blogurl', 'xml' ) ?></link>
	<description><?php $Blog->disp( 'shortdesc', 'xml' ) ?></description>
	<dc:language><?php $Blog->disp( 'locale', 'xml' ) ?></dc:language>
	<admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $app_version ?>"/>
	<sy:updatePeriod>hourly</sy:updatePeriod>
	<sy:updateFrequency>1</sy:updateFrequency>
	<sy:updateBase>2000-01-01T12:00+00:00</sy:updateBase>
	<items>
		<rdf:Seq>
		<?php while( $Item = $MainList->get_item() ) { ?>
			<rdf:li rdf:resource="<?php $Item->permalink( 'single' ) ?>"/>
		<?php } ?>
		</rdf:Seq>
	</items>
</channel>
<?php
$MainList->restart();
while( $Item = $MainList->get_item() )
{ ?>
<item rdf:about="<?php $Item->permalink( 'single' ) ?>">
	<title><?php $Item->title( '', '', false, 'xml' ) ?></title>
	<link><?php $Item->permalink( 'single' ) ?></link>
	<dc:date><?php $Item->issue_date( 'isoZ', true ) ?></dc:date>
	<dc:creator><?php $Item->Author->prefered_name( 'xml' ) ?></dc:creator>
	<dc:subject><?php $Item->main_category( 'xml' ) ?></dc:subject>
	<description><?php
		$Item->url_link( '', ' ', 'xml' );
		$Item->content( 1, false, T_('[...] Read more!'), '', '', '', 'xml', $rss_excerpt_length );
	?></description>
	<content:encoded><![CDATA[<?php
		$Item->url_link( '<p>', '</p>' );
		$Item->content()
	?>]]></content:encoded>
</item>
<?php } ?>
</rdf:RDF>
<?php $Hit->log(); // log the hit on this page ?>