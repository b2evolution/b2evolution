<?php
  /*
   * This template generates an RSS 0.92 feed for the requested blog's latest posts
   * (http://backend.userland.com/rss092)
   */
  $skin = '';										// We don't want this do be displayed in a skin !
	$show_statuses = array();     // Restrict to published posts
	$timestamp_min = '';					// Show past
	$timestamp_max = 'now';				// Hide future
  require dirname(__FILE__).'/../b2evocore/_blog_main.php';
  header("Content-type: text/xml");
  echo "<?xml version=\"1.0\"?".">";
?>
<!-- generator="b2evolution/<?php echo $b2_version ?>" -->
<rss version="0.92">
  <channel>
    <title><?php $Blog->disp( 'name', 'xml' ) ?></title>
    <link><?php $Blog->disp( 'blogurl', 'xml' ) ?></link>
    <description><?php $Blog->disp( 'shortdesc' ,'xml' ) ?></description>
    <language><?php $Blog->disp( 'locale', 'xml' ) ?></language>
    <docs>http://backend.userland.com/rss092</docs>
    <?php while( $Item = $MainList->get_item() ) { ?>
    <item>
      <title><?php $Item->title( '', '', false, 'xml' ) ?></title>
      <description><?php
        $Item->url_link( '', ' ', 'entityencoded' );
        $Item->content( 1, false, T_('[...] Read more!'), '', '', '', 'entityencoded' );
      ?></description>
      <link><?php $Item->permalink( 'single' ) ?></link>
    </item>
    <?php } ?>
  </channel>
</rss>
<?php log_hit();  // log the hit on this page ?>