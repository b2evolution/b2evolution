<?php
/**
 * This file display the 1st step of WordPress XML importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', T_('WordPress XML Importer') );

$Form->add_crumb( 'wpxml' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'import' );

$Form->begin_fieldset( T_('Select XML file') );

	$Form->text_input( 'wp_file', '', 20, T_('WordPress XML File'), '', array( 'type' => 'file', 'required' => true ) );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Select a blog for import') );

	$BlogCache = & get_BlogCache();
	$BlogCache->none_option_text = '&nbsp;';

	$Form->select_input_object( 'wp_blog_ID', param( 'wp_blog_ID', 'integer', 0 ), $BlogCache, T_('Blog for import'), array(
			'note' => T_('This blog will be used for import.').' <a href="'.$dispatcher.'?ctrl=collections&action=new">'.T_('Create new blog').' &raquo;</a>',
			'allow_none' => true, 'required' => true ) );

	$Form->radio_input( 'import_type', param( 'import_type', 'string', 'replace' ), array(
				array(
					'value' => 'replace',
					'label' => T_('Replace existing contents'),
					'note'  => T_('WARNING: this option will permanently remove existing Posts, comments, categories and tags from the selected blog.') ),
				array(
					'value' => 'append',
					'label' => T_('Append to existing contents') ),
			), '', array( 'lines' => true ) );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', T_('Continue !'), 'SaveButton' ),
											 array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

$Form->end_form();

/*
 * $Log$
 * Revision 1.2  2013/11/06 08:04:55  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>