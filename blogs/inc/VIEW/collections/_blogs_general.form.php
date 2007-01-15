<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;


global $action, $next_action, $blogtemplate, $blog, $tab, $maxlength_urlname_stub;

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->hidden_ctrl();
$Form->hidden( 'action', $next_action );
$Form->hidden( 'tab', $tab );
if( $next_action == 'create' )
{
	$Form->hidden( 'kind', get_param('kind') );
	$Form->hidden( 'skin_ID', get_param('skin_ID') );
}
else
{
	$Form->hidden( 'blog', $blog );
}

$Form->begin_fieldset( T_('General parameters'), array( 'class'=>'fieldset clear' ) );
	$Form->text( 'blog_name', $edited_Blog->get( 'name' ), 50, T_('Full Name'), T_('Will be displayed on top of the blog.') );
	$Form->text( 'blog_shortname', $edited_Blog->get( 'shortname', 'formvalue' ), 12, T_('Short Name'), T_('Will be used in selection menus and throughout the admin interface.') );
	$Form->text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, T_('Blog URL name'), T_('Used to uniquely identify this blog. Appears in URLs and gets used as default for the media location (see the advanced tab).'), $maxlength_urlname_stub );
	$Form->select( 'blog_locale', $edited_Blog->get( 'locale' ), 'locale_options_return', T_('Main Locale'), T_('Determines the language of the navigation links on the blog.') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Feedback options') );
	$Form->radio( 'blog_allowcomments', $edited_Blog->get( 'allowcomments' ),
						array(  array( 'always', T_('Always on all posts'), T_('Always allow comments on every posts') ),
						array( 'post_by_post', T_('Can be disabled on a per post basis'),  T_('Comments can be disabled on each post separatly') ),
						array( 'never', T_('No comments are allowed in this blog'), T_('Never allow any comments in this blog') ),
					), T_('Allow comments'), true );

	$status_options = array(
			'draft'      => T_('Draft'),
			'published'  => T_('Published'),
			'deprecated' => T_('Deprecated')
		);
	$Form->select_input_array( 'new_feedback_status', $status_options, T_('New feedback status') /* gets referred to in antispam settings form */, array(
				'value' => $edited_Blog->get_setting('new_feedback_status'),
				'note' => T_('This status will be assigned to any new comment/trackback (unless overriden by plugins).')
			) );

	$Form->checkbox( 'blog_allowtrackbacks', $edited_Blog->get( 'allowtrackbacks' ), T_('Allow trackbacks'), T_("Allow other bloggers to send trackbacks to this blog, letting you know when they refer to it. This will also let you send trackbacks to other blogs.") );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

$Form->end_form();

/*
 * $Log$
 * Revision 1.17  2007/01/15 03:54:36  fplanque
 * pepped up new blog creation a little more
 *
 * Revision 1.16  2007/01/15 00:38:05  fplanque
 * pepped up "new blog" creation a little. To be continued.
 *
 * Revision 1.15  2006/09/11 19:36:58  fplanque
 * blog url ui refactoring
 *
 */
?>