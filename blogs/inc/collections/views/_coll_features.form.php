<?php
/**
 * This file implements the UI view for the Collection features properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}.
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

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'features' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Post options') );
	$Form->radio( 'require_title', $edited_Blog->get_setting('require_title'),
								array(  array( 'required', T_('Always'), T_('The blogger must provide a title') ),
												array( 'optional', T_('Optional'), T_('The blogger can leave the title field empty') ),
												array( 'none', T_('Never'), T_('No title field') ),
											), T_('Post titles'), true );

	// FP> TODO:
	// -post_url  always('required')|optional|never
	// -multilingual:  true|false   or better yet: provide a list to narrow down the active locales
	// -tags  always('required')|optional|never

$Form->end_fieldset();

$Form->begin_fieldset( T_('Feedback options') );
	$Form->radio( 'blog_allowcomments', $edited_Blog->get( 'allowcomments' ),
						array(  array( 'always', T_('Allow on all posts'), T_('Always allow comments on every post'),
										'', 'onclick="show_hide_feedback_details(this);"'),
						array( 'post_by_post', T_('Can be disabled on a per post basis'),  T_('Comments can be disabled on each post separatly'),
										'', 'onclick="show_hide_feedback_details(this);"'),
						array( 'never', T_('No comments are allowed in this blog'), T_('Never allow any comments in this blog'),
										'', 'onclick="show_hide_feedback_details(this);"'),
					), T_('Comments'), true );

	echo '<div id="feedback_details_container">';

	$Form->radio( 'allow_rating', $edited_Blog->get_setting( 'allow_rating' ),
						array(  array( 'always', T_('Always') ),
										array( 'never', T_('Never') ),
					), T_('Ratings'), true );

	$Form->checkbox( 'blog_allowtrackbacks', $edited_Blog->get( 'allowtrackbacks' ), T_('Trackbacks'), T_("Allow other bloggers to send trackbacks to this blog, letting you know when they refer to it. This will also let you send trackbacks to other blogs.") );

	$status_options = array(
			'draft'      => T_('Draft'),
			'published'  => T_('Published'),
			'deprecated' => T_('Deprecated')
		);
	$Form->select_input_array( 'new_feedback_status', $edited_Blog->get_setting('new_feedback_status'), $status_options,
				T_('New feedback status'), T_('This status will be assigned to new comments/trackbacks from non moderators (unless overriden by plugins).') );

	$Form->radio( 'comments_orderdir', $edited_Blog->get_setting('comments_orderdir'),
						array(	array( 'ASC', T_('Chronologic') ),
								array ('DESC', T_('Reverse') ),	
						), T_('Display order'), true );

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


$Form->begin_fieldset( T_('RSS/Atom feeds') );
	$Form->radio( 'feed_content', $edited_Blog->get_setting('feed_content'),
								array(  array( 'none', T_('No feeds') ),
												array( 'title', T_('Titles only') ),
												array( 'excerpt', T_('Post excerpts') ),
												array( 'normal', T_('Standard post contents (stopping at "&lt;!-- more -->")') ),
												array( 'full', T_('Full post contents (including after "&lt;!-- more -->")') ),
											), T_('Feed contents'), true, T_('How much content do you want to make available in feeds?') );
	$Form->text( 'posts_per_feed', $edited_Blog->get_setting('posts_per_feed'), 4, T_('Posts in feeds'),  T_('How many of the latest posts do you want to include in RSS & Atom feeds?'), 4 );

	load_funcs( 'files/model/_image.funcs.php' );
	$params['force_keys_as_values'] = true;
	$Form->select_input_array( 'image_size', $edited_Blog->get_setting('image_size') , get_available_thumb_sizes(), T_('Image size'), '', $params );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Sitemaps') );
	$Form->checkbox( 'enable_sitemaps', $edited_Blog->get_setting( 'enable_sitemaps' ),
						T_( 'Enable sitemaps' ), T_( 'Check to allow usage of skins with the "sitemap" type.' ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Multiple authors') );
	$Form->checkbox( 'advanced_perms', $edited_Blog->get( 'advanced_perms' ), T_('Use advanced perms'), T_('This will turn on the advanced User and Group permissions tabs for this blog.') );
	$Form->checkbox( 'blog_use_workflow', $edited_Blog->get_setting( 'use_workflow' ), T_('Use workflow'), T_('This will notably turn on the Tracker tab in the Posts view.') );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Custom field names') );
	$notes = array(
			T_('Ex: Price'),
			T_('Ex: Weight'),
			T_('Ex: Latitude or Length'),
			T_('Ex: Longitude or Width'),
			T_('Ex: Altitude or Height'),
		);
	for( $i = 1 ; $i <= 5; $i++ )
	{
		$Form->text( 'custom_double'.$i, $edited_Blog->get_setting('custom_double'.$i), 20, T_('(numeric)').' double'.$i, $notes[$i-1], 40 );
	}

	$notes = array(
			T_('Ex: Color'),
			T_('Ex: Fabric'),
			T_('Leave empty if not needed'),
		);
	for( $i = 1 ; $i <= 3; $i++ )
	{
		$Form->text( 'custom_varchar'.$i, $edited_Blog->get_setting('custom_varchar'.$i), 30, T_('(text)').' varchar'.$i, $notes[$i-1], 60 );
	}
$Form->end_fieldset();


$Form->begin_fieldset( T_('Subscriptions') );
	$Form->checkbox( 'allow_subscriptions', $edited_Blog->get_setting( 'allow_subscriptions' ), T_('Email subscriptions'), T_('Allow users to subscribe and receive email notifications for each new post and/or comment.') );
	// TODO: checkbox 'Enable RSS/Atom feeds'
	// TODO2: which feeds (skins)?
$Form->end_fieldset();

$Form->begin_fieldset( T_('List of public blogs') );
	$Form->checkbox( 'blog_in_bloglist', $edited_Blog->get( 'in_bloglist' ), T_('Include in public blog list'), T_('Check this if you want this blog to be advertised in the list of all public blogs on this system.') );
$Form->end_fieldset();

if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
{	// Permission to edit advanced admin settings

	$Form->begin_fieldset( T_('Skin and style').' ['.T_('Admin').']' );

		$SkinCache = & get_SkinCache();
		$SkinCache->load_all();
		$Form->select_input_object( 'blog_skin_ID', $edited_Blog->skin_ID, $SkinCache, T_('Skin') );
		$Form->checkbox( 'blog_allowblogcss', $edited_Blog->get( 'allowblogcss' ), T_('Allow customized blog CSS file'), T_('You will be able to customize the blog\'s skin stylesheet with a file named style.css in the blog\'s media file folder.') );
		$Form->checkbox( 'blog_allowusercss', $edited_Blog->get( 'allowusercss' ), T_('Allow user customized CSS file for this blog'), T_('Users will be able to customize the blog and skin stylesheets with a file named style.css in their personal file folder.') );
	$Form->end_fieldset();

}


$Form->end_form( array(
	array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
	array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
 * Revision 1.23  2010/01/27 15:20:07  efy-asimo
 * Change select list to radio button
 *
 * Revision 1.22  2010/01/22 04:28:51  fplanque
 * fixes
 *
 * Revision 1.21  2010/01/20 20:08:31  efy-asimo
 * Countries&Currencies redirect fix + RSS/Atom feeds image size select list
 *
 * Revision 1.20  2010/01/03 13:45:36  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.19  2009/10/04 23:06:30  fplanque
 * doc
 *
 * Revision 1.18  2009/09/29 16:56:12  tblue246
 * Added setting to disable sitemaps skins
 *
 * Revision 1.17  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.16  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.15  2009/08/31 17:21:32  fplanque
 * minor
 *
 * Revision 1.14  2009/08/27 12:24:27  tblue246
 * Added blog setting to display comments in ascending/descending order
 *
 * Revision 1.13  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.12  2008/07/01 08:32:12  fplanque
 * minor
 *
 * Revision 1.11  2008/06/30 23:47:04  blueyed
 * require_title setting for Blogs, defaulting to 'required'. This makes the title field now a requirement (by default), since it often gets forgotten when posting first (and then the urltitle is ugly already)
 *
 * Revision 1.10  2008/04/03 22:03:06  fplanque
 * added "save & edit" and "publish now" buttons to edit screen.
 *
 * Revision 1.9  2008/02/09 20:14:14  fplanque
 * custom fields management
 *
 * Revision 1.8  2008/01/21 09:35:27  fplanque
 * (c) 2008
 *
 * Revision 1.7  2008/01/17 18:10:11  fplanque
 * deprecated linkblog_ID blog param
 *
 * Revision 1.6  2008/01/15 08:19:40  fplanque
 * blog footer text tag
 *
 * Revision 1.5  2008/01/10 19:59:51  fplanque
 * reduced comment PITA
 *
 * Revision 1.4  2007/12/26 17:21:17  fplanque
 * Anne's pony about full posts in RSS
 *
 * Revision 1.3  2007/12/18 23:50:40  fplanque
 * minor
 *
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