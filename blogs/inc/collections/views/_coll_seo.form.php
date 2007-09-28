<?php
/**
 * This file implements the UI view for the Collection SEO properties.
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
$Form->hidden( 'tab', 'seo' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Canonical URL control').get_manual_link('canonical_url_control') );
	$Form->checkbox( 'canonical_item_urls', $edited_Blog->get_setting( 'canonical_item_urls' ), T_('Posts / Pages'), T_('301 redirect to canonical URL') );
	$Form->checkbox( 'canonical_cat_urls', $edited_Blog->get_setting( 'canonical_cat_urls' ), T_('Categories'), T_('301 redirect to canonical URL') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Blog content pages indexing') );	$Form->checkbox( 'default_noindex', $edited_Blog->get_setting( 'default_noindex' ), T_('Default blog page'), T_('META NOINDEX') );
	$Form->checkbox( 'paged_noindex', $edited_Blog->get_setting( 'paged_noindex' ), T_('Following blog pages'), T_('META NOINDEX').' - '.T_('Page 2,3,4...') );
	$Form->checkbox( 'archive_noindex', $edited_Blog->get_setting( 'archive_noindex' ), T_('Archive pages'), T_('META NOINDEX') );
	$Form->checkbox( 'category_noindex', $edited_Blog->get_setting( 'category_noindex' ), T_('Category pages'), T_('META NOINDEX') );
	$Form->checkbox( 'filtered_noindex', $edited_Blog->get_setting( 'filtered_noindex' ), T_('Other filtered pages'), T_('META NOINDEX') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Special features pages indexing') );
	$Form->checkbox( 'arcdir_noindex', $edited_Blog->get_setting( 'arcdir_noindex' ), T_('Archive directory'), T_('META NOINDEX') );
	$Form->checkbox( 'catdir_noindex', $edited_Blog->get_setting( 'catdir_noindex' ), T_('Category directory'), T_('META NOINDEX') );
	$Form->checkbox( 'feedback-popup_noindex', $edited_Blog->get_setting( 'feedback-popup_noindex' ), T_('Comment popups'), T_('META NOINDEX') );
	$Form->checkbox( 'msgform_noindex', $edited_Blog->get_setting( 'msgform_noindex' ), T_('Message forms'), T_('META NOINDEX').' - '.T_('Keyword searched, etc.') );
	$Form->checkbox( 'special_noindex', $edited_Blog->get_setting( 'special_noindex' ), T_('Other special pages'), T_('META NOINDEX').' - '.T_('User profile form, etc.') );
$Form->end_fieldset();


$Form->end_form( array(
	array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
	array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
 * Revision 1.1  2007/09/28 09:28:36  fplanque
 * per blog advanced SEO settings
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