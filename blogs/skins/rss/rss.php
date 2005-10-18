<?php
	/**
   * This template generates an RSS 0.92 feed for the requested blog's latest posts
	 *
	 * See {@link http://backend.userland.com/rss092}
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package xmlsrv
	 */
  $skin = '';										// We don't want this do be displayed in a skin !
	$show_statuses = array();     // Restrict to published posts
	$timestamp_min = '';					// Show past
	$timestamp_max = 'now';				// Hide future
	/**
	 * Initialize everything:
	 */
	$resolve_extra_path = false;	// We don't want extra path resolution on this page

    require $core_root.'/evocore/_blog_main.inc.php' ;

  header("Content-type: application/xml");
  echo "<?xml version=\"1.0\"?".">";
?>
<!-- generator="<?php echo $app_name; ?>/<?php echo $app_version ?>" -->
<rss version="0.92">
  <channel>
		<title><?php
			$Blog->disp( 'name', 'xml' );
			request_title( ' - ', '', ' - ', 'xml' );
		?></title>
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
<?php $Hit->log();  // log the hit on this page ?>
