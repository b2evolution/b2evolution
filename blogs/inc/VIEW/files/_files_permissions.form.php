<?php
/**
 * This file implements the UI for file permissions
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
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


/**
 * @global Filelist
 */
global $selected_Filelist;

global $more_than_one_selected_file, $perms_read_readonly, $field_options_read_readonly;


$Form = & new Form( NULL, 'fm_perms_checkchanges' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', T_('Change permissions') );
	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );
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

		$Form->info_field( '', '<a id="checkallspan_edit_perms_set" href="#" onclick="toggleCheckboxes(\'fm_perms_checkchanges\', \'use_default_perms[]\', \'edit_perms_set\'); return false;">'.T_('check all').'</a>' );

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
		{ // readonly/write only (WINDOWS): display radio inputs to change readonly/write
			$field_options = $field_options_read_readonly;
			$field_params = array();
			if( !empty($use_default_perms_checkbox) )
			{
				$field_params['field_suffix'] = str_replace( '%file_ID%', $l_File->get_md5_ID(), $use_default_perms_checkbox );
			}

			$l_perms = $l_File->get_perms( 'octal' );
			if( $l_perms == 555 )
			{
				$field_options[0]['params']['checked'] = 'checked';
			}
			else
			{
				$field_options[1]['params']['checked'] = 'checked';
			}
			$Form->radio_input( 'perms['.$l_File->get_md5_ID().']', false, $field_options, $l_File->get_rdfp_rel_path(), $field_params );
		}
		else
		{ // display a text input with UNIX perms
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
 * Revision 1.6  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.5  2006/04/19 20:13:51  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.4  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.3  2006/03/12 03:25:44  blueyed
 * doc fixed
 *
 * Revision 1.2  2006/03/12 03:03:33  blueyed
 * Fixed and cleaned up "filemanager".
 *
 * Revision 1.1  2006/02/23 21:12:17  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.3  2006/02/11 21:17:54  fplanque
 * fixed permissions on windows;
 * added bozo validator
 *
 * Revision 1.2  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.1  2005/11/19 23:48:28  blueyed
 * "Edit File permissions" action fixed/finished
 *
 */
?>