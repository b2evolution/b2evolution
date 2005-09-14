<?php
	/**
	 * This template generates an Atom feed for the requested blog's latest posts
	 *
	 * See {@link http://www.mnot.net/drafts/draft-nottingham-atom-format-02.html}
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package xmlsrv
	 */
	$skin = '';										// We don't want this do be displayed in a skin !
	$show_statuses = array();			// Restrict to published posts
	$timestamp_min = '';					// Show past
	$timestamp_max = 'now';				// Hide future
	/**
	 * Initialize everything:
	 */
	$resolve_extra_path = false;	// We don't want extra path resolution on this page
	require dirname(__FILE__).'/../evocore/_blog_main.inc.php';
	header("Content-type: application/atom+xml");
	// header("Content-type: text/xml");
	echo '<?xml version="1.0" encoding="utf-8"?'.'>';
?>
<feed version="0.3" xml:lang="<?php $Blog->disp( 'locale', 'xml' ) ?>" xmlns="http://purl.org/atom/ns#">
	<title><?php
		$Blog->disp( 'name', 'xml' );
		request_title( ' - ', '', ' - ', 'xml' );
	?></title>
	<link rel="alternate" type="text/html" href="<?php $Blog->disp( 'blogurl', 'xml' ) ?>" />
	<tagline><?php $Blog->disp( 'shortdesc', 'xml' ) ?></tagline>
	<generator url="http://b2evolution.net/" version="<?php echo $app_version ?>"><?php echo $app_name ?></generator>
	<modified><?php $MainList->mod_date( 'isoZ', true ) ?></modified>
	<?php while( $Item = $MainList->get_item() ) {	?>
	<entry>
		<title type="text/plain" mode="xml"><?php $Item->title( '', '', false, 'xml' ) ?></title>
		<link rel="alternate" type="text/html" href="<?php $Item->permalink( 'single' ) ?>" />
		<author>
			<name><?php $Item->Author->prefered_name( 'xml' ) ?></name>
			<?php $Item->Author->url( '<url>', "</url>\n", 'xml' ) ?>
		</author>
		<id><?php $Item->permalink( 'single' ) ?></id>
		<issued><?php $Item->issue_date( 'isoZ', true ) ?></issued>
		<modified><?php $Item->mod_date( 'isoZ', true ) ?></modified>
		<content type="text/html" mode="escaped"><![CDATA[<?php
			$Item->url_link( '<p>', '</p>' );
			$Item->content()
		?>]]></content>
	</entry>
	<?php } ?>
</feed>
<?php $Hit->log(); // log the hit on this page ?>