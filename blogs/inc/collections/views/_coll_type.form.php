<?php
/**
 * This file implements the UI view for the blog type.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _coll_type.form.php 2139 2012-10-15 06:21:30Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;


global $action, $next_action, $blogtemplate, $blog, $tab, $admin_url;

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update_type' );
$Form->hidden( 'tab', $tab );
$Form->hidden( 'blog', $blog );


$Form->begin_fieldset( T_('Collection type').get_manual_link('collection-type') );

	$collection_kinds = get_collection_kinds();
	$radio_options = array();
	foreach( $collection_kinds as $kind_value => $kind )
	{
		$radio_options[] = array( $kind_value, $kind['name'], $kind['desc'] );
	}
	$Form->radio( 'type', $edited_Blog->get( 'type' ), $radio_options, T_('Type'), true );

	$Form->checkbox_input( 'reset', 0, T_('Reset'), array(
			'input_suffix' => ' '.T_('Reset all parameters as for a new blog.'),
			'note' => T_('(Only keep blog name, owner, URL, categories and content).')
		) );

$Form->end_fieldset();


$Form->buttons( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

$Form->end_form();

?>