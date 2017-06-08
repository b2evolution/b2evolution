<?php
/**
 * This template generates an Atom feed for the requested blog's latest comments
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * See {@link http://atompub.org/2005/07/11/draft-ietf-atompub-format-10.html}
 *
 * @package evoskins
 * @subpackage atom
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// What level of detail do we want?
$feed_content = $Blog->get_setting('comment_feed_content');
if( $feed_content == 'none' )
{	// We don't want to provide this feed!
	// This will normaly have been detected earlier but just for security:
	debug_die( 'Feeds are disabled.');
}

if( !$Blog->get_setting( 'comments_latest' ) )
{ // The latest comments are disabled for current blog
	// Redirect to page with text/html mime type
	header_redirect( get_dispctrl_url( 'comments' ), 302 );
	// will have exited
}

$post_ID = NULL;
if( isset($Item) )
{	// Comments for a specific Item:
  $post_ID = $Item->ID;
}

$CommentList = new CommentList2( $Blog );

// Filter list:
$CommentList->set_filters( array(
		'types' => array( 'comment' ),
		'statuses' => array ( 'published' ),
		'post_ID' => $post_ID,
		'order' => 'DESC',
		'comments' => $Blog->get_setting('comments_per_feed'),
	) );

// Get ready for display (runs the query):
$CommentList->display_init();


if($debug)
{
	headers_content_mightcache( 'application/xml' );		// In most situations, you do NOT want to cache dynamic content!
}
else
{
	headers_content_mightcache( 'application/atom+xml' );		// In most situations, you do NOT want to cache dynamic content!
}


echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.'>';
?>
<feed xml:lang="<?php $Blog->disp( 'locale', 'xml' ) ?>" xmlns="http://www.w3.org/2005/Atom">
	<title><?php
		$Blog->disp( 'name', 'xml' );
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title( array(
				'title_before'=> ' - ',
				'title_after' => '',
				'title_none'  => '',
				'glue'        => ' - ',
				'title_single_disp' => true,
				'format'      => 'xml',
			) );
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?></title>
	<link rel="alternate" type="text/html" href="<?php $Blog->disp( 'lastcommentsurl', 'xml' ) ?>" />
	<link rel="self" type="application/atom+xml" href="<?php $Blog->disp( 'comments_atom_url', 'xmlattr' ) ?>" />
	<id><?php $Blog->disp( 'comments_atom_url', 'xmlattr' ) /* TODO: may need a regenerate_url() */ ?></id>
	<generator uri="http://b2evolution.net/" version="<?php echo $app_version ?>"><?php echo $app_name ?></generator>
	<updated><?php echo gmdate('Y-m-d\TH:i:s\Z'); ?></updated>
	<?php while( $Comment = & $CommentList->get_next() )
	{	/* Loop through comments: */ ?>
	<entry>
		<title type="text"><?php
			echo format_to_output( T_('In response to').':', 'xml' ).' ';
				$Comment->get_Item();
				$Comment->Item->title( array(
				'format' => 'xml',
				'link_type' => 'none',
			) ); ?></title>
		<link rel="alternate" type="text/html" href="<?php $Comment->permanent_url() ?>" />
		<author>
			<name><?php $Comment->author( '', '#', '', '#', 'xml' ) ?></name>
			<?php $Comment->author_url( '', '<uri>', "</uri>\n", false ) ?>
		</author>
		<id><?php $Comment->permanent_url() ?></id>
		<published><?php $Comment->date( 'isoZ', true ); ?></published>
		<updated><?php $Comment->date( 'isoZ', true ); ?></updated>
		<?php
		$content = $Comment->get_content();
		if( $feed_content == 'excerpt' )
		{
			$content = excerpt($content);
		}
		?>
		<content type="html"><![CDATA[<?php echo format_to_output( $content, 'htmlfeed' ); ?>]]></content>
	</entry>
	<?php
	} // End of comment loop.
	?>
</feed>
