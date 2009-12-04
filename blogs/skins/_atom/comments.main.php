<?php
/**
 * This template generates an Atom feed for the requested blog's latest comments
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * See {@link http://atompub.org/2005/07/11/draft-ietf-atompub-format-10.html}
 *
 * @package evoskins
 * @subpackage atom
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


if( isset($Item) )
{	// Comments for a specific Item:
  $CommentList = & new CommentList( $Blog, "'comment'", array('published'), $Item->ID,
  																	'', 'DESC', '', $Blog->get_setting('posts_per_feed') );
}
else
{	// Comments for the blog:
  $CommentList = & new CommentList( $Blog, "'comment'", array('published'), '',
  																	'',	'DESC',	'',	$Blog->get_setting('posts_per_feed') );
}


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
	{ /* Loop through comments: */ ?>
	<entry>
		<title type="text"><?php
			echo format_to_output( T_('In response to:'), 'xml' ).' ';
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
		<content type="html"><![CDATA[<?php echo make_rel_links_abs( $Comment->get_content() ); ?>]]></content>
	</entry>
	<?php
	} // End of comment loop.
	?>
</feed>
