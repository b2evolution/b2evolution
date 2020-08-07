<?php
/**
 * This file implements the UI for file display settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var UserSettings
 */
global $UserSettings;

$Form = new Form( NULL, 'file_displaysettings_checkchanges' );

$Form->global_icon( TB_('Close settings!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', TB_('Display settings').get_manual_link('file-manager-display-settings') );

	$Form->add_crumb( 'file' );
	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );

	$Form->begin_fieldset( TB_('Images') );
		$Form->checkbox( 'option_imglistpreview', $UserSettings->get('fm_imglistpreview'), TB_('Thumbnails'), TB_('Check to display thumbnails instead of icons for image files') );
		$Form->checkbox( 'option_getimagesizes', $UserSettings->get('fm_getimagesizes'), TB_('Dimensions'), TB_('Check to display the pixel dimensions of image files') );
	$Form->end_fieldset();

	$Form->begin_fieldset( TB_('Columns') );
		$Form->checkbox( 'option_showtypes', $UserSettings->get('fm_showtypes'), TB_('File type'), TB_('Based on file extension') );
		$Form->radio_input( 'option_showdate', $UserSettings->get('fm_showdate'), array(
				array( 'value'=>'no', 'label'=>TB_('No') ),
				array( 'value'=>'compact', 'label'=>TB_('Smart date format') ),
				array( 'value'=>'long', 'label'=>TB_('Long format') ) ), TB_('Last change') );
		$Form->checklist( array(
				array( 'option_showfsperms', 1, TB_('Unix file permissions'), $UserSettings->get( 'fm_showfsperms' ) ),
				array( 'option_permlikelsl', 1, TB_('Check to display file permissions like "rwxr-xr-x" rather than short form'), $UserSettings->get( 'fm_permlikelsl' ) ),
			), 'unix_options', TB_('File permissions') );
		$Form->checkbox( 'option_showfsowner', $UserSettings->get('fm_showfsowner'), TB_('File Owner'), TB_('Unix file owner') );
		$Form->checkbox( 'option_showfsgroup', $UserSettings->get('fm_showfsgroup'), TB_('File Group'), TB_('Unix file group') );
		$Form->checkbox( 'option_showcreator', $UserSettings->get('fm_showcreator'), TB_('Added by'), TB_('File creator') );
		$Form->checkbox( 'option_showdownload', $UserSettings->get('fm_showdownload'), TB_('Download Count'), TB_('Number of times the file has been downloaded through disp=download') );
	$Form->end_fieldset();

	$Form->begin_fieldset( TB_('Options') );
		$Form->checklist( array(
				array( 'option_showhidden', 1, TB_('Check this to show system hidden files. System hidden files start with a dot (.)'), $UserSettings->get( 'fm_showhidden' ) ),
				array( 'option_showevocache', 1, TB_('Check this to show _evocache folders (not recommended)'), $UserSettings->get( 'fm_showevocache' ) ),
			), 'hidden_options', TB_('Hidden files') );
		$Form->checkbox( 'option_dirsattop', !$UserSettings->get('fm_dirsnotattop'), TB_('Folders first'), TB_('Check to always display folders before files') );
		$Form->checkbox( 'option_recursivedirsize', $UserSettings->get('fm_recursivedirsize'), TB_('Folder sizes'), TB_('Check to compute recursive size of folders') );
		$Form->radio_input( 'option_allowfiltering', $UserSettings->get('fm_allowfiltering'), array(
				array( 'value'=>'no', 'label'=>TB_('Don\'t show') ),
				array( 'value'=>'simple', 'label'=>TB_('Simple') ),
				array( 'value'=>'regexp', 'label'=>TB_('With regular expressions') ) ), TB_('Filter box') );
	$Form->end_fieldset();

if( check_user_perm( 'options', 'edit', false ) )
{	// TODO: better perm check
  /**
	 * @var FileRoot
	 */
	global $fm_FileRoot;

	if( $fm_FileRoot->type == 'collection' )
	{
		echo '<p class="note">'.TB_('See also:').' ';
		echo TB_('Blog Settings').' &gt; '.TB_('Advanced').' &gt; <a href="?ctrl=coll_settings&tab=advanced&blog='.$fm_FileRoot->in_type_ID.'">'
					.TB_('Media directory location').'</a>';
	}
}

$Form->end_form( array( array( 'submit', 'actionArray[update_settings]', TB_('Save Changes!'), 'SaveButton') ) );

?>
