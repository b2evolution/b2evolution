<?php
/**
 * This file implements the UI view for the Collection features other properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
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


$Form = new Form( NULL, 'coll_other_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'other' );
$Form->hidden( 'blog', $edited_Blog->ID );


$Form->begin_fieldset( T_('Search results').get_manual_link( 'search-results-other' ) );
	$Form->text( 'search_per_page', $edited_Blog->get_setting( 'search_per_page' ), 4, T_('Results per page'), '', 4 );
	$Form->radio( 'search_sort_by', $edited_Blog->get_setting( 'search_sort_by' ), array(
			array( 'score', T_('Score') ),
			array( 'date', T_('Date'), T_('If sorted by date, everything without a date will be sorted last.') ),
		), T_('Sort by'), true );
	$Form->checklist( array(
			array( 'search_include_posts', 1, T_('Posts'), $edited_Blog->get_setting( 'search_include_posts' ) ),
			array( 'search_include_cmnts', 1, T_('Comments'), $edited_Blog->get_setting( 'search_include_cmnts' ) ),
			array( 'search_include_cats',  1, T_('Categories'), $edited_Blog->get_setting( 'search_include_cats' ) ),
			array( 'search_include_tags',  1, T_('Tags'), $edited_Blog->get_setting( 'search_include_tags' ) ),
		), 'search_include', T_('Include') );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Latest comments').get_manual_link( 'latest-comments-other' ) );
	$Form->text( 'latest_comments_num', $edited_Blog->get_setting( 'latest_comments_num' ), 4, T_('Comments shown'), '', 4 );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Archive pages').get_manual_link( 'archives-other' ) );
	$Form->radio( 'archive_mode', $edited_Blog->get_setting( 'archive_mode' ),
							array(  array( 'monthly', T_('monthly') ),
											array( 'weekly', T_('weekly') ),
											array( 'daily', T_('daily') ),
											array( 'postbypost', T_('post by post') )
										), T_('Archive grouping'), false,  T_('How do you want to browse the post archives? May also apply to permalinks.') );

	// TODO: Hide if archive_mode != 'postbypost' (JS)
	// fp> there should probably be no post by post mode since we do have other ways to list posts now
	// fp> TODO: this is display param and should go to plugin/widget
	$Form->radio( 'archives_sort_order', $edited_Blog->get_setting( 'archives_sort_order' ),
							array(  array( 'date', T_('date') ),
											array( 'title', T_('title') ),
										), T_('Archive sorting'), false,  T_('How to sort your archives? (only in post by post mode)') );

	$Form->text( 'archive_posts_per_page', $edited_Blog->get_setting('archive_posts_per_page'), 4, T_('Posts per page'),
								T_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->radio( 'archive_content', $edited_Blog->get_setting('archive_content'),
		array(
				array( 'excerpt', T_('Post excerpts'), '('.T_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', T_('Standard post contents (stopping at "[teaserbreak]")'), '('.T_('Teaser images will be displayed').')' ),
				array( 'full', T_('Full post contents (including after "[teaserbreak]")'), '('.T_('All images will be displayed').')' ),
			), T_('Post contents'), true );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Category pages').get_manual_link( 'category-pages-other' ) );
	$Form->text( 'chapter_posts_per_page', $edited_Blog->get_setting('chapter_posts_per_page'), 4, T_('Posts per page'),
								T_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->radio( 'chapter_content', $edited_Blog->get_setting('chapter_content'),
		array(
				array( 'excerpt', T_('Post excerpts'), '('.T_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', T_('Standard post contents (stopping at "[teaserbreak]")'), '('.T_('Teaser images will be displayed').')' ),
				array( 'full', T_('Full post contents (including after "[teaserbreak]")'), '('.T_('All images will be displayed').')' ),
			), T_('Post contents'), true );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Tag pages').get_manual_link( 'tag-pages-other' ) );
	$Form->text( 'tag_posts_per_page', $edited_Blog->get_setting('tag_posts_per_page'), 4, T_('Posts per page'),
								T_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->radio( 'tag_content', $edited_Blog->get_setting('tag_content'),
		array(
				array( 'excerpt', T_('Post excerpts'), '('.T_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', T_('Standard post contents (stopping at "[teaserbreak]")'), '('.T_('Teaser images will be displayed').')' ),
				array( 'full', T_('Full post contents (including after "[teaserbreak]")'), '('.T_('All images will be displayed').')' ),
			), T_('Post contents'), true );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Other filtered pages').get_manual_link( 'other-filtered-pages-other' ) );
	$Form->radio( 'filtered_content', $edited_Blog->get_setting('filtered_content'),
		array(
				array( 'excerpt', T_('Post excerpts'), '('.T_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', T_('Standard post contents (stopping at "[teaserbreak]")'), '('.T_('Teaser images will be displayed').')' ),
				array( 'full', T_('Full post contents (including after "[teaserbreak]")'), '('.T_('All images will be displayed').')' ),
			), T_('Post contents'), true );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Download pages').get_manual_link( 'download-display-other' ) );
	$Form->text_input( 'download_delay', $edited_Blog->get_setting( 'download_delay' ), 2, T_('Download delay') );
$Form->end_fieldset();


if( isset($GLOBALS['files_Module']) )
{
	load_funcs( 'files/model/_image.funcs.php' );
	$params['force_keys_as_values'] = true;

	$Form->begin_fieldset( T_('User directory').get_manual_link( 'user-directory-other' ) );
			$Form->select_input_array( 'image_size_user_list', $edited_Blog->get_setting( 'image_size_user_list' ), get_available_thumb_sizes(), T_('Profile picture size'), '', $params );
	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Messaging pages').get_manual_link( 'messaging-other' ) );
			$Form->select_input_array( 'image_size_messaging', $edited_Blog->get_setting( 'image_size_messaging' ), get_available_thumb_sizes(), T_('Profile picture size'), '', $params );
	$Form->end_fieldset();
}

$Form->begin_fieldset( T_('Contact form').' (disp=msgform)'.get_manual_link( 'contact-form' ) );
	$Form->text_input( 'msgform_title', $edited_Blog->get_setting( 'msgform_title' ), 80, T_('Page Title'), T_('Leave empty for default') );
	$Form->checkbox( 'msgform_display_recipient', $edited_Blog->get_setting( 'msgform_display_recipient' ), T_('Display recipient'), T_('Check to show a "Message to:" line.') );
$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>