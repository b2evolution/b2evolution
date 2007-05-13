<?php
/**
 * This file implements the UI view for the Blog display properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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

global $tab, $blog;

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', $tab );
$Form->hidden( 'blog', $blog );

$Form->begin_fieldset( T_('Description') );
	$Form->text( 'blog_tagline', $edited_Blog->get( 'tagline' ), 50, T_('Tagline'), T_('This is diplayed under the blog name on the blog template.'), 250 );
	$Form->textarea( 'blog_longdesc', $edited_Blog->get( 'longdesc' ), 5, T_('Long Description'), T_('This is displayed on the blog template.'), 50, 'large' );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Content / Posts') );
	$Form->select_input_array( 'orderby', $edited_Blog->get_setting('orderby'), array(
												'datestart' => T_('Date issued (Default)'),
												//'datedeadline' => T_('Deadline'),
												'title'     => T_('Title'),
												'datecreated' => T_('Date created'),
												'datemodified' => T_('Date last modified'),
												'urltitle'     => T_('URL Title'),
												'priority'     => T_('Priority'),
											), T_('Order by'), T_('Default ordering of posts.') );
	$Form->select_input_array( 'orderdir', $edited_Blog->get_setting('orderdir'), array(
												'ASC'  => T_('Ascending'),
												'DESC' => T_('Descending'), ), T_('Direction') );
	$Form->radio( 'what_to_show', $edited_Blog->get_setting('what_to_show'),
								array(  array( 'days', T_('days') ),
												array( 'posts', T_('posts') ),
											), T_('Display unit'), false,  T_('Do you want to restrict on the number of days or the number of posts?') );
	$Form->text( 'posts_per_page', $edited_Blog->get_setting('posts_per_page'), 4, T_('Posts/Days per page'), T_('How many days or posts fo you want to display on the home page?'), 4 );
	$Form->radio( 'archive_mode',  $edited_Blog->get_setting('archive_mode'),
							array(  array( 'monthly', T_('monthly') ),
											array( 'weekly', T_('weekly') ),
											array( 'daily', T_('daily') ),
											array( 'postbypost', T_('post by post') )
										), T_('Archive grouping'), false,  T_('How do you want to browse the post archives? May also apply to permalinks.') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Skin and style') );

	$SkinCache = & get_Cache( 'SkinCache' );
	$SkinCache->load_all();
	$Form->select_input_object( 'blog_skin_ID', $edited_Blog->skin_ID, $SkinCache, T_('Skin') );
	$Form->checkbox( 'blog_allowblogcss', $edited_Blog->get( 'allowblogcss' ), T_('Allow customized blog CSS file'), T_('A CSS file in the blog media directory will override the default skin stylesheet.') );
	$Form->checkbox( 'blog_allowusercss', $edited_Blog->get( 'allowusercss' ), T_('Allow user customized CSS file for this blog'), T_('Users will be able to override the blog and skin stylesheet with their own.') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Link blog / Blogroll') );
	$BlogCache = & get_Cache( 'BlogCache' );
	$Form->select_object( 'blog_links_blog_ID', $edited_Blog->get( 'links_blog_ID' ), $BlogCache, T_('Default linkblog'), T_('Will be displayed next to this blog (if your skin supports this).'), true );
$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

$Form->end_form();

/*
 * $Log$
 * Revision 1.12  2007/05/13 22:53:31  fplanque
 * allow feeds restricted to post excerpts
 *
 * Revision 1.11  2007/05/08 00:54:31  fplanque
 * public blog list as a widget
 *
 * Revision 1.10  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.9  2007/01/23 09:25:40  fplanque
 * Configurable sort order.
 *
 * Revision 1.8  2007/01/08 02:11:55  fplanque
 * Blogs now make use of installed skins
 * next step: make use of widgets inside of skins
 *
 * Revision 1.7  2006/12/14 21:41:16  fplanque
 * Allow different number of items in feeds than on site
 *
 * Revision 1.6  2006/12/04 21:25:18  fplanque
 * removed user skin switching
 *
 * Revision 1.5  2006/12/04 19:41:11  fplanque
 * Each blog can now have its own "archive mode" settings
 *
 * Revision 1.4  2006/12/04 18:16:50  fplanque
 * Each blog can now have its own "number of page/days to display" settings
 *
 * Revision 1.3  2006/09/11 19:35:35  fplanque
 * minor
 *
 */
?>