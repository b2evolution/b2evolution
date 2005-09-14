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
	$skin = '';								// We don't want this do be displayed in a skin !
	$disp = 'comments';				// What we want is the latest comments
	$show_statuses = array(); // Restrict to published comments
	/**
	 * Initialize everything:
	 */
	$resolve_extra_path = false;	// We don't want extra path resolution on this page
	require dirname(__FILE__).'/../evocore/_blog_main.inc.php';
	$CommentList = & new CommentList( $blog, "'comment'", $show_statuses, '',	'',	'DESC',	'',	20 );
	header("Content-type: application/atom+xml");
	echo '<?xml version="1.0" encoding="utf-8"?'.'>';
?>
<feed version="0.3" xml:lang="<?php $Blog->disp( 'locale', 'xml' ) ?>" xmlns="http://purl.org/atom/ns#">
	<title><?php
		$Blog->disp( 'name', 'xml' );
		request_title( ' - ', '', ' - ', 'xml' );
	?></title>
	<link rel="alternate" type="text/html" href="<?php $Blog->disp( 'lastcommentsurl', 'xml' ) ?>" />
	<generator url="http://b2evolution.net/" version="<?php echo $app_version ?>"><?php echo $app_name ?></generator>
	<modified><?php echo gmdate('Y-m-d\TH:i:s\Z'); ?></modified>
	<?php while( $Comment = $CommentList->get_next() )
	{ // Loop through comments: ?>
	<entry>
		<title type="text/plain" mode="xml"><?php echo format_to_output( T_('In response to:'), 'xml' ) ?> <?php $Comment->Item->title( '', '', false, 'xml' ) ?></title>
		<link rel="alternate" type="text/html" href="<?php $Comment->permalink() ?>" />
		<author>
			<name><?php $Comment->author( '', '#', '', '#', 'xml' ) ?></name>
			<?php $Comment->author_url( '', '<url>', "</url>\n", false ) ?>
		</author>
		<id><?php $Comment->permalink() ?></id>
		<issued><?php $Comment->date( 'isoZ', true ); ?></issued>
		<modified><?php $Comment->date( 'isoZ', true ); ?></modified>
		<content type="text/html" mode="escaped"><![CDATA[<?php $Comment->content() ?>]]></content>
	</entry>
	<?php } // End of comment loop. ?>
</feed>
<?php $Hit->log(); // log the hit on this page ?>