<?php
/**
 * This file implements the UI view for the Collection SEO properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @version $Id$
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
	<!--
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


	function show_hide_tag_prefix(ob)
	{
		var fldset = document.getElementById( 'tag_prefix_container' );
		if( ob.value == 'param_num' )
		{
			fldset.style.display = 'none';
		}
		else
		{
			fldset.style.display = '';
		}
	}
	//-->
</script>

<?php

$blogurl = $edited_Blog->gen_blogurl();

$Form = & new Form( NULL, 'coll_features_checkchanges' );

$Form->begin_form( 'fform' );

$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'seo' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('SEO Presets') );

	$available_presets = array( 'awall' => 'Aaron Wall',
                              'abeal' => 'Andy Beal',
                              'mgray' => 'Michael Gray',
                              'rfishkin' => 'Rand Fishkin',
                              'sspencer' => 'Stephan Spencer' );

  $preset_html = '';
	foreach( $available_presets as $preset_code => $preset_name )
	{
		$preset_html .= '<a href="?ctrl=coll_settings&amp;tab=seo&amp;blog='.$edited_Blog->ID.'&amp;preset='
												.$preset_code.'" title="'.$preset_name.'"';
		if( $preset == $preset_code )
		{
			$preset_html .= ' class="current"';
		}
		$preset_html .= '><img alt="'.$preset_name.'" src="'
												.$rsc_url.'/img/people/'.$preset_code.'.png" width="124" height="180" /></a>';
	}


	echo '<div class="seo_presets">'.$preset_html.'</div>';

	switch( $preset )
	{
		case 'awall':
			$seo_author = '<a href="http://www.seobook.com/" target="_blank">Aaron Wall</a>';
			$seo_site = 'For more SEO tips, visit <strong><a href="http://www.seobook.com/" target="_blank">SEO Book</a></strong>.';
			break;

		case 'abeal':
			$seo_author = '<a href="http://www.marketingpilgrim.com/" target="_blank">Andy Beal</a>';
			$seo_site = 'For more advanced optimization, visit <strong><a href="http://www.marketingpilgrim.com/" target="_blank">Marketing Pilgrim</a></strong>.';
			break;

		case 'mgray':
			$seo_author = '<a href="http://www.wolf-howl.com/" target="_blank">Michael Gray</a>';
			$seo_site = 'For more advanced optimization, visit <strong><a href="http://www.wolf-howl.com/" target="_blank">Graywolf\'s SEO blog</a></strong>.';
			break;

		case 'rfishkin':
			$seo_author = '<a href="http://www.seomoz.org/team/randfish" target="_blank">Rand Fishkin</a>';
			$seo_site = 'For more advanced optimization, visit <strong><a href="http://www.seomoz.org/" target="_blank">SEOmoz</a></strong>.';
			break;

		case 'sspencer':
			$seo_author = '<a href="http://www.stephanspencer.com/" target="_blank">Stephan Spencer</a>';
			$seo_site = 'For more advanced optimization, visit <strong><a href="http://www.netconcepts.com/" target="_blank">NetConcepts</a></strong>.';
			break;
	}

	if( !empty($seo_author) )
	{
	 	echo '<div class="seo_message">';
		printf( T_('You can review the SEO settings recommended by <strong>%s</strong> below. Click the "Save!" button to apply these settings.'),
								$seo_author );
		echo '<br/>'.$seo_site.'</div>';
	}
$Form->end_fieldset();

$Form->begin_fieldset( T_('Main page / post list').get_manual_link('main_page_seo') );
	$Form->checkbox( 'default_noindex', $edited_Blog->get_setting( 'default_noindex' ), T_('Default blog page'), T_('META NOINDEX') );
	$Form->checkbox( 'paged_noindex', $edited_Blog->get_setting( 'paged_noindex' ), T_('"Next" blog pages'), T_('META NOINDEX').' - '.T_('Page 2,3,4...') );
	$Form->checkbox( 'paged_nofollowto', $edited_Blog->get_setting( 'paged_nofollowto' ), '', T_('NOFOLLOW on links to').' '.T_('Page 2,3,4...') );

	$Form->radio( 'title_link_type', $edited_Blog->get_setting( 'title_link_type' ), array(
			  array( 'permalink', T_('Link to the permanent url of the post') ),
			  array( 'linkto_url', T_('Link to the "link to URL" specified in the post (if any)') ),
			  array( 'none', T_('No links on titles') ),
			), T_('Post titles'), true );
	// TODO: checkbox display "permalink" separately from the title
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

	$Form->checkbox( 'canonical_item_urls', $edited_Blog->get_setting( 'canonical_item_urls' ),
			T_('Make canoncial'), T_('301 redirect to canonical URL') );

$Form->end_fieldset();

$Form->begin_fieldset( T_('"By date" archives').get_manual_link('archive_pages_seo') );

	$Form->radio( 'archive_links', $edited_Blog->get_setting('archive_links'),
		array(
				array( 'param', T_('Use param'), T_('E-g: ')
								.url_add_param( $blogurl, '<strong>m=20071231</strong>' ) ),
				array( 'extrapath', T_('Use extra-path'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2007/12/31/</strong>' ) ),
			), T_('Date archive URLs'), true );

	$Form->checkbox( 'archive_noindex', $edited_Blog->get_setting( 'archive_noindex' ), T_('Indexing'), T_('META NOINDEX') );
	$Form->checkbox( 'archive_nofollowto', $edited_Blog->get_setting( 'archive_nofollowto' ), T_('Follow TO'), T_('NOFOLLOW on links to').' '.T_('date archives') );

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

	$Form->checkbox( 'canonical_cat_urls', $edited_Blog->get_setting( 'canonical_cat_urls' ), T_('Make canonical'), T_('301 redirect to canonical URL') );

	$Form->checkbox( 'chapter_noindex', $edited_Blog->get_setting( 'chapter_noindex' ), T_('Indexing'), T_('META NOINDEX') );

	$Form->text( 'chapter_posts_per_page', $edited_Blog->get_setting('chapter_posts_per_page'), 4, T_('Posts per page'),
								T_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->checkbox( 'catdir_noindex', $edited_Blog->get_setting( 'catdir_noindex' ), T_('Category directory'), T_('META NOINDEX') );

	$Form->end_fieldset();

$Form->begin_fieldset( T_('Tag pages').get_manual_link('tag_pages_seo') );

	$Form->radio( 'tag_links', $edited_Blog->get_setting('tag_links'),
		array(
				array( 'param', T_('Use param'), T_('E-g: ')
								.url_add_param( $blogurl, '<strong>tag=mytag</strong>' ),'', 'onclick="show_hide_tag_prefix(this);"'),
				array( 'semicol', T_('Use extra-path'), T_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/mytag;</strong>' ), '', 'onclick="show_hide_tag_prefix(this);"' ),
			), T_('Tag page URLs'), true );


	echo '<div id="tag_prefix_container">';
		$Form->text_input( 'tag_prefix', $edited_Blog->get_setting( 'tag_prefix' ), 30, T_('Prefix'),
													T_('An optional prefix to be added to the URLs of the tag pages'),
													array('maxlength' => 120) );
	echo '</div>';
	if( $edited_Blog->get_setting( 'tag_links' ) == 'param' )
	{ ?>
	<script type="text/javascript">
		<!--
		var fldset = document.getElementById( 'tag_prefix_container' );
		fldset.style.display = 'none';
		//-->
	</script>
	<?php
	}

	$Form->checkbox( 'canonical_tag_urls', $edited_Blog->get_setting( 'canonical_tag_urls' ), T_('Make canonical'), T_('301 redirect to canonical URL') );

	$Form->checkbox( 'tag_noindex', $edited_Blog->get_setting( 'tag_noindex' ), T_('Indexing'), T_('META NOINDEX') );

	$Form->text( 'tag_posts_per_page', $edited_Blog->get_setting('tag_posts_per_page'), 4, T_('Posts per page'),
								T_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->end_fieldset();


$Form->begin_fieldset( T_('Other pages').get_manual_link('other_pages_seo') );
	$Form->checkbox( 'filtered_noindex', $edited_Blog->get_setting( 'filtered_noindex' ), T_('Other filtered posts pages'), T_('META NOINDEX').' - '.T_('Filtered by keyword search, by author, etc.') );

	$Form->checkbox( 'feedback-popup_noindex', $edited_Blog->get_setting( 'feedback-popup_noindex' ), T_('Comment popups'),
										T_('META NOINDEX').' - '.T_('For skins with comment popups only.') );
	$Form->checkbox( 'msgform_noindex', $edited_Blog->get_setting( 'msgform_noindex' ), T_('Contact forms'),
										T_('META NOINDEX').' - '.T_('WARNING: Letting search engines index contact forms will attract spam.') );
	$Form->checkbox( 'special_noindex', $edited_Blog->get_setting( 'special_noindex' ), T_('Other special pages'),
										T_('META NOINDEX').' - '.T_('Pages with no index setting of their own... yet.') );
$Form->end_fieldset();


$Form->end_form( array(
	array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
	array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

echo '<p class="note right">SEO portraits kindly provided by <a href="http://www.seomoz.org/" target="_blank">SEOmoz</a>.</p>';

/*
 * $Log$
 * Revision 1.9  2008/01/07 02:53:27  fplanque
 * cleaner tag urls
 *
 * Revision 1.8  2007/12/27 18:20:00  fplanque
 * cosmetics
 *
 * Revision 1.7  2007/12/27 01:58:48  fplanque
 * additional SEO
 *
 * Revision 1.6  2007/11/29 21:23:35  fplanque
 * Changed wording.
 *
 * Revision 1.5  2007/11/25 18:20:38  fplanque
 * additional SEO settings
 *
 * Revision 1.4  2007/11/24 21:41:12  fplanque
 * additional SEO settings
 *
 * Revision 1.3  2007/11/03 04:56:03  fplanque
 * permalink / title links cleanup
 *
 * Revision 1.2  2007/09/29 03:42:12  fplanque
 * skin install UI improvements
 *
 * Revision 1.1  2007/09/28 09:28:36  fplanque
 * per blog advanced SEO settings
 *
 * Revision 1.1  2007/06/25 10:59:35  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.4  2007/05/31 03:02:23  fplanque
 * Advanced perms now disabled by default (simpler interface).
 * Except when upgrading.
 * Enable advanced perms in blog settings -> features
 *
 * Revision 1.3  2007/05/13 22:53:31  fplanque
 * allow feeds restricted to post excerpts
 *
 * Revision 1.2  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.1  2006/12/16 01:30:47  fplanque
 * Setting to allow/disable email subscriptions on a per blog basis
 *
 */
?>