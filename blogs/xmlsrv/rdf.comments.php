<?php
	/**
	 * This template generates an RSS 1.0 (RDF) feed for the requested blog's latest posts
	 *
	 * See {@link http://web.resource.org/rss/1.0/}
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package xmlsrv
	 */
	$skin = '';								// We don't want this do be displayed in a skin !
	$disp = 'comments';				// What we want is the latest comments
	$show_statuses = array(); // Restrict to published comments
	/**
	 * Initialize everything:
	 */
	require dirname(__FILE__).'/../evocore/_blog_main.inc.php' ;
	$CommentList = & new CommentList( $blog, "'comment'", $show_statuses, '',	'',	'DESC',	'',	20 );
	header("Content-type: application/xml");
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?".">";
?>
<!-- generator="<?php echo $app_name ?>/<?php echo $app_version ?>" -->
<rdf:RDF xmlns="http://purl.org/rss/1.0/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"					xmlns:admin="http://webns.net/mvcb/" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel rdf:about="<?php $Blog->disp( 'blogurl', 'xmlattr' ) ?>">
	<title><?php
		$Blog->disp( 'name', 'xml' );
		single_cat_title( ' - ', 'xml' );
		single_month_title( ' - ', 'xml' );
		single_post_title( ' - ', 'xml' );
		last_comments_title( ' - ', 'xml' ) ;
	?></title>
	<link><?php $Blog->disp( 'lastcommentsurl', 'xml' ) ?></link>
	<description></description>
	<dc:language><?php $Blog->disp( 'locale', 'xml' ) ?></dc:language>
	<admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $app_version ?>"/>
	<sy:updatePeriod>hourly</sy:updatePeriod>
	<sy:updateFrequency>1</sy:updateFrequency>
	<sy:updateBase>2000-01-01T12:00+00:00</sy:updateBase>
	<items>
		<rdf:Seq>
		<?php while( $Comment = $CommentList->get_next() )
		{ // Loop through comments: ?>
			<rdf:li rdf:resource="<?php $Comment->permalink() ?>"/>
		<?php } // End of comment loop. ?>
		</rdf:Seq>
	</items>
</channel>
<?php $CommentList->restart();
while( $Comment = $CommentList->get_next() )
{ // Loop through comments: ?>
<item rdf:about="<?php $Comment->permalink() ?>">
	<title><?php echo format_to_output( T_('In response to:'), 'xml' ) ?> <?php $Comment->Item->title( '', '', false, 'xml' ) ?></title>
	<link><?php $Comment->permalink() ?></link>
	<dc:date><?php $Comment->date( 'isoZ', true ); ?></dc:date>
	<dc:creator><?php $Comment->author( '', '#', '', '#', 'xml' ) ?></dc:creator>
	<description><?php $Comment->content( 'xml' ) ?></description>
	<content:encoded><![CDATA[<?php $Comment->content() ?>]]></content:encoded>
</item>
<?php } // End of comment loop. ?>
</rdf:RDF>
<?php log_hit(); // log the hit on this page ?>