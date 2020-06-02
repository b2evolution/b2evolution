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


$Form = new Form( NULL, 'coll_search_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'search' );
$Form->hidden( 'blog', $edited_Blog->ID );


$Form->begin_fieldset( TB_('Search results').get_manual_link( 'search-results-other' ) );
	$Form->checkbox( 'search_enable', $edited_Blog->get_setting( 'search_enable' ), TB_('Enable search') );
	$Form->text( 'search_per_page', $edited_Blog->get_setting( 'search_per_page' ), 4, TB_('Results per page'), '', 4 );
	$Form->radio( 'search_sort_by', $edited_Blog->get_setting( 'search_sort_by' ), array(
			array( 'score', TB_('Score') ),
			array( 'date', TB_('Date'), TB_('If sorted by date, everything without a date will be sorted last.') ),
		), TB_('Sort by'), true );
	$Form->checklist( array(
			array( 'search_include_posts', 1, TB_('Posts'), $edited_Blog->get_setting( 'search_include_posts' ) ),
			array( 'search_include_cmnts', 1, TB_('Comments'), $edited_Blog->get_setting( 'search_include_cmnts' ) ),
			array( 'search_include_metas', 1, TB_('Internal comments'), $edited_Blog->get_setting( 'search_include_metas' ) ),
			array( 'search_include_files', 1, TB_('Files'), $edited_Blog->get_setting( 'search_include_files' ) ),
			array( 'search_include_cats',  1, TB_('Categories'), $edited_Blog->get_setting( 'search_include_cats' ) ),
			array( 'search_include_tags',  1, TB_('Tags'), $edited_Blog->get_setting( 'search_include_tags' ) ),
		), 'search_include', TB_('Include') );
	// Scoring:
	$score_settings = array(
		TB_('Scoring for posts') => array(
			'post_title'          => TB_('weight multiplier for keywords found in post title'),
			'post_content'        => TB_('weight multiplier for keywords found in post content'),
			'post_tags'           => TB_('weight multiplier for keywords found in post tags'),
			'post_excerpt'        => TB_('weight multiplier for keywords found in post excerpt'),
			'post_titletag'       => TB_('weight multiplier for keywords found in post &lt;title&gt; tag'),
			'post_metakeywords'   => TB_('weight multiplier for keywords found in post &lt;meta&gt; keywords'),
			'post_author'         => TB_('weight multiplier for keywords found in post author login'),
			'post_date_future'    => TB_('weight multiplier for posts from future'),
			'post_date_moremonth' => TB_('weight multiplier for posts older month'),
			'post_date_lastmonth' => TB_('weight multiplier for posts from the last month'),
			'post_date_twoweeks'  => TB_('weight multiplier for posts from the last two weeks'),
			'post_date_lastweek'  => TB_('weight multiplier for posts from the last week, depending on the days passed since modification date, and it is restricted with min value as weight multiplier of last two weeks'),
		),
		TB_('Scoring for comments') => array(
			'cmnt_post_title'     => TB_('weight multiplier for keywords found in title of the comment\'s post'),
			'cmnt_content'        => TB_('weight multiplier for keywords found in comment content'),
			'cmnt_author'         => TB_('weight multiplier for keywords found in comment author name'),
			'cmnt_date_future'    => TB_('weight multiplier for comments from future'),
			'cmnt_date_moremonth' => TB_('weight multiplier for comments older month'),
			'cmnt_date_lastmonth' => TB_('weight multiplier for comments from the last month'),
			'cmnt_date_twoweeks'  => TB_('weight multiplier for comments from the last two weeks'),
			'cmnt_date_lastweek'  => TB_('weight multiplier for comments from the last week, depending on the days passed since modification date, and it is restricted with min value as weight multiplier of last two weeks'),
		),
		TB_('Scoring for files') => array(
			'file_name'           => TB_('weight multiplier for keywords found in file name'),
			'file_path'           => TB_('weight multiplier for keywords found in file path'),
			'file_title'          => TB_('weight multiplier for keywords found in file long title'),
			'file_alt'            => TB_('weight multiplier for keywords found in file alternative text'),
			'file_description'    => TB_('weight multiplier for keywords found in file caption/description'),
		),
		TB_('Scoring for categories') => array(
			'cat_name'            => TB_('weight multiplier for keywords found in category name'),
			'cat_desc'            => TB_('weight multiplier for keywords found in category description'),
		),
		TB_('Scoring for tags') => array(
			'tag_name'            => TB_('weight multiplier for keywords found in tag name'),
		),
	);
	foreach( $score_settings as $score_group_title => $score_settings_data )
	{
		$s = 0;
		foreach( $score_settings_data as $score_name => $score_description )
		{
			$Form->text( 'search_score_'.$score_name, $edited_Blog->get_setting( 'search_score_'.$score_name ), 1, $s == 0 ? $score_group_title : '', $score_description, 10 );
			$s = 1;
		}
	}

	// Quick Templates:
	$context = 'search_result';
	$TemplateCache = & get_TemplateCache();
	$TemplateCache->load_by_context( $context );
	$template_options = $TemplateCache->get_code_option_array();
	$template_input_suffix = ( check_user_perm( 'options', 'edit' ) ? '&nbsp;'
		.action_icon( '', 'edit', $admin_url.'?ctrl=templates&amp;context='.$context.'&amp;blog='.$Blog->ID, NULL, NULL, NULL, array( 'onclick' => 'return b2template_list_highlight( this )' ), array( 'title' => TB_('Manage templates').'...' ) ) : '' );
	$Form->select_input_array( 'search_result_template_item', $edited_Blog->get_setting( 'search_result_template_item' ), $template_options, sprintf( TB_('Template for %s search result'), TB_('Item') ), NULL, array( 'input_suffix' => $template_input_suffix ) );
	$Form->select_input_array( 'search_result_template_comment', $edited_Blog->get_setting( 'search_result_template_comment' ), $template_options, sprintf( TB_('Template for %s search result'), TB_('Comment') ), NULL, array( 'input_suffix' => $template_input_suffix ) );
	$Form->select_input_array( 'search_result_template_meta', $edited_Blog->get_setting( 'search_result_template_meta' ), $template_options, sprintf( TB_('Template for %s search result'), TB_('Internal comment') ), NULL, array( 'input_suffix' => $template_input_suffix ) );
	$Form->select_input_array( 'search_result_template_file', $edited_Blog->get_setting( 'search_result_template_file' ), $template_options, sprintf( TB_('Template for %s search result'), TB_('File') ), NULL, array( 'input_suffix' => $template_input_suffix ) );
	$Form->select_input_array( 'search_result_template_category', $edited_Blog->get_setting( 'search_result_template_category' ), $template_options, sprintf( TB_('Template for %s search result'), TB_('Category') ), NULL, array( 'input_suffix' => $template_input_suffix ) );
	$Form->select_input_array( 'search_result_template_tag', $edited_Blog->get_setting( 'search_result_template_tag' ), $template_options, sprintf( TB_('Template for %s search result'), TB_('Tag') ), NULL, array( 'input_suffix' => $template_input_suffix ) );
$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton' ) ) );

?>
