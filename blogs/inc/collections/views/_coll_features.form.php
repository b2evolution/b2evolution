<?php
/**
 * This file implements the UI view for the Collection features properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
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


$Form = new Form( NULL, 'coll_features_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'features' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Post list') );
  $Form->select_input_array( 'orderby', $edited_Blog->get_setting('orderby'), get_available_sort_options(), T_('Order by'), T_('Default ordering of posts.') );
  $Form->select_input_array( 'orderdir', $edited_Blog->get_setting('orderdir'), array(
                        'ASC'  => T_('Ascending'),
                        'DESC' => T_('Descending'), ), T_('Direction') );
  $Form->radio( 'what_to_show', $edited_Blog->get_setting('what_to_show'),
                array(  array( 'days', T_('days') ),
                        array( 'posts', T_('posts') ),
                      ), T_('Display unit'), false,  T_('Do you want to restrict on the number of days or the number of posts?') );
  $Form->text( 'posts_per_page', $edited_Blog->get_setting('posts_per_page'), 4, T_('Posts/Days per page'), T_('How many days or posts do you want to display on the home page?'), 4 );

  $Form->radio( 'timestamp_min', $edited_Blog->get_setting('timestamp_min'),
                array(  array( 'yes', T_('yes') ),
                        array( 'no', T_('no') ),
                        array( 'duration', T_('only the last') ),
                      ), T_('Show past posts'), true );
  $Form->duration_input( 'timestamp_min_duration', $edited_Blog->get_setting('timestamp_min_duration'), '' );

  $Form->radio( 'timestamp_max', $edited_Blog->get_setting('timestamp_max'),
                array(  array( 'yes', T_('yes') ),
                        array( 'no', T_('no') ),
                        array( 'duration', T_('only the next') ),
                      ), T_('Show future posts'), true );
  $Form->duration_input( 'timestamp_max_duration', $edited_Blog->get_setting('timestamp_max_duration'), '' );

$Form->end_fieldset();


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

// display features settings provided by optional modules:
// echo 'modules';
modules_call_method( 'display_collection_features', array( 'Form' => & $Form, 'edited_Blog' => & $edited_Blog ) );

$Form->begin_fieldset( T_('RSS/Atom feeds') );
	$Form->radio( 'feed_content', $edited_Blog->get_setting('feed_content'),
								array(  array( 'none', T_('No feeds') ),
												array( 'title', T_('Titles only') ),
												array( 'excerpt', T_('Post excerpts') ),
												array( 'normal', T_('Standard post contents (stopping at "&lt;!-- more -->")') ),
												array( 'full', T_('Full post contents (including after "&lt;!-- more -->")') ),
											), T_('Post feed contents'), true, T_('How much content do you want to make available in post feeds?') );

	$Form->text( 'posts_per_feed', $edited_Blog->get_setting('posts_per_feed'), 4, T_('Posts in feeds'),  T_('How many of the latest posts do you want to include in RSS & Atom feeds?'), 4 );

	if( isset($GLOBALS['files_Module']) )
	{
		load_funcs( 'files/model/_image.funcs.php' );
		$params['force_keys_as_values'] = true;
		$Form->select_input_array( 'image_size', $edited_Blog->get_setting('image_size') , get_available_thumb_sizes(), T_('Image size'), '', $params );
	}
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


$Form->end_form( array(
	array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
	array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

?>
<?php


/*
 * $Log$
 * Revision 1.55  2011/10/05 21:13:45  fplanque
 * no message
 *
 * Revision 1.54  2011/10/05 13:49:07  efy-yurybakh
 * Add settings for a $timestamp_min & $timestamp_max
 *
 * Revision 1.53  2011/10/05 12:05:02  efy-yurybakh
 * Blog settings > features tab refactoring
 *
 * Revision 1.52  2011/09/30 13:03:20  fplanque
 * doc
 *
 * Revision 1.51  2011/09/30 08:22:18  efy-asimo
 * Events update
 *
 * Revision 1.50  2011/09/30 04:56:39  efy-yurybakh
 * RSS feed settings
 *
 * Revision 1.49  2011/09/28 12:09:53  efy-yurybakh
 * "comment was helpful" votes (new tab "comments")
 *
 * Revision 1.48  2011/09/27 08:55:15  efy-asimo
 * Display module features in different fieldset
 *
 * Revision 1.47  2011/09/08 05:22:40  efy-asimo
 * Remove item attending and add item settings
 *
 * Revision 1.46  2011/09/06 00:54:38  fplanque
 * i18n update
 *
 * Revision 1.45  2011/09/04 22:13:14  fplanque
 * copyright 2011
 *
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
 */
?>