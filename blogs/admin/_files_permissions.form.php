<?php
/**
 * This file implements the UI for file permissions
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link https://thequod.de/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * In addition, as a special exception, the copyright holders give permission to link
 * the code of this program with the PHP/SWF Charts library by maani.us (or with
 * modified versions of this library that use the same license as PHP/SWF Charts library
 * by maani.us), and distribute linked combinations including the two. You must obey the
 * GNU General Public License in all respects for all of the code used other than the
 * PHP/SWF Charts library by maani.us. If you modify this file, you may extend this
 * exception to your version of the file, but you are not obligated to do so. If you do
 * not wish to do so, delete this exception statement from your version.
 * }}
 *
 * {@internal
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$Form = & new Form( 'files.php', 'form_edit_perms' );

$Form->begin_form( 'fform', T_('Change permissions') );
	echo $Fileman->getFormHiddenInputs();
	echo $Fileman->getFormHiddenSelectedFiles();
	$Form->hidden( 'action', 'edit_perms' );

	if( $more_than_one_selected_file )
	{ // more than one file, provide default
		$Form->begin_fieldset( T_('Default') );

		if( $perms_read_readonly )
		{
			$Form->radio_input( 'edit_perms_default', $edit_perms_default, $field_options_read_readonly, T_('Default permissions') );
		}
		else
		{
			$Form->text_input( 'edit_perms_default', $edit_perms_default, 3, T_('Default permissions') );
		}

		$Form->info_field( '', '<a id="checkallspan_edit_perms_set" href="#" onclick="toggleCheckboxes(\'form_edit_perms\', \'use_default_perms[]\', \'edit_perms_set\'); return false;">'.T_('check all').'</a>' );

		$Form->end_fieldset();
	}

	if( $more_than_one_selected_file )
	{ // generate checkbox input to use with every file
		$Form->output = false;
		$Form->switch_layout('none');
		$use_default_perms_checkbox = $Form->checkbox_input(
			'use_default_perms[]', $checked = 0, T_('Use default value'),
			array( 'value' => '%file_ID%', 'id' => 'use_default_perms_%file_ID%' ) );
		$Form->switch_layout(NULL);
		$Form->output = true;
	}

	$selected_Filelist->restart();

	$Form->begin_fieldset();
	while( $l_File = & $selected_Filelist->get_next() )
	{
		if( $perms_read_readonly )
		{ // readonly/write only: display radio inputs to change readonly/write
			$field_options = $field_options_read_readonly;
			$field_params = array();
			if( !empty($use_default_perms_checkbox) )
			{
				$field_params['field_suffix'] = str_replace( '%file_ID%', $l_File->get_md5_ID(), $use_default_perms_checkbox );
			}

			$l_perms = $l_File->get_perms( 'octal' );
			if( $l_perms == 444 )
			{
				$field_options[0]['params']['checked'] = 'checked';
			}
			elseif( $l_perms == 666 )
			{
				$field_options[1]['params']['checked'] = 'checked';
			}
			$Form->radio_input( 'perms['.$l_File->get_md5_ID().']', false, $field_options, $l_File->get_rdfp_rel_path(), $field_params );
		}
		else
		{ // display a text input with unix perms
			$field_params = array();
			if( !empty($use_default_perms_checkbox) )
			{
				$field_params['field_suffix'] = str_replace( '%file_ID%', $l_File->get_md5_ID(), $use_default_perms_checkbox );
			}

			$Form->text_input( 'perms['.$l_File->get_md5_ID().']', $l_File->get_perms( 'octal' ), 3, $l_File->get_rdfp_rel_path(), $field_params );
		}
	}
	$Form->end_fieldset();

$Form->end_form( array(
		array( 'submit', 'submit', T_('Set new permissions'), 'ActionButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );


/*
 * $Log$
 * Revision 1.2  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.1  2005/11/19 23:48:28  blueyed
 * "Edit File permissions" action fixed/finished
 *
 */
?>