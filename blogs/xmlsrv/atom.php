<?php
  /*
   * This template generates an Atom feed for the requested blog's latest posts
   * (http://www.mnot.net/drafts/draft-nottingham-atom-format-02.html)
   */
  $skin = '';                   // We don't want this do be displayed in a skin !
	$show_statuses = array();     // Restrict to published posts
	$timestamp_min = '';					// Show past
	$timestamp_max = 'now';				// Hide future
  require dirname(__FILE__)."/../b2evocore/_blog_main.php";
  header("Content-type: application/atom+xml");
  echo '<?xml version="1.0" encoding="utf-8"?'.'>';
?>
<feed version="0.3" xml:lang="<?php $Blog->disp( 'lang', 'xml' ) ?>" xmlns="http://purl.org/atom/ns#">
	<title><?php $Blog->disp( 'name', 'xml' ) ?></title>
	<link rel="alternate" type="text/html" href="<?php $Blog->disp( 'blogurl', 'xml' ) ?>" />
	<tagline><?php $Blog->disp( 'shortdesc', 'xml' ) ?></tagline>
	<generator url="http://b2evolution.net/" version="<?php echo $b2_version ?>">b2evolution</generator>
	<modified><?php echo gmdate('Y-m-d\TH:i:s\Z'); ?></modified>
	<?php while( $Item = $MainList->get_item() ) {  ?>
	<entry>
		<title type="text/plain" mode="xml"><?php $Item->title( '', '', false, 'xml' ) ?></title>
		<link rel="alternate" type="text/html" href="<?php $Item->permalink( 'single' ) ?>" />
		<author>
			<name><?php $Item->Author->prefered_name( 'xml' ) ?></name>
			<?php $Item->Author->url( '<url>', "</url>\n", 'xml' ) ?>
		</author>
		<id><?php $Item->permalink( 'single' ) ?></id>
		<modified><?php $Item->date( 'isoZ', true ) ?></modified>
		<issued><?php $Item->date( 'isoZ', true ) ?></issued>
		<content type="text/html" mode="escaped"><![CDATA[<?php
			$Item->url_link( '<p>', '</p>' );
			$Item->content()
		?>]]></content>
	</entry>
	<?php } ?>
</feed>
<?php log_hit(); // log the hit on this page ?>