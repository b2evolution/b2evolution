<?php
  /*
   * This template generates an RSS 0.92 feed for the requested blog
   * (http://backend.userland.com/rss092)
   */
  $default_to_blog = 1; // This is the default. Should be overidden in url e-g: rss.php?blog=2
  $skin = '';                         // We don't want this do be displayed in a skin ! 
	$show_statuses = "'published'";     // Restrict to published posts
	$timestamp_min = '';								// Show past								
	$timestamp_max = 'now';							// Hide future
  include dirname(__FILE__)."/../b2evocore/_blog_main.php";
  header("Content-type: text/xml");
  echo "<?xml version=\"1.0\"?".">";
?>
<!-- generator="b2evolution/<?php echo $b2_version ?>" -->
<rss version="0.92">
  <channel>
    <title><?php bloginfo( 'name', 'xml' ) ?></title>
    <link><?php bloginfo( 'link', 'xml' ) ?></link>
    <description><?php bloginfo( 'description' ,'xml') ?></description>
    <language><?php bloginfo( 'lang', 'xml' ) ?></language>
    <docs>http://backend.userland.com/rss092</docs>
    <?php while( $MainList->get_item() ) { ?>
    <item>
      <title><?php the_title( '', '', false, 'xml' ) ?></title>
      <description><?php
        the_link( '', ' ', 'xml' );
        the_content('[...] Read more!', 0, '', '', '', '', 'entityencoded' );
      ?></description>
      <link><?php permalink_single() ?></link>
    </item>
    <?php } ?>
  </channel>
</rss>
<?php log_hit();  // log the hit on this page ?>