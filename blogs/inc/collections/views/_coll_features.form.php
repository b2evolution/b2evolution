<?php
/**
 * This file implements the UI view for the Collection features properties.
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

?>
<script type="text/javascript">
	<!--
	function show_hide_feedback_details(ob)
	{
		var fldset = document.getElementById( 'feedback_details_container' );
		if( ob.value == 'never' )
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

$Form = & new Form( NULL, 'coll_features_checkchanges' );

$Form->begin_form( 'fform' );

$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'features' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Feedback options') );
	$Form->radio( 'blog_allowcomments', $edited_Blog->get( 'allowcomments' ),
						array(  array( 'always', T_('Always on all posts'), T_('Always allow comments on every post'),
										'', 'onclick="show_hide_feedback_details(this);"'),
						array( 'post_by_post', T_('Can be disabled on a per post basis'),  T_('Comments can be disabled on each post separatly'),
										'', 'onclick="show_hide_feedback_details(this);"'),
						array( 'never', T_('No comments are allowed in this blog'), T_('Never allow any comments in this blog'),
										'', 'onclick="show_hide_feedback_details(this);"'),
					), T_('Allow comments'), true );

	echo '<div id="feedback_details_container">';

	$Form->radio( 'allow_rating', $edited_Blog->get_setting( 'allow_rating' ),
						array(  array( 'always', T_('Always') ),
										array( 'never', T_('Never') ),
					), T_('Allow rating'), true );

	$status_options = array(
			'draft'      => T_('Draft'),
			'published'  => T_('Published'),
			'deprecated' => T_('Deprecated')
		);
	$Form->select_input_array( 'new_feedback_status', $edited_Blog->get_setting('new_feedback_status'), $status_options,
				T_('New feedback status'), T_('This status will be assigned to any new comment/trackback (unless overriden by plugins).') );

	$Form->checkbox( 'blog_allowtrackbacks', $edited_Blog->get( 'allowtrackbacks' ), T_('Allow trackbacks'), T_("Allow other bloggers to send trackbacks to this blog, letting you know when they refer to it. This will also let you send trackbacks to other blogs.") );

	echo '</div>';

	if( $edited_Blog->get( 'allowcomments' ) == 'never' )
	{ ?>
	<script type="text/javascript">
		<!--
		var fldset = document.getElementById( 'feedback_details_container' );
		fldset.style.display = 'none';
		//-->
	</script>
	<?php
	}

$Form->end_fieldset();


$Form->begin_fieldset( T_('XML Feeds (RSS, Atom)') );
	$Form->radio( 'feed_content', $edited_Blog->get_setting('feed_content'),
								array(  array( 'none', T_('No feeds') ),
												array( 'title', T_('Titles only') ),
												array( 'excerpt', T_('Post excerpts') ),
												array( 'normal', T_('Post contents (stopping at "&lt;!-- more -->")') ),
											), T_('Feed contents'), false, T_('How much content do you want to make available in feeds?') );
	$Form->text( 'posts_per_feed', $edited_Blog->get_setting('posts_per_feed'), 4, T_('Posts in RSS/Atom feeds'),  T_('How many of the latest posts do you want to include in RSS & Atom feeds?'), 4 );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Subscriptions') );
	$Form->checkbox( 'allow_subscriptions', $edited_Blog->get_setting( 'allow_subscriptions' ), T_('Allow email subscriptions'), T_('Allow users to subscribe and receive email notifications for each new post and/or comment.') );
	// TODO: checkbox 'Enable RSS/Atom feeds'
	// TODO2: which feeds (skins)?
$Form->end_fieldset();

$Form->begin_fieldset( T_('List of public blogs') );
	$Form->checkbox( 'blog_in_bloglist', $edited_Blog->get( 'in_bloglist' ), T_('Include in public blog list'), T_('Check this if you want this blog to be advertised in the list of all public blogs on this system.') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Link blog / Blogroll') );
	$BlogCache = & get_Cache( 'BlogCache' );
	$Form->select_object( 'blog_links_blog_ID', $edited_Blog->get( 'links_blog_ID' ), $BlogCache, T_('Default linkblog'), T_('Will be displayed next to this blog (if your skin supports this).'), true );
$Form->end_fieldset();

if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
{	// Permission to edit advanced admin settings

	$Form->begin_fieldset( T_('Skin and style').' ['.T_('Admin').']' );

		$SkinCache = & get_Cache( 'SkinCache' );
		$SkinCache->load_all();
		$Form->select_input_object( 'blog_skin_ID', $edited_Blog->skin_ID, $SkinCache, T_('Skin') );
		$Form->checkbox( 'blog_allowblogcss', $edited_Blog->get( 'allowblogcss' ), T_('Allow customized blog CSS file'), T_('You will be able to customize the blog\'s skin stylesheet with a file named style.css in the blog\'s media file folder.') );
		$Form->checkbox( 'blog_allowusercss', $edited_Blog->get( 'allowusercss' ), T_('Allow user customized CSS file for this blog'), T_('Users will be able to customize the blog and skin stylesheets with a file named style.css in their personal file folder.') );
	$Form->end_fieldset();

}

$Form->begin_fieldset( T_('Multiple authors') );
	$Form->checkbox( 'advanced_perms', $edited_Blog->get( 'advanced_perms' ), T_('Use advanced perms'), T_('This will turn on the advanced User and Group permissions tabs for this blog.') );
	$Form->checkbox( 'blog_use_workflow', $edited_Blog->get_setting( 'use_workflow' ), T_('Use workflow'), T_('This will notably turn on the Tracker tab in the Posts view.') );
$Form->end_fieldset();


$Form->end_form( array(
	array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
	array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
 * Revision 1.2  2007/11/02 01:49:16  fplanque
 * comment ratings
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