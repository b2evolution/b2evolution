<?php
/**
 * This file implements the UI view for the Collection features other properties.
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
global $edited_Blog, $admin_url, $Blog;


$Form = new Form( NULL, 'coll_other_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'other' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( TB_('Latest comments').get_manual_link( 'latest-comments-other' ) );
	$Form->text( 'latest_comments_num', $edited_Blog->get_setting( 'latest_comments_num' ), 4, TB_('Comments shown'), '', 4 );
$Form->end_fieldset();


$Form->begin_fieldset( TB_('Archive pages').get_manual_link( 'archives-other' ) );
	$Form->radio( 'archive_mode', $edited_Blog->get_setting( 'archive_mode' ),
							array(  array( 'monthly', TB_('monthly') ),
											array( 'weekly', TB_('weekly') ),
											array( 'daily', TB_('daily') ),
											array( 'postbypost', TB_('post by post') )
										), TB_('Archive grouping'), false,  TB_('How do you want to browse the post archives? May also apply to permalinks.') );

	// TODO: Hide if archive_mode != 'postbypost' (JS)
	// fp> there should probably be no post by post mode since we do have other ways to list posts now
	// fp> TODO: this is display param and should go to plugin/widget
	$Form->radio( 'archives_sort_order', $edited_Blog->get_setting( 'archives_sort_order' ),
							array(  array( 'date', TB_('date') ),
											array( 'title', TB_('title') ),
										), TB_('Archive sorting'), false,  TB_('How to sort your archives? (only in post by post mode)') );

	$Form->text( 'archive_posts_per_page', $edited_Blog->get_setting('archive_posts_per_page'), 4, TB_('Posts per page'),
								TB_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->radio( 'archive_content', $edited_Blog->get_setting('archive_content'),
		array(
				array( 'excerpt', TB_('Post excerpts'), '('.TB_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', TB_('Standard post contents (stopping at "[teaserbreak]")'), '('.TB_('Teaser images will be displayed').')' ),
				array( 'full', TB_('Full post contents (including after "[teaserbreak]")'), '('.TB_('All images will be displayed').')' ),
			), TB_('Post contents'), true );
$Form->end_fieldset();


$Form->begin_fieldset( TB_('Category pages').get_manual_link( 'category-pages-other' ) );
	$Form->text( 'chapter_posts_per_page', $edited_Blog->get_setting('chapter_posts_per_page'), 4, TB_('Posts per page'),
								TB_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->radio( 'chapter_content', $edited_Blog->get_setting('chapter_content'),
		array(
				array( 'excerpt', TB_('Post excerpts'), '('.TB_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', TB_('Standard post contents (stopping at "[teaserbreak]")'), '('.TB_('Teaser images will be displayed').')' ),
				array( 'full', TB_('Full post contents (including after "[teaserbreak]")'), '('.TB_('All images will be displayed').')' ),
			), TB_('Post contents'), true );
$Form->end_fieldset();


$Form->begin_fieldset( TB_('Tag pages').get_manual_link( 'tag-pages-other' ) );
	$Form->text( 'tag_posts_per_page', $edited_Blog->get_setting('tag_posts_per_page'), 4, TB_('Posts per page'),
								TB_('Leave empty to use blog default').' ('.$edited_Blog->get_setting('posts_per_page').')', 4 );

	$Form->radio( 'tag_content', $edited_Blog->get_setting('tag_content'),
		array(
				array( 'excerpt', TB_('Post excerpts'), '('.TB_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', TB_('Standard post contents (stopping at "[teaserbreak]")'), '('.TB_('Teaser images will be displayed').')' ),
				array( 'full', TB_('Full post contents (including after "[teaserbreak]")'), '('.TB_('All images will be displayed').')' ),
			), TB_('Post contents'), true );
$Form->end_fieldset();


$Form->begin_fieldset( TB_('Other filtered pages').get_manual_link( 'other-filtered-pages-other' ) );
	$Form->radio( 'filtered_content', $edited_Blog->get_setting('filtered_content'),
		array(
				array( 'excerpt', TB_('Post excerpts'), '('.TB_('No Teaser images will be displayed on default skins').')' ),
				array( 'normal', TB_('Standard post contents (stopping at "[teaserbreak]")'), '('.TB_('Teaser images will be displayed').')' ),
				array( 'full', TB_('Full post contents (including after "[teaserbreak]")'), '('.TB_('All images will be displayed').')' ),
			), TB_('Post contents'), true );
$Form->end_fieldset();


$Form->begin_fieldset( TB_('Download pages').get_manual_link( 'download-display-other' ) );
	$Form->checkbox( 'download_enable', $edited_Blog->get_setting( 'download_enable' ), TB_('Enable Download pages'), sprintf( TB_('Check to use %s intead of default opening of attachments'), '<code>?disp=download</code>' ) );
	$Form->text_input( 'download_delay', $edited_Blog->get_setting( 'download_delay' ), 2, TB_('Download delay') );
$Form->end_fieldset();


if( isset($GLOBALS['files_Module']) )
{
	load_funcs( 'files/model/_image.funcs.php' );

	$Form->begin_fieldset( TB_('Messaging pages').get_manual_link( 'messaging-other' ) );
			$Form->select_input_array( 'image_size_messaging', $edited_Blog->get_setting( 'image_size_messaging' ), get_available_thumb_sizes(), TB_('Profile picture size'), '', array( 'force_keys_as_values' => true ) );
	$Form->end_fieldset();
}


$Form->end_form( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton' ) ) );

?>
