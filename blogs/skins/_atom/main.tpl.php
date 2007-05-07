<?php
/**
 * This template generates an Atom feed for the requested blog's latest posts
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

// Note: even if we request the same post as $Item earlier, the following will do more restrictions (dates, etc.)
// Init the MainList object:
init_MainList( $Blog->get_setting('posts_per_feed') );

if( $debug)
{
	skin_content_header( 'application/xml' );	// Sets charset!
}
else
{
	skin_content_header( 'application/atom+xml' );	// Sets charset!
}

echo '<?xml version="1.0" encoding="'.$io_charset.'"?'.'>';
?>
<feed xml:lang="<?php $Blog->disp( 'locale', 'xml' ) ?>" xmlns="http://www.w3.org/2005/Atom">
	<title><?php
		$Blog->disp( 'name', 'xml' );
		request_title( ' - ', '', ' - ', 'xml' );
	?></title>
	<link rel="alternate" type="text/html" href="<?php $Blog->disp( 'blogurl', 'xml' ) ?>" />
	<link rel="self" type="application/atom+xml" href="<?php $Blog->disp( 'atom_url', 'xmlattr' ) ?>" />
	<id><?php $Blog->disp( 'atom_url', 'xmlattr' ); /* TODO: may need a regenerate_url() */ ?></id>
	<subtitle><?php $Blog->disp( 'shortdesc', 'xml' ) ?></subtitle>
	<generator uri="http://b2evolution.net/" version="<?php echo $app_version ?>"><?php echo $app_name ?></generator>
	<updated><?php echo gmdate('Y-m-d\TH:i:s\Z'); ?></updated>
	<?php
	while( $Item = & $MainList->get_item() )
	{
		// Load Item's creator User:
		$Item->get_creator_User();
		?>

	<entry>
		<title type="text"><?php $Item->title( '', '', false, 'xml' ) ?></title>
		<link rel="alternate" type="text/html" href="<?php $Item->permanent_url( 'single' ) ?>" />
		<author>
			<name><?php $Item->creator_User->preferred_name( 'xml' ) ?></name>
			<?php $Item->creator_User->url( '<uri>', "</uri>\n", 'xml' ) ?>
		</author>
		<id><?php $Item->permanent_url( 'single' ) ?></id>
		<published><?php $Item->issue_date( 'isoZ', true ) ?></published>
		<updated><?php $Item->mod_date( 'isoZ', true ) ?></updated>
		<content type="html"><![CDATA[<?php
				$Item->url_link( '<p>', '</p>' );

				// Display images that are linked to this post:
				$content = $Item->get_images( array(
						'before' =>              '<div>',
						'before_image' =>        '<div>',
						'before_image_legend' => '<div><i>',
						'after_image_legend' =>  '</i></div>',
						'after_image' =>         '</div>',
						'after' =>               '</div>',
						'image_size' =>          'fit-320x320'
					), 'htmlbody' );

				$content .= $Item->get_content_teaser( 1, false );

				$content .= $Item->get_more_link( '', '', '#', '', 1 );

				// fp> this is another one of these "oooooh it's just a tiny little change"
				// and "we only need to make the links absolute in RSS"
				// and then you get half baked code! The URL LINK stays RELATIVE!! :((
				// TODO: clean solution : work in format_to_output! --- we probably need 'htmlfeed' as 'htmlbody+absolute'
				echo make_rel_links_abs( $content );
		?>]]></content>
	</entry>

	<?php
	}
	?>
</feed>
<?php
	$Hit->log(); // log the hit on this page

	// This is a self contained XML document, make sure there is no additional output:
	exit();
?>