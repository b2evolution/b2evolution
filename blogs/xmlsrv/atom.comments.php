<?php
	/*
	 * This template generates an Atom feed for the requested blog's latest posts
	 * (http://www.mnot.net/drafts/draft-nottingham-atom-format-02.html)
	 */
	$skin = '';								// We don't want this do be displayed in a skin !
	$disp = 'comments';				// What we want is the latest comments
	$show_statuses = array(); // Restrict to published comments
	require dirname(__FILE__).'/../b2evocore/_blog_main.php';
	$CommentList = & new CommentList( $blog, "'comment'", $show_statuses );
	header("Content-type: application/atom+xml");
	echo '<?xml version="1.0" encoding="utf-8"?'.'>';
?>
<feed version="0.3" xml:lang="<?php $Blog->disp( 'locale', 'xml' ) ?>" xmlns="http://purl.org/atom/ns#">
	<title><?php $Blog->disp( 'name', 'xml' ); last_comments_title( ' : ', 'xml' ) ?></title>
	<link rel="alternate" type="text/html" href="<?php $Blog->disp( 'lastcommentsurl', 'xml' ) ?>" />
	<generator url="http://b2evolution.net/" version="<?php echo $b2_version ?>">b2evolution</generator>
	<modified><?php echo gmdate('Y-m-d\TH:i:s\Z'); ?></modified>
	<?php while( $Comment = $CommentList->get_next() )
	{ // Loop through comments: ?>
	<entry>
		<title type="text/plain" mode="xml"><?php echo format_to_output( T_('In response to:'), 'xml' ) ?> <?php $Comment->Item->title( '', '', false, 'xml' ) ?></title>
		<link rel="alternate" type="text/html" href="<?php $Comment->permalink() ?>" />
		<author>
			<name><?php $Comment->author( 'xml' ) ?></name>
			<?php $Comment->author_url( '', '<url>', "</url>\n", false ) ?>
		</author>
		<id><?php $Comment->permalink() ?></id>
		<issued><?php $Comment->date( 'isoZ', true ); ?></issued>
		<modified><?php $Comment->date( 'isoZ', true ); ?></modified>
		<content type="text/html" mode="escaped"><![CDATA[<?php $Comment->content() ?>]]></content>
	</entry>
	<?php } // End of comment loop. ?>
</feed>
<?php log_hit(); // log the hit on this page ?>