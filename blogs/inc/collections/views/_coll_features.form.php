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


$Form = & new Form( NULL, 'coll_features_checkchanges' );

$Form->begin_form( 'fform' );

$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'features' );
$Form->hidden( 'blog', $edited_Blog->ID );

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

$Form->begin_fieldset( T_('Multiple authors') );
	$Form->checkbox( 'advanced_perms', $edited_Blog->get( 'advanced_perms' ), T_('Use advanced perms'), T_('This will turn on the advanced User and Group permissions tabs for this blog.') );
	$Form->checkbox( 'blog_use_workflow', $edited_Blog->get_setting( 'use_workflow' ), T_('Use workflow'), T_('This will notably turn on the Tracker tab in the Posts view.') );
$Form->end_fieldset();


$Form->end_form( array(
	array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
	array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
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