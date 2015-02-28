<?php
/**
 * This file implements the file editing form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var File
 */
global $edited_File;

$Form = new Form( NULL, 'file_edit' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url('fm_mode') );

$Form->begin_form( 'fform', T_('Editing:').' '.$edited_File->get_rdfs_rel_path() );
	$Form->hidden_ctrl();
	$Form->add_crumb( 'file' );
	$Form->hidden( 'action', 'update_file' );
	$Form->hiddens_by_key( get_memorized() );

 	$Form->switch_layout( 'none' );
	echo '<div class="center">';

	$Form->textarea_input( 'file_content', $edited_File->content, 25, '', array( 'cols' => '80' ) );

	$Form->buttons( array( array( 'submit', '', T_('Save Changes!'), 'SaveButton' ) ) );

	echo '</div>';
 	$Form->switch_layout( NULL );

$Form->end_form();

?>