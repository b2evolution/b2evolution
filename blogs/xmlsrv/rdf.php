<?php
  /*
   * This template generates an RSS 1.0 (RDF) feed for the requested blog
   */
  $default_to_blog = 1; // This is the default. Should be overidden in url e-g: rss.php?blog=2
  $skin = '';                          // We don't want this do be displayed in a skin !
	$show_statuses = "'published'";     // Restrict to published posts
	$timestamp_min = '';								// Show past
	$timestamp_max = 'now';							// Hide future
  include dirname(__FILE__)."/../b2evocore/_blog_main.php";
  header("Content-type: text/xml");
  echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?".">";
?>
<!-- generator="b2evolution/<?php echo $b2_version ?>" -->
<rdf:RDF xmlns="http://purl.org/rss/1.0/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"         xmlns:admin="http://webns.net/mvcb/" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel rdf:about="<?php bloginfo('url', 'xml') ?>">
  <title><?php bloginfo('name', 'xml') ?></title>
  <link><?php bloginfo('link', 'xml') ?></link>
  <description><?php bloginfo('description', 'xml') ?></description>
  <dc:language><?php bloginfo( 'lang', 'xml' ) ?></dc:language>
  <admin:generatorAgent rdf:resource="http://b2evolution.net/?v=<?php echo $b2_version ?>"/>
  <sy:updatePeriod>hourly</sy:updatePeriod>
  <sy:updateFrequency>1</sy:updateFrequency>
  <sy:updateBase>2000-01-01T12:00+00:00</sy:updateBase>
  <items>
    <rdf:Seq>
    <?php while( $MainList->get_item() ) { ?>
      <rdf:li rdf:resource="<?php permalink_single() ?>"/>
    <?php } ?>
    </rdf:Seq>
  </items>
</channel>
<?php $MainList->restart(); while( $MainList->get_item() ) { ?>
<item rdf:about="<?php permalink_single() ?>">
  <title><?php the_title( '', '', false, 'xml' ) ?></title>
  <link><?php permalink_single() ?></link>
  <dc:date><?php the_time('Y-m-d\TH:i:s',1,1); ?></dc:date>
  <dc:creator><?php the_author( 'xml' ) ?></dc:creator>
  <dc:subject><?php the_category( 'xml' ) ?></dc:subject>
  <description><?php
    the_link( '', ' ', 'xml' );
    the_content(_('[...] Read more!'), 0, '', '', '', '', 'xml',$rss_excerpt_length );
  ?></description>
  <content:encoded><![CDATA[<?php
    the_link( '<p>', '</p>' );
    the_content()
  ?>]]></content:encoded>
</item>
<?php } ?>
</rdf:RDF>
<?php log_hit(); // log the hit on this page ?>