<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;
global $blog, $admin_url;
global $Settings;

$Form = new Form();

$form_title = '';

$Form->begin_form( 'fform', $form_title );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'metadata' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Meta data').get_manual_link('blog-meta-data') );
	$social_media_boilerplate_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => T_('Select logo for social media boilerplate'), 'root' => 'shared_0', 'size_name' => 'fit-320x320' );
	$Form->fileselect( 'social_media_image_file_ID', $edited_Blog->get_setting( 'social_media_image_file_ID' ), T_('Social media boilerplate'), NULL, $social_media_boilerplate_params );

	$shortdesc_chars_count = utf8_strlen( html_entity_decode( $edited_Blog->get( 'shortdesc' ) ) );
	$Form->text( 'blog_shortdesc', $edited_Blog->get( 'shortdesc' ), 60, T_('Short Description'), T_('This is is used in meta tag description and RSS feeds. NO HTML!')
		.' ('.sprintf( T_('%s characters'), '<span id="blog_shortdesc_chars_count">'.$shortdesc_chars_count.'</span>' ).')', 250, 'large' );

	$Form->textarea( 'blog_longdesc', $edited_Blog->get( 'longdesc' ), 5, T_('Long Description'), T_('This will be used in Open Graph tags and XML feeds. This may also be displayed by widgets in the front-office.')
		.' '.T_(' HTML markup possible but not recommended.'), 50 );

	$Form->text( 'blog_keywords', $edited_Blog->get( 'keywords' ), 60, T_('Keywords'), T_('This is is used in meta tag keywords. NO HTML!'), 250, 'large' );

	$publisher_logo_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => T_('Select publisher logo'), 'root' => 'shared_0', 'size_name' => 'fit-320x320' );
	$Form->fileselect( 'blog_publisher_logo_file_ID', $edited_Blog->get_setting( 'publisher_logo_file_ID' ), T_('Publisher logo'), T_('This is used to add Structured Data to your pages.'), $publisher_logo_params );

	$Form->text( 'blog_publisher_name', $edited_Blog->get_setting( 'publisher_name' ), 60, T_('Publisher name'), T_('This is used to add Structured Data to your pages.'), 250, 'large' );

	$Form->text( 'blog_footer_text', $edited_Blog->get_setting( 'blog_footer_text' ), 60, T_('Blog footer'), sprintf(
		T_('Use &lt;br /&gt; to insert a line break. You might want to put your copyright or <a href="%s" target="_blank">creative commons</a> notice here.'),
		'http://creativecommons.org/license/' ), 1000, 'large' );

	$Form->textarea( 'single_item_footer_text', $edited_Blog->get_setting( 'single_item_footer_text' ), 2, T_('Single post footer'),
		T_('This will be displayed after each post in single post view.').' '.sprintf( T_('Available variables: %s.'), '<b>$perm_url$</b>, <b>$title$</b>, <b>$excerpt$</b>, <b>$author$</b>, <b>$author_login$</b>' ), 50 );

	$Form->textarea( 'xml_item_footer_text', $edited_Blog->get_setting( 'xml_item_footer_text' ), 2, T_('Post footer in RSS/Atom'),
		T_('This will be appended to each post in your RSS/Atom feeds.').' '.sprintf( T_('Available variables: %s.'), T_('same as above') ), 50 );

$Form->end_fieldset();


$Form->buttons( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

$Form->end_form();

?>
<script>
jQuery( '#blog_shortdesc' ).keyup( function()
{	// Count characters of meta short description(each html entity is counted as single char):
	jQuery( '#blog_shortdesc_chars_count' ).html( jQuery( this ).val().replace( /&[^;\s]+;/g, '&' ).length );
} );
</script>