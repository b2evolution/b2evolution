<?php
/**
 * This file implements the UI view for the Collection SEO properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 *
 * @version $Id: _coll_seo.form.php 6650 2014-05-09 09:22:38Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

global $preset;

global $rsc_url;

?>
<script type="text/javascript">
	function show_hide_chapter_prefix(ob)
	{
		var fldset = document.getElementById( 'category_prefix_container' );
		if( ob.value == 'param_num' )
		{
			fldset.style.display = 'none';
		}
		else
		{
			fldset.style.display = '';
		}
	}
</script>

<?php

$blogurl = $edited_Blog->gen_blogurl();

$Form = new Form( NULL, 'coll_features_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'seo' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Main page / post list').get_manual_link('main_page_seo') );
	$Form->checkbox( 'default_noindex', $edited_Blog->get_setting( 'default_noindex' ), T_('Default blog page'), T_('META NOINDEX') );
	$Form->checklist( array(
		array( 'canonical_homepage', 1, T_('301 redirect to canonical URL when possible'), $edited_Blog->get_setting( 'canonical_homepage' ) ),
		array( 'relcanonical_homepage', 1, T_('Use rel="canonical" if not 301 redirected'), $edited_Blog->get_setting( 'relcanonical_homepage' ) ),
		), 'canonical_homepage_options', T_('Make canonical') );

	$Form->checkbox( 'paged_noindex', $edited_Blog->get_setting( 'paged_noindex' ), T_('"Next" blog pages'), T_('META NOINDEX').' - '.T_('Page 2,3,4...') );
	$Form->checkbox( 'paged_nofollowto', $edited_Blog->get_setting( 'paged_nofollowto' ), '', T_('NOFOLLOW on links to').' '.T_('Page 2,3,4...') );

	$Form->radio( 'title_link_type', $edited_Blog->get_setting( 'title_link_type' ), array(
			  array( 'permalink', T_('Link to the permanent url of the post') ),
			  array( 'linkto_url', T_('Link to the "link to URL" specified in the post (if any)') ),
			  array( 'auto', T_('Link to the "link to URL" if specified, otherwise fall back to permanent url') ),
			  array( 'none', T_('No links on titles') ),
			), T_('Post titles'), true );
	// TODO: checkbox display "permalink" separately from the title

	$Form->radio( 'main_content', $edited_Blog->get_setting('main_content'),
		array(
				array( 'excerpt', T_('Post excerpts') ),
				array( 'normal', T_('Standard post contents (stopping at "[teaserbreak]")') ),
				array( 'full', T_('Full post contents (including after "[teaserbreak]")') ),
			), T_('Post contents'), true );

 	$Form->radio( 'permalinks', $edited_Blog->get_setting('permalinks'), array(
			  array( 'single', T_('Link to single post') ),
			  array( 'archive', T_('Link to post in archive') ),
			  array( 'subchap', T_('Link to post in sub-category') ),
			), T_('Permalinks'), true );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Single post pages / "Permalink" pages').get_manual_link('single_post_pages_seo') );

	$Form->radio( 'single_links', $edited_Blog->get_setting('single_links'),
		array(
			  array( 'param_num', T_('Use param: post ID'), T_('E-g: ')
			  				.url_add_param( $blogurl, '<strong>p=123&amp;more=1</strong>' ) ),
			  array( 'param_title', T_('Use param: post title'), T_('E-g: ')
			  				.url_add_param( $blogurl, '<strong>title=post-title&amp;more=1</strong>' ) ),
				array( 'short', T_('Use extra-path: post title'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/post-title</strong>' ) ),
				array( 'y', T_('Use extra-path: year'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2006/post-title</strong>' ) ),
				array( 'ym', T_('Use extra-path: year & month'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2006/12/post-title</strong>' ) ),
				array( 'ymd', T_('Use extra-path: year, month & day'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2006/12/31/post-title</strong>' ) ),
				array( 'subchap', T_('Use extra-path: sub-category'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/subcat/post-title</strong>' ) ),
				array( 'chapters', T_('Use extra-path: category path'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/cat/subcat/post-title</strong>' ) ),
			), T_('Permalink scheme'), true );

	$Form->text_input( 'slug_limit', $edited_Blog->get_setting('slug_limit'), 3, T_('Limit slug length to'), '', array( 'input_suffix' => ' '.T_('words') ) );

	$Form->checklist( array(
		array( 'canonical_item_urls', 1, T_('301 redirect to canonical URL when possible'), $edited_Blog->get_setting( 'canonical_item_urls' ) ),
		array( 'relcanonical_item_urls', 1, T_('Use rel="canonical" if not 301 redirected'), $edited_Blog->get_setting( 'relcanonical_item_urls' ) ),
		), 'canonical_item_urls_options', T_('Make canonical') );

	$Form->checkbox( 'excerpts_meta_description', $edited_Blog->get_setting( 'excerpts_meta_description' ),
			T_('Meta description'), T_('When no meta description is provided for an item, use the excerpt instead.') );

	$Form->checkbox( 'tags_meta_keywords', $edited_Blog->get_setting( 'tags_meta_keywords' ),
			T_('Meta Keywords'), T_('When no meta keywords are provided for an item, use tags instead.') );

	$Form->checkbox( 'tags_open_graph', $edited_Blog->get_setting( 'tags_open_graph' ),
			T_('Open Graph'), T_('Include open graph tags like og:image and og:type.') );

$Form->end_fieldset();

$Form->begin_fieldset( T_('"By date" archives').get_manual_link('archive_pages_seo') );

	$Form->radio( 'archive_links', $edited_Blog->get_setting('archive_links'),
		array(
				array( 'param', T_('Use param'), T_('E-g: ')
								.url_add_param( $blogurl, '<strong>m=20071231</strong>' ) ),
				array( 'extrapath', T_('Use extra-path'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2007/12/31/</strong>' ) ),
			), T_('Date archive URLs'), true );

	$Form->checklist( array(
		array( 'canonical_archive_urls', 1, T_('301 redirect to canonical URL when possible'), $edited_Blog->get_setting( 'canonical_archive_urls' ) ),
		array( 'relcanonical_archive_urls', 1, T_('Use rel="canonical" if not 301 redirected'), $edited_Blog->get_setting( 'relcanonical_archive_urls' ) ),
		), 'canonical_archive_urls_options', T_('Make canonical') );

	$Form->checkbox( 'archive_noindex', $edited_Blog->get_setting( 'archive_noindex' ), T_('Indexing'), T_('META NOINDEX') );
	$Form->checkbox( 'archive_nofollowto', $edited_Blog->get_setting( 'archive_nofollowto' ), T_('Follow TO'), T_('NOFOLLOW on links to').' '.T_('date archives') );

	$Form->radio( 'archive_content', $edited_Blog->get_setting('archive_content'),
		array(
				array( 'excerpt', T_('Post excerpts') ),
				array( 'normal', T_('Standard post contents (stopping at "[teaserbreak]")') ),
				array( 'full', T_('Full post contents (including after "[teaserbreak]")') ),
			), T_('Post contents'), true );

	$Form->text( 'archive_posts_per_page', $edited_Blog->get_setting('archive_posts_per_page'), 4, T_('Posts per page'),
								T_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->checkbox( 'arcdir_noindex', $edited_Blog->get_setting( 'arcdir_noindex' ), T_('Archive directory'), T_('META NOINDEX') );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Category pages').get_manual_link('category_pages_seo') );

	$Form->radio( 'chapter_links', $edited_Blog->get_setting('chapter_links'),
		array(
				array( 'param_num', T_('Use param: cat ID'), T_('E-g: ')
								.url_add_param( $blogurl, '<strong>cat=123</strong>' ),'', 'onclick="show_hide_chapter_prefix(this);"'),
				array( 'subchap', T_('Use extra-path: sub-category'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/subcat/</strong>' ), '', 'onclick="show_hide_chapter_prefix(this);"' ),
				array( 'chapters', T_('Use extra-path: category path'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/cat/subcat/</strong>' ), '', 'onclick="show_hide_chapter_prefix(this);"' ),
			), T_('Category URLs'), true );

		echo '<div id="category_prefix_container">';
			$Form->text_input( 'category_prefix', $edited_Blog->get_setting( 'category_prefix' ), 30, T_('Prefix'),
														T_('An optional prefix to be added to the URLs of the categories'),
														array('maxlength' => 120) );
		echo '</div>';
		if( $edited_Blog->get_setting( 'chapter_links' ) == 'param_num' )
		{ ?>
		<script type="text/javascript">
			<!--
			var fldset = document.getElementById( 'category_prefix_container' );
			fldset.style.display = 'none';
			//-->
		</script>
		<?php
		}

	$Form->checklist( array(
		array( 'canonical_cat_urls', 1, T_('301 redirect to canonical URL when possible'), $edited_Blog->get_setting( 'canonical_cat_urls' ) ),
		array( 'relcanonical_cat_urls', 1, T_('Use rel="canonical" if not 301 redirected'), $edited_Blog->get_setting( 'relcanonical_cat_urls' ) ),
		), 'canonical_cat_urls_options', T_('Make canonical') );

	$Form->checkbox( 'chapter_noindex', $edited_Blog->get_setting( 'chapter_noindex' ), T_('Indexing'), T_('META NOINDEX') );

	$Form->radio( 'chapter_content', $edited_Blog->get_setting('chapter_content'),
		array(
				array( 'excerpt', T_('Post excerpts') ),
				array( 'normal', T_('Standard post contents (stopping at "[teaserbreak]")') ),
				array( 'full', T_('Full post contents (including after "[teaserbreak]")') ),
			), T_('Post contents'), true );

	$Form->text( 'chapter_posts_per_page', $edited_Blog->get_setting('chapter_posts_per_page'), 4, T_('Posts per page'),
								T_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->checkbox( 'catdir_noindex', $edited_Blog->get_setting( 'catdir_noindex' ), T_('Category directory'), T_('META NOINDEX') );
	$Form->checkbox( 'categories_meta_description', $edited_Blog->get_setting( 'categories_meta_description' ),
			T_('Meta description'), T_('Use category description as meta description for category pages') );

	$Form->end_fieldset();


$Form->begin_fieldset( T_('Tag pages').get_manual_link('tag_pages_seo'), array('id'=>'tag_links_fieldset') );

	$Form->radio( 'tag_links', $edited_Blog->get_setting('tag_links'),
		array(
			array( 'param', T_('Use param'), T_('E-g: ')
				.url_add_param( $blogurl, '<strong>tag=mytag</strong>' ) ),
			array( 'prefix-only', T_('Use extra-path').': '.'Use URL path prefix only (recommended)', T_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag</strong>' ) ),
			array( 'dash', T_('Use extra-path').': '.'trailing dash', T_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag-</strong>' ) ),
			array( 'colon', T_('Use extra-path').': '.'trailing colon', T_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag:</strong>' ) ),
			array( 'semicolon', T_('Use extra-path').': '.'trailing semi-colon (NOT recommended)', T_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag;</strong>' ) ),
		), T_('Tag page URLs'), true );


	$Form->text_input( 'tag_prefix', $edited_Blog->get_setting( 'tag_prefix' ), 30, T_('Prefix'),
		T_('An optional prefix to be added to the URLs of the tag pages'),
		array('maxlength' => 120) );

	$Form->checkbox( 'tag_rel_attrib', $edited_Blog->get_setting( 'tag_rel_attrib' ), T_('Rel attribute'),
		sprintf( T_('Add <a %s>rel="tag" attribute</a> to tag links.'), 'href="http://microformats.org/wiki/rel-tag"' ) );

	$Form->checklist( array(
		array( 'canonical_tag_urls', 1, T_('301 redirect to canonical URL when possible'), $edited_Blog->get_setting( 'canonical_tag_urls' ) ),
		array( 'relcanonical_tag_urls', 1, T_('Use rel="canonical" if not 301 redirected'), $edited_Blog->get_setting( 'relcanonical_tag_urls' ) ),
		), 'canonical_tag_urls_options', T_('Make canonical') );

	$Form->checkbox( 'tag_noindex', $edited_Blog->get_setting( 'tag_noindex' ), T_('Indexing'), T_('META NOINDEX') );

	$Form->radio( 'tag_content', $edited_Blog->get_setting('tag_content'),
		array(
				array( 'excerpt', T_('Post excerpts') ),
				array( 'normal', T_('Standard post contents (stopping at "[teaserbreak]")') ),
				array( 'full', T_('Full post contents (including after "[teaserbreak]")') ),
			), T_('Post contents'), true );

	$Form->text( 'tag_posts_per_page', $edited_Blog->get_setting('tag_posts_per_page'), 4, T_('Posts per page'),
								T_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->end_fieldset();

// Javascript juice for the tag fields.
?>
<script type="text/javascript">
jQuery("#tag_links_fieldset input[name=tag_links][type=radio]").click( function()
{
	// Disable tag_prefix, if "param" is used. fp> TODO: visual feedback that this is disabled
	if( jQuery( this ).val() == 'param' )
	{
		jQuery('#tag_prefix').attr("disabled", "disabled");
	}
	else
	{
		jQuery('#tag_prefix').removeAttr("disabled");
	}
	// Disable tag_rel_attrib, if "prefix-only" is not used.
	jQuery('#tag_rel_attrib').attr("disabled", this.value == 'prefix-only' ? "" : "disabled");

	// NOTE: dh> ".closest('fieldset').andSelf()" is required for the add-field_required-class-to-fieldset-hack. Remove as appropriate.
	if( this.value == 'prefix-only' )
		jQuery('#tag_prefix').closest('fieldset').andSelf().addClass('field_required');
	else
		jQuery('#tag_prefix').closest('fieldset').andSelf().removeClass('field_required');
} ).filter(":checked").click();

// Set text of span.tag_links_tag_prefix according to this field, defaulting to "tag" for "prefix-only".
jQuery("#tag_prefix").keyup( function() {
	jQuery("span.tag_links_tag_prefix").each(
		function() {
			var newval = ((jQuery("#tag_prefix").val().length || jQuery(this).closest("div").find("input[type=radio]").attr("value") != "prefix-only") ? jQuery("#tag_prefix").val() : "tag");
			if( newval.length ) newval += "/";
			jQuery(this).text( newval );
		}
	) } ).keyup();
</script>


<?php
$Form->begin_fieldset( T_('Other filtered pages').get_manual_link('other_filtered_pages_seo') );
	$Form->checkbox( 'filtered_noindex', $edited_Blog->get_setting( 'filtered_noindex' ), T_('Other filtered posts pages'), T_('META NOINDEX').' - '.T_('Filtered by keyword search, by author, etc.') );

	$Form->radio( 'filtered_content', $edited_Blog->get_setting('filtered_content'),
		array(
				array( 'excerpt', T_('Post excerpts') ),
				array( 'normal', T_('Standard post contents (stopping at "[teaserbreak]")') ),
				array( 'full', T_('Full post contents (including after "[teaserbreak]")') ),
			), T_('Post contents'), true );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Other pages').get_manual_link('other_pages_seo') );
	$Form->checkbox( 'feedback-popup_noindex', $edited_Blog->get_setting( 'feedback-popup_noindex' ), T_('Comment popups'),
										T_('META NOINDEX').' - '.T_('For skins with comment popups only.') );
	$Form->checkbox( 'msgform_noindex', $edited_Blog->get_setting( 'msgform_noindex' ), T_('Contact forms'),
										T_('META NOINDEX').' - '.T_('WARNING: Letting search engines index contact forms will attract spam.') );
	$Form->checkbox( 'special_noindex', $edited_Blog->get_setting( 'special_noindex' ), T_('Other special pages'),
										T_('META NOINDEX').' - '.T_('Pages with no index setting of their own... yet.') );
	$Form->radio( '404_response', $edited_Blog->get_setting('404_response'),
		array(
				array( '200', T_('200 "OK" response') ),
				array( '301', T_('301 redirect to main page') ),
				array( '302', T_('302 redirect to main page') ),
				array( '303', T_('303 redirect to main page') ),
				array( '404', T_('404 "Not found" response') ),
				array( '410', T_('410 "Gone" response') ),
			), T_('404 "Not Found" response'), true );

	$Form->radio( 'help_link', $edited_Blog->get_setting('help_link'),
		array(
			array( 'param', T_('Use param').': ?disp=help', T_('E-g: ')
				.url_add_param( $blogurl, '<strong>disp=help</strong>' ) ),
			array( 'slug', T_('Use extra-path').': '.'/help', T_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/help</strong>' ) ),
			), T_('Help page'), true );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Download display').get_manual_link( 'download-display-seo' ) );
	$Form->checkbox( 'download_noindex', $edited_Blog->get_setting( 'download_noindex' ), T_('Indexing'), T_('META NOINDEX') );
	$Form->checkbox( 'download_nofollowto', $edited_Blog->get_setting( 'download_nofollowto' ), T_('No Follow TO'), T_('NOFOLLOW on links leading to download pages') );
$Form->end_fieldset();


$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>
