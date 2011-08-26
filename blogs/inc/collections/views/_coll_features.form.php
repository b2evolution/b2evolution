<?php
/**
 * This file implements the UI view for the Collection features properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
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
		var fldset = jQuery( '.feedback_details_container' );
		if( ob.value == 'never' )
		{
			for( i = 0; i < fldset.length; i++ )
			{
				fldset[i].style.display = 'none';
			}
		}
		else
		{
			for( i = 0; i < fldset.length; i++ )
			{
				fldset[i].style.display = '';
			}
		}
	}
	//-->
</script>
<?php

$Form = new Form( NULL, 'coll_features_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'features' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Post options').get_manual_link('blog_features_settings') );
	$Form->radio( 'require_title', $edited_Blog->get_setting('require_title'),
								array(  array( 'required', T_('Always'), T_('The blogger must provide a title') ),
												array( 'optional', T_('Optional'), T_('The blogger can leave the title field empty') ),
												array( 'none', T_('Never'), T_('No title field') ),
											), T_('Post titles'), true );
											
	$Form->checkbox( 'enable_goto_blog', $edited_Blog->get_setting( 'enable_goto_blog' ),
						T_( 'View blog after publishing' ), T_( 'Check this to automatically view the blog after publishing a post.' ) );

	// FP> TODO:
	// -post_url  always('required')|optional|never
	// -multilingual:  true|false   or better yet: provide a list to narrow down the active locales
	// -tags  always('required')|optional|never

	$Form->radio( 'post_categories', $edited_Blog->get_setting('post_categories'),
		array( array( 'one_cat_post', T_('Allow only one category per post') ),
			array( 'multiple_cat_post', T_('Allow multiple categories per post') ),
			array( 'main_extra_cat_post', T_('Allow one main + several extra categories') ),
			array( 'no_cat_post', T_('Don\'t allow category selections'), T_('(Main cat will be assigned automatically)') ) ),
			T_('Post category options'), true );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Feedback options') );
	$Form->radio( 'allow_view_comments', $edited_Blog->get_setting( 'allow_view_comments' ),
						array(  array( 'any', T_('Any user'), T_('Including anonymous users') ),
								array( 'registered', T_('Registered users only') ),
								array( 'member', T_('Members only'),  T_( 'Users have to be members of this blog' ) ),
								array( 'moderator', T_('Moderators & Admins only') ),
					), T_('Comment viewing by'), true );

	$Form->radio( 'allow_comments', $edited_Blog->get_setting( 'allow_comments' ),
						array(  array( 'any', T_('Any user'), T_('Including anonymous users'),
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'registered', T_('Registered users only'),  '',
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'member', T_('Members only'),  T_( 'Users have to be members of this blog' ),
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'never', T_('Not allowed'), '',
										'', 'onclick="show_hide_feedback_details(this);"'),
					), T_('Comment posting by'), true );

	echo '<div class="feedback_details_container">';

	$Form->checkbox( 'disable_comments_bypost', $edited_Blog->get_setting( 'disable_comments_bypost' ), '', T_('Comments can be disabled on each post separately') );

	$Form->checkbox( 'allow_anon_url', $edited_Blog->get_setting( 'allow_anon_url' ), T_('Anonymous URLs'), T_('Allow anonymous commenters to submit an URL') );

	$any_option = array( 'any', T_('Any user'), T_('Including anonymous users'), '' );
	$registered_option = array( 'registered', T_('Registered users only'),  '', '' );
	$member_option = array( 'member', T_('Members only'), T_('Users have to be members of this blog'), '' );
	$never_option = array( 'never', T_('Not allowed'), '', '' );
	$Form->radio( 'allow_attachments', $edited_Blog->get_setting( 'allow_attachments' ),
						array(  $any_option, $registered_option, $member_option, $never_option,
						), T_('Allow attachments from'), true );

	$Form->radio( 'allow_rating', $edited_Blog->get_setting( 'allow_rating' ),
						array( $any_option, $registered_option, $member_option, $never_option,
						), T_('Allow ratings from'), true );

	echo '</div>';

	$Form->radio( 'allow_attending', $edited_Blog->get_setting( 'allow_attending' ),
						array( array( 'never', T_( 'Never' ), '', '' ),
								array( 'enable_bypost', T_( 'Let post author decide' ), '', '' ),
								array( 'always', T_( 'Always' ), '', '' )
						), T_( 'Allow users to attend events posted in this blog' ), true );

	echo '<div class="feedback_details_container">';

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

	$Form->checkbox( 'paged_comments', $edited_Blog->get_setting( 'paged_comments' ), T_( 'Paged comments' ), T_( 'Check to enable paged comments on the public pages.' ) );

	$Form->text( 'comments_per_page', $edited_Blog->get_setting('comments_per_page'), 4, T_('Comments/Page'),  T_('How many comments do you want to display on one page?'), 4 );

	global $default_avatar;
	$Form->radio( 'default_gravatar', $edited_Blog->get_setting('default_gravatar'),
						array(	array( 'b2evo', T_('Default image'), $default_avatar ),
								array ('', T_('Gravatar') ),
								array ('identicon', T_('Identicon') ),
								array ('monsterid', T_('Monsterid') ),
								array ('wavatar', T_('Wavatar') ),
						), T_('Default gravatars'), true, T_('Gravatar users can choose to set up a unique icon for themselves, and if they don\'t, they will be assigned a default image.') );

	echo '</div>';

	if( $edited_Blog->get_setting( 'allow_comments' ) == 'never' )
	{ ?>
	<script type="text/javascript">
		<!--
		var fldset = jQuery( '.feedback_details_container' );
		for( i = 0; i < fldset.length; i++ )
		{
			fldset[i].style.display = 'none';
		}
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
											), T_('Post feed contents'), true, T_('How much content do you want to make available in post feeds?') );

	$Form->radio( 'comment_feed_content', $edited_Blog->get_setting('comment_feed_content'),
								array(  array( 'none', T_('No feeds') ),
										array( 'excerpt', T_('Comment excerpts') ),
										array( 'normal', T_('Standard comment contents') ),
									), T_('Comment feed contents'), true, T_('How much content do you want to make available in comment feeds?') );

	$Form->text( 'posts_per_feed', $edited_Blog->get_setting('posts_per_feed'), 4, T_('Posts in feeds'),  T_('How many of the latest posts do you want to include in RSS & Atom feeds?'), 4 );

	if( isset($GLOBALS['files_Module']) )
	{
		load_funcs( 'files/model/_image.funcs.php' );
		$params['force_keys_as_values'] = true;
		$Form->select_input_array( 'image_size', $edited_Blog->get_setting('image_size') , get_available_thumb_sizes(), T_('Image size'), '', $params );
	}
$Form->end_fieldset();


$Form->begin_fieldset( T_('Sitemaps') );
	$Form->checkbox( 'enable_sitemaps', $edited_Blog->get_setting( 'enable_sitemaps' ),
						T_( 'Enable sitemaps' ), T_( 'Check to allow usage of skins with the "sitemap" type.' ) );
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
	$Form->checkbox( 'allow_item_subscriptions', $edited_Blog->get_setting( 'allow_item_subscriptions' ), '', T_( 'Allow users to subscribe and receive email notifications for comments on a specific post.' ) );
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

?>
<script type="text/javascript">
	jQuery( '#paged_comments' ).click( function()
	{
		if ( $('#paged_comments').is(':checked') )
		{
			$('#comments_per_page').val('20');
		}
		else
		{
			$('#comments_per_page').val('1000');
		}
	} );
</script>
<?php


/*
 * $Log$
 * Revision 1.44  2011/08/26 07:40:13  efy-asimo
 * Setting to show comment to "Members only"
 *
 * Revision 1.43  2011/08/23 21:42:24  fplanque
 * doc
 *
 * Revision 1.42  2011/05/25 14:59:33  efy-asimo
 * Post attending
 *
 * Revision 1.41  2011/05/23 02:20:07  sam2kb
 * Option to display excerpts in comment feeds, or disable feeds completely
 *
 * Revision 1.40  2011/05/19 17:47:07  efy-asimo
 * register for updates on a specific blog post
 *
 * Revision 1.39  2011/03/02 09:45:59  efy-asimo
 * Update collection features allow_comments, disable_comments_bypost, allow_attachments, allow_rating
 *
 * Revision 1.38  2010/11/03 19:44:14  sam2kb
 * Increased modularity - files_Module
 * Todo:
 * - split core functions from _file.funcs.php
 * - check mtimport.ctrl.php and wpimport.ctrl.php
 * - do not create demo Photoblog and posts with images (Blog A)
 *
 * Revision 1.37  2010/10/19 02:00:53  fplanque
 * MFB
 *
 * Revision 1.36  2010/10/13 14:07:55  efy-asimo
 * Optional paged comments in the front end
 *
 * Revision 1.35  2010/09/08 15:07:44  efy-asimo
 * manual links
 *
 * Revision 1.34  2010/07/26 06:52:16  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.33  2010/07/13 23:54:50  sam2kb
 * type
 *
 * Revision 1.32  2010/07/06 08:17:39  efy-asimo
 * Move "Multiple authors" block to Blog setings advanced tab. Fix validating urlname when user has no blog_admin permission.
 *
 * Revision 1.31  2010/06/08 22:29:25  sam2kb
 * Per blog settings for different default gravatar types
 *
 * Revision 1.30  2010/06/08 01:49:53  sam2kb
 * Paged comments in frontend
 *
 * Revision 1.29  2010/05/22 12:22:49  efy-asimo
 * move $allow_cross_posting in the backoffice
 *
 * Revision 1.28  2010/02/26 22:15:47  fplanque
 * whitespace/doc/minor
 *
 * Revision 1.26  2010/02/08 17:52:09  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.25  2010/02/06 11:48:32  efy-yury
 * add checkbox 'go to blog after posting' in blog settings
 *
 * Revision 1.24  2010/01/30 18:55:21  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
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