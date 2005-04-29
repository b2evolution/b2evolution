<?php
/**
 * This file implements the UI for file copy / move / rename.
 *
 * fplanque>> This whole thing is flawed:
 * 1) only geeks can possibly like to use the same interface for renaming, moving and copying
 * 2) even the geeky unix commands won't pretend copying and moving are the same thing. They are not!
 *    Only moving and renaming are similar, and again FOR GEEKS ONLY.
 * 3) The way this works it breaks the File meta data (I'm working on it).
 * 4) For Move and Copy, this should use a "destination directory tree" on the right (same as for upload)
 * 5) Given all the reasons above copy, move and rename should be clearly separated into 3 different interfaces.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

if( false )
{	/**
	 * This is ugly, sorry, but I temporarily need this until NuSphere fixes their CodeInsight :'(
	 */
	include('_header.php');
	include('files.php');
}

// Begin payload block:
$AdminUI->dispPayloadBegin();

$Form = & new Form( 'files.php' );

$Form->global_icon( T_('Quit copy/move/rename mode!'), 'close',	$Fileman->getCurUrl( array( 'fm_mode' => false, 'forceFM' => 1 ) ) );

$Form->begin_form( 'fform', T_('Copy / Move / Rename') );

	echo $Fileman->getFormHiddenInputs();
	$Form->hidden( 'cmr_doit', 1 );

	echo '<div class="notes"><strong>'.T_('You are in copy-move-rename mode.')
					.'</strong><br />'.T_('Please navigate to the desired location.').'</div>';

	$LogCmr->display( '', '', true, 'all' );

	$sourcesInSameDir = true;

	while( $lSourceFile = & $Fileman->SourceList->get_next() )
	{
		if( $sourcesInSameDir && $lSourceFile->get_dir() != $Fileman->cwd )
		{
			$sourcesInSameDir = false;
		}
		?>

		<fieldset>
			<legend><?php echo T_('Source').': '.$lSourceFile->get_full_path(); ?></legend>

			<?php
			if( isset( $cmr_overwrite[$lSourceFile->get_md5_ID()] )
					&& $cmr_overwrite[$lSourceFile->get_md5_ID()] === 'ask' )
			{
				form_checkbox( 'overwrite', 0, '<span class="error">'.T_('Overwrite existing file').'</span>',
												sprintf( T_('The existing file &laquo;%s&raquo; will be replaced with this file.'),
																	$TargetFile->get_full_path() ) );
			}
			?>

			<div class="label">
				<label for="cmr_keepsource_<?php $lSourceFile->get_md5_ID(); ?>"><?php echo T_('Keep source file') ?>:</label>
			</div>
			<div class="input">
				<input class="checkbox" type="checkbox" value="1"
					name="cmr_keepsource[<?php echo $lSourceFile->get_md5_ID(); ?>]"
					id="cmr_keepsource_<?php $lSourceFile->get_md5_ID(); ?>"
					onclick="setCmrSubmitButtonValue( this.form );"<?php
					if( $cmr_keepsource )
					{
						echo ' checked="checked"';
					} ?> />
				<span class="notes"><?php echo T_('Copy instead of move.') ?></span>
			</div>
			<div class="clear"></div>


			<div class="label">
				<label for="cmr_newname_<?php $lSourceFile->get_md5_ID(); ?>">New name:</label>
			</div>
			<div class="input">
				<input type="text" name="cmr_newname[<?php $lSourceFile->get_md5_ID(); ?>]"
					id="cmr_newname_<?php $lSourceFile->get_md5_ID(); ?>" value="<?php
					echo isset( $cmr_newname[$lSourceFile->get_md5_ID()] ) ?
									$cmr_newname[$lSourceFile->get_md5_ID()] :
									$lSourceFile->get_name() ?>" />
			</div>

		</fieldset>

	<?php
	}

	// text and value for JS dynamic fields, when referring to move/rename
	if( $sourcesInSameDir )
	{
		$submitMoveOrRenameText = T_('Rename');
		$submitMoveOrRenameText_JS = TS_('Rename');
	}
	else
	{
		$submitMoveOrRenameText = T_('Move');
		$submitMoveOrRenameText_JS = TS_('Move');
	}


$Form->end_form( array( array( 'submit', 'cmr_submit', $cmr_keepsource ?  T_('Copy') : $submitMoveOrRenameText, 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

?>
<script type="text/javascript">
	<!--
	function setCmrSubmitButtonValue()
	{
		if( document.getElementById( 'cmr_keepsource' ).checked )
		{
			text = '<?php echo TS_('Copy') ?>';
		}
		else
		{
			text = '<?php echo $submitMoveOrRenameText_JS ?>';
		}
		document.getElementById( 'cmr_submit' ).value = text;
	}
	setCmrSubmitButtonValue(); // init call
	// -->
</script>
<?php

// End payload block:
$AdminUI->dispPayloadEnd();

/*
 * $Log$
 * Revision 1.4  2005/04/29 18:49:32  fplanque
 * Normalizing, doc, cleanup
 *
 * Revision 1.3  2005/04/27 19:05:44  fplanque
 * normalizing, cleanup, documentaion
 *
 * Revision 1.2  2005/04/14 19:57:52  fplanque
 * filemanager refactoring & cleanup
 * started implementation of properties/meta data editor
 * note: the whole fm_mode thing is not really desireable...
 *
 * Revision 1.1  2005/04/14 18:34:03  fplanque
 * filemanager refactoring
 *
 * Revision 1.3  2005/04/13 17:48:21  fplanque
 * File manager refactoring
 * storing of file meta data through upload
 * displaying or metadate in previews
 *
 * Revision 1.2  2005/04/12 19:36:30  fplanque
 * File manager cosmetics
 *
 * Revision 1.1  2005/04/12 19:00:22  fplanque
 * File manager cosmetics
 *
 * This file was extracted from _files.php
 */
?>