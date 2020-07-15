<?php
/**
 * This file implements the UI view for the Collection SEO properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

global $preset;

global $rsc_url;

?>
<script>
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

$Form->begin_fieldset( TB_('General').get_manual_link( 'general-seo' ) );
	$Form->checkbox( 'tags_open_graph', $edited_Blog->get_setting( 'tags_open_graph' ), TB_('Open Graph'),
			sprintf( /* TRANS: %s replaced with <code><head></code> */ TB_('Include Open Graph tags in the %s section'), '<code>&lt;head&gt;</code>' ).' (og:title, og:url, og:description, og:type and og:image)' );

	$Form->checkbox( 'tags_twitter_card', $edited_Blog->get_setting( 'tags_twitter_card' ), TB_('Twitter Card'),
			sprintf( /* TRANS: %s replaced with <code><head></code> */ TB_('Include Twitter Summary card in the %s section'), '<code>&lt;head&gt;</code>' ) );

	$Form->checkbox( 'tags_structured_data', $edited_Blog->get_setting( 'tags_structured_data' ), TB_('Structured Data'),
			sprintf( /* TRANS: %s replaced with <code></body></code> */ TB_('Include Structured Data before %s'), '<code>&lt;/body&gt;</code>' ) );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('Special Front Page').' <span class="text-muted">(disp=front)</span>'.get_manual_link( 'special-front-page-seo' ) );
	$Form->checkbox( 'default_noindex', $edited_Blog->get_setting( 'default_noindex' ), TB_('Indexing'), TB_('META NOINDEX') );

	$Form->checklist( array(
		array( 'canonical_homepage', 1, TB_('301 redirect to canonical URL when possible'), $edited_Blog->get_setting( 'canonical_homepage' ) ),
		array( 'relcanonical_homepage', 1, TB_('Use rel="canonical" whenever necessary'), $edited_Blog->get_setting( 'relcanonical_homepage' ) ),
		array( 'self_canonical_homepage', 1, TB_('Use rel="canonical" even when not necessary (self-refering)'), $edited_Blog->get_setting( 'self_canonical_homepage' ) ),
		), 'canonical_homepage_options', TB_('Make canonical') );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('Browsing posts pages').' <span class="text-muted">(disp=posts)</span>'.get_manual_link('main-page-seo') );
	$Form->checkbox( 'posts_firstpage_noindex', $edited_Blog->get_setting( 'posts_firstpage_noindex' ), TB_('First posts page'), TB_('META NOINDEX') );

	$Form->checklist( array(
			array( 'paged_noindex', 1, TB_('META NOINDEX').' - '.TB_('Page 2,3,4, etc. without intro'), $edited_Blog->get_setting( 'paged_noindex' ) ),
			array( 'paged_intro_noindex', 1, TB_('META NOINDEX').' - '.TB_('Page 2,3,4, etc. with an intro'), $edited_Blog->get_setting( 'paged_intro_noindex' ) ),
			array( 'paged_nofollowto', 1, TB_('NOFOLLOW on links to').' '.TB_('Page 2,3,4...'), $edited_Blog->get_setting( 'paged_nofollowto' ) ),
		), 'paged', TB_('Next posts pages') );

	$Form->checklist( array(
		array( 'canonical_posts', 1, TB_('301 redirect to canonical URL when possible'), $edited_Blog->get_setting( 'canonical_posts' ) ),
		array( 'relcanonical_posts', 1, TB_('Use rel="canonical" whenever necessary'), $edited_Blog->get_setting( 'relcanonical_posts' ) ),
		array( 'self_canonical_posts', 1, TB_('Use rel="canonical" even when not necessary (self-refering)'), $edited_Blog->get_setting( 'self_canonical_posts' ) ),
		), 'canonical_posts_options', TB_('Make canonical') );

	$Form->radio( 'title_link_type', $edited_Blog->get_setting( 'title_link_type' ), array(
			  array( 'permalink', TB_('Link to the permanent url of the post') ),
			  array( 'linkto_url', TB_('Link to the "link to URL" specified in the post (if any)') ),
			  array( 'auto', TB_('Link to the "link to URL" if specified, otherwise fall back to permanent url') ),
			  array( 'none', TB_('No links on titles') ),
			), TB_('Post titles'), true );
	// TODO: checkbox display "permalink" separately from the title

	$Form->radio( 'main_content', $edited_Blog->get_setting('main_content'),
		array(
				array( 'excerpt', TB_('Post excerpts'), '('.TB_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', TB_('Standard post contents (stopping at "[teaserbreak]")'), '('.TB_('Teaser images will be displayed').')' ),
				array( 'full', TB_('Full post contents (including after "[teaserbreak]")'), '('.TB_('All images will be displayed').')' ),
			), TB_('Post contents'), true );

 	$Form->radio( 'permalinks', $edited_Blog->get_setting('permalinks'), array(
			  array( 'single', TB_('Link to single post') ),
			  array( 'archive', TB_('Link to post in archive') ),
			  array( 'subchap', TB_('Link to post in sub-category') ),
			), TB_('Permalinks'), true );
$Form->end_fieldset();


$Form->begin_fieldset( TB_('Single post pages / "Permalink" pages').get_manual_link('single-post-pages-seo') );

	$Form->checkbox( 'single_noindex', $edited_Blog->get_setting( 'single_noindex' ), TB_('Indexing'), TB_('META NOINDEX') );

	$Form->radio( 'single_links', $edited_Blog->get_setting('single_links'),
		array(
			  array( 'param_num', TB_('Use param: post ID'), TB_('E-g: ')
			  				.url_add_param( $blogurl, '<strong>p=123&amp;more=1</strong>' ) ),
			  array( 'param_title', TB_('Use param: post title'), TB_('E-g: ')
			  				.url_add_param( $blogurl, '<strong>title=post-title&amp;more=1</strong>' ) ),
				array( 'short', TB_('Use extra-path: post title'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/post-title</strong>' ) ),
				array( 'y', TB_('Use extra-path: year'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2006/post-title</strong>' ) ),
				array( 'ym', TB_('Use extra-path: year & month'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2006/12/post-title</strong>' ) ),
				array( 'ymd', TB_('Use extra-path: year, month & day'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2006/12/31/post-title</strong>' ) ),
				array( 'subchap', TB_('Use extra-path: sub-category'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/subcat/post-title</strong>' ) ),
				array( 'chapters', TB_('Use extra-path: category path'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/cat/subcat/post-title</strong>' ) ),
			), TB_('Permalink scheme'), true );

	$Form->text_input( 'slug_limit', $edited_Blog->get_setting('slug_limit'), 3, TB_('Limit slug length to'), '', array( 'input_suffix' => ' '.TB_('words') ) );

	$Form->checklist( array(
		array( 'canonical_item_urls', 1, sprintf( TB_('301 redirect to canonical URL when possible (but not to <a %s>External canonical URL</a> %s)'), 'href="'.get_manual_url( 'external-canonical-url' ).'"', get_pro_label() ), $edited_Blog->get_setting( 'canonical_item_urls' ) ),
		array( 'allow_crosspost_urls', 1, TB_('Do not 301 redirect cross-posted Items'), $edited_Blog->get_setting( 'allow_crosspost_urls' ), ! $edited_Blog->get_setting( 'canonical_item_urls' ) ),
		array( 'relcanonical_item_urls', 1, TB_('Use rel="canonical" whenever necessary'), $edited_Blog->get_setting( 'relcanonical_item_urls' ) ),
		array( 'self_canonical_item_urls', 1, TB_('Use rel="canonical" even when not necessary (self-refering)'), $edited_Blog->get_setting( 'self_canonical_item_urls' ) ),
		), 'canonical_item_urls_options', TB_('Make canonical') );

	$Form->checkbox( 'excerpts_meta_description', $edited_Blog->get_setting( 'excerpts_meta_description' ),
			TB_('Meta description'), TB_('When no meta description is provided for an item, use the excerpt instead.') );

	$Form->checkbox( 'tags_meta_keywords', $edited_Blog->get_setting( 'tags_meta_keywords' ),
			TB_('Meta Keywords'), TB_('When no meta keywords are provided for an item, use tags instead.') );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('"By date" archives').get_manual_link('archive-pages-seo') );

	$Form->radio( 'archive_links', $edited_Blog->get_setting('archive_links'),
		array(
				array( 'param', TB_('Use param'), TB_('E-g: ')
								.url_add_param( $blogurl, '<strong>m=20071231</strong>' ) ),
				array( 'extrapath', TB_('Use extra-path'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2007/12/31/</strong>' ) ),
			), TB_('Date archive URLs'), true );

	$Form->checklist( array(
		array( 'canonical_archive_urls', 1, TB_('301 redirect to canonical URL when possible'), $edited_Blog->get_setting( 'canonical_archive_urls' ) ),
		array( 'relcanonical_archive_urls', 1, TB_('Use rel="canonical" whenever necessary'), $edited_Blog->get_setting( 'relcanonical_archive_urls' ) ),
		array( 'self_canonical_archive_urls', 1, TB_('Use rel="canonical" even when not necessary (self-refering)'), $edited_Blog->get_setting( 'self_canonical_archive_urls' ) ),
		), 'canonical_archive_urls_options', TB_('Make canonical') );

	$Form->checkbox( 'archive_noindex', $edited_Blog->get_setting( 'archive_noindex' ), TB_('Indexing'), TB_('META NOINDEX') );
	$Form->checkbox( 'archive_nofollowto', $edited_Blog->get_setting( 'archive_nofollowto' ), TB_('Follow TO'), TB_('NOFOLLOW on links to').' '.TB_('date archives') );

	$Form->radio( 'archive_content', $edited_Blog->get_setting('archive_content'),
		array(
				array( 'excerpt', TB_('Post excerpts'), '('.TB_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', TB_('Standard post contents (stopping at "[teaserbreak]")'), '('.TB_('Teaser images will be displayed').')' ),
				array( 'full', TB_('Full post contents (including after "[teaserbreak]")'), '('.TB_('All images will be displayed').')' ),
			), TB_('Post contents'), true );

	$Form->text( 'archive_posts_per_page', $edited_Blog->get_setting('archive_posts_per_page'), 4, TB_('Posts per page'),
								TB_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->checkbox( 'arcdir_noindex', $edited_Blog->get_setting( 'arcdir_noindex' ), TB_('Archive directory'), TB_('META NOINDEX') );

$Form->end_fieldset();

$Form->begin_fieldset( TB_('Category pages').get_manual_link('category-pages-seo') );

	$Form->radio( 'chapter_links', $edited_Blog->get_setting('chapter_links'),
		array(
				array( 'param_num', TB_('Use param: cat ID'), TB_('E-g: ')
								.url_add_param( $blogurl, '<strong>cat=123</strong>' ),'', 'onclick="show_hide_chapter_prefix(this);"'),
				array( 'subchap', TB_('Use extra-path: sub-category'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/subcat/</strong>' ), '', 'onclick="show_hide_chapter_prefix(this);"' ),
				array( 'chapters', TB_('Use extra-path: category path'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/cat/subcat/</strong>' ), '', 'onclick="show_hide_chapter_prefix(this);"' ),
			), TB_('Category URLs'), true );

		echo '<div id="category_prefix_container">';
			$Form->text_input( 'category_prefix', $edited_Blog->get_setting( 'category_prefix' ), 30, TB_('Prefix'),
														TB_('An optional prefix to be added to the URLs of the categories'),
														array('maxlength' => 120) );
		echo '</div>';
		if( $edited_Blog->get_setting( 'chapter_links' ) == 'param_num' )
		{ ?>
		<script>
			<!--
			var fldset = document.getElementById( 'category_prefix_container' );
			fldset.style.display = 'none';
			//-->
		</script>
		<?php
		}

	$Form->checklist( array(
		array( 'canonical_cat_urls', 1, TB_('301 redirect to canonical URL when possible'), $edited_Blog->get_setting( 'canonical_cat_urls' ) ),
		array( 'relcanonical_cat_urls', 1, TB_('Use rel="canonical" whenever necessary'), $edited_Blog->get_setting( 'relcanonical_cat_urls' ) ),
		array( 'self_canonical_cat_urls', 1, TB_('Use rel="canonical" even when not necessary (self-refering)'), $edited_Blog->get_setting( 'self_canonical_cat_urls' ) ),
		), 'canonical_cat_urls_options', TB_('Make canonical') );

	$Form->checklist( array(
		array( 'chapter_noindex', 1, TB_('META NOINDEX for category pages without intro'), $edited_Blog->get_setting( 'chapter_noindex' ) ),
		array( 'chapter_intro_noindex', 1, TB_('META NOINDEX for category pages with an intro'), $edited_Blog->get_setting( 'chapter_intro_noindex' ) ),
		), 'chapter_noindex', TB_('Indexing') );

	$Form->radio( 'chapter_content', $edited_Blog->get_setting('chapter_content'),
		array(
				array( 'excerpt', TB_('Post excerpts'), '('.TB_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', TB_('Standard post contents (stopping at "[teaserbreak]")'), '('.TB_('Teaser images will be displayed').')' ),
				array( 'full', TB_('Full post contents (including after "[teaserbreak]")'), '('.TB_('All images will be displayed').')' ),
			), TB_('Post contents'), true );

	$Form->text( 'chapter_posts_per_page', $edited_Blog->get_setting('chapter_posts_per_page'), 4, TB_('Posts per page'),
								TB_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->checkbox( 'catdir_noindex', $edited_Blog->get_setting( 'catdir_noindex' ), TB_('Category directory'), TB_('META NOINDEX') );
	$Form->checkbox( 'categories_meta_description', $edited_Blog->get_setting( 'categories_meta_description' ),
			TB_('Meta description'), TB_('Use category description as meta description for category pages') );

	$Form->end_fieldset();


$Form->begin_fieldset( TB_('Tag pages').get_manual_link('tag-pages-seo'), array('id'=>'tag_links_fieldset') );

	$Form->radio( 'tag_links', $edited_Blog->get_setting('tag_links'),
		array(
			array( 'param', TB_('Use param'), TB_('E-g: ')
				.url_add_param( $blogurl, '<strong>tag=mytag</strong>' ) ),
			array( 'prefix-only', TB_('Use extra-path').': '.'Use URL path prefix only (recommended)', TB_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag</strong>' ) ),
			array( 'dash', TB_('Use extra-path').': '.'trailing dash', TB_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag-</strong>' ) ),
			array( 'colon', TB_('Use extra-path').': '.'trailing colon', TB_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag:</strong>' ) ),
			array( 'semicolon', TB_('Use extra-path').': '.'trailing semi-colon (NOT recommended)', TB_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag;</strong>' ) ),
		), TB_('Tag page URLs'), true );


	$Form->text_input( 'tag_prefix', $edited_Blog->get_setting( 'tag_prefix' ), 30, TB_('Prefix'),
		TB_('An optional prefix to be added to the URLs of the tag pages'),
		array('maxlength' => 120) );

	$Form->checkbox( 'tag_rel_attrib', $edited_Blog->get_setting( 'tag_rel_attrib' ), TB_('Rel attribute'),
		sprintf( TB_('Add <a %s>rel="tag" attribute</a> to tag links.'), 'href="http://microformats.org/wiki/rel-tag"' ) );

	$Form->checklist( array(
		array( 'canonical_tag_urls', 1, TB_('301 redirect to canonical URL when possible'), $edited_Blog->get_setting( 'canonical_tag_urls' ) ),
		array( 'relcanonical_tag_urls', 1, TB_('Use rel="canonical" whenever necessary'), $edited_Blog->get_setting( 'relcanonical_tag_urls' ) ),
		array( 'self_canonical_tag_urls', 1, TB_('Use rel="canonical" even when not necessary (self-refering)'), $edited_Blog->get_setting( 'self_canonical_tag_urls' ) ),
		), 'canonical_tag_urls_options', TB_('Make canonical') );

	$Form->checklist( array(
		array( 'tag_noindex', 1, TB_('META NOINDEX for tag pages without intro'), $edited_Blog->get_setting( 'tag_noindex' ) ),
		array( 'tag_intro_noindex', 1, TB_('META NOINDEX for tag pages with an intro'), $edited_Blog->get_setting( 'tag_intro_noindex' ) ),
		), 'tag_noindex', TB_('Indexing') );

	$Form->radio( 'tag_content', $edited_Blog->get_setting('tag_content'),
		array(
				array( 'excerpt', TB_('Post excerpts'), '('.TB_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', TB_('Standard post contents (stopping at "[teaserbreak]")'), '('.TB_('Teaser images will be displayed').')' ),
				array( 'full', TB_('Full post contents (including after "[teaserbreak]")'), '('.TB_('All images will be displayed').')' ),
			), TB_('Post contents'), true );

	$Form->text( 'tag_posts_per_page', $edited_Blog->get_setting('tag_posts_per_page'), 4, TB_('Posts per page'),
								TB_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->end_fieldset();

// Javascript juice for the tag fields.
?>
<script>
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
$Form->begin_fieldset( TB_('User profile pages').get_manual_link( 'user_pages_seo' ), array( 'id' => 'user_links_fieldset' ) );
	$Form->checklist( array(
		array( 'canonical_user_urls', 1, TB_('301 redirect to canonical URL when possible'), $edited_Blog->get_setting( 'canonical_user_urls' ) ),
		), 'canonical_user_urls_options', TB_('Make canonical') );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('Other filtered pages').get_manual_link('other-filtered-pages-seo') );
	$Form->checklist( array(
		array( 'filtered_noindex', 1, TB_('META NOINDEX for filtered pages without intro'), $edited_Blog->get_setting( 'filtered_noindex' ) ),
		array( 'filtered_intro_noindex', 1, TB_('META NOINDEX for filtered pages with an intro'), $edited_Blog->get_setting( 'filtered_intro_noindex' ) ),
		), 'filtered_noindex', TB_('Indexing'), false, false, array( 'note' => TB_('Filtered by keyword search, by author, etc.') ) );

	$Form->radio( 'filtered_content', $edited_Blog->get_setting('filtered_content'),
		array(
				array( 'excerpt', TB_('Post excerpts'), '('.TB_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', TB_('Standard post contents (stopping at "[teaserbreak]")'), '('.TB_('Teaser images will be displayed').')' ),
				array( 'full', TB_('Full post contents (including after "[teaserbreak]")'), '('.TB_('All images will be displayed').')' ),
			), TB_('Post contents'), true );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('Download pages').get_manual_link( 'download-display-seo' ) );
	$Form->checkbox( 'download_noindex', $edited_Blog->get_setting( 'download_noindex' ), TB_('Indexing'), TB_('META NOINDEX') );
	$Form->checkbox( 'download_nofollowto', $edited_Blog->get_setting( 'download_nofollowto' ), TB_('No Follow TO'), TB_('NOFOLLOW on links leading to download pages') );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('Contact/Message Form pages').get_manual_link( 'contact-message-form-pages-seo' ) );
	$Form->checkbox( 'msgform_noindex', $edited_Blog->get_setting( 'msgform_noindex' ), TB_('Indexing'),
										TB_('META NOINDEX').' - '.TB_('WARNING: Letting search engines index contact forms will attract spam.') );
	$Form->checkbox( 'msgform_nofollowto', $edited_Blog->get_setting( 'msgform_nofollowto' ), TB_('No Follow TO'), TB_('NOFOLLOW on links leading to contact/message form pages') );
	$Form->text_input( 'msgform_redirect_slug', $edited_Blog->get_setting( 'msgform_redirect_slug' ), 100, TB_('Default redirect after message send'), TB_('Enter slug or leave empty to redirect to front page of current collection.') );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('Other pages').get_manual_link('other-pages-seo') );
	$Form->checkbox( 'feedback-popup_noindex', $edited_Blog->get_setting( 'feedback-popup_noindex' ), TB_('Comment popups'),
										TB_('META NOINDEX').' - '.TB_('For skins with comment popups only.') );
	$Form->checkbox( 'special_noindex', $edited_Blog->get_setting( 'special_noindex' ), TB_('Other special pages'),
										TB_('META NOINDEX').' - '.TB_('Pages with no index setting of their own... yet.') );
	$Form->radio( '404_response', $edited_Blog->get_setting('404_response'),
		array(
				array( '200', TB_('200 "OK" response') ),
				array( '301', sprintf( /* TRANS: 301, 302, 303... */ TB_('%s redirect to main page'), '301' ) ),
				array( '302', sprintf( /* TRANS: 301, 302, 303... */ TB_('%s redirect to main page'), '302' ) ),
				array( '303', sprintf( /* TRANS: 301, 302, 303... */ TB_('%s redirect to main page'), '303' ) ),
				array( '404', TB_('404 "Not Found" response') ),
				array( '410', TB_('410 "Gone" response') ),
			), TB_('404 "Not Found" response'), true );

	$Form->radio( 'help_link', $edited_Blog->get_setting('help_link'),
		array(
			array( 'param', TB_('Use param').': ?disp=help', TB_('E-g: ')
				.url_add_param( $blogurl, '<strong>disp=help</strong>' ) ),
			array( 'slug', TB_('Use extra-path').': '.'/help', TB_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/help</strong>' ) ),
			), TB_('Help page'), true );
$Form->end_fieldset();


$Form->end_form( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton' ) ) );

?>
<script>
jQuery( 'input[name=canonical_item_urls]' ).click( function()
{
	var canonical_item_urls_is_unchecked = ! jQuery( this ).prop( 'checked' );
	jQuery( 'input[name=allow_crosspost_urls]' ).prop( 'disabled', canonical_item_urls_is_unchecked );
	if( canonical_item_urls_is_unchecked )
	{	// When "301 redirect to canonical URL" is disabled then we always must NOT do 301 redirect cross-posted Items:
		jQuery( 'input[name=allow_crosspost_urls]' ).prop( 'checked', true );
	}
} );
</script>