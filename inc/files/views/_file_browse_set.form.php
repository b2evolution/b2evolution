<?php
/**
 * This file implements the UI for file display settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var UserSettings
 */
global $UserSettings;

$Form = new Form( NULL, 'file_displaysettings_checkchanges' );

$Form->global_icon( T_('Close settings!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', T_('Display settings').get_manual_link('file-manager-display-settings') );

	$Form->add_crumb( 'file' );
	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );

	$Form->begin_fieldset( T_('Images') );
		$Form->checkbox( 'option_imglistpreview', $UserSettings->get('fm_imglistpreview'), T_('Thumbnails'), T_('Check to display thumbnails instead of icons for image files') );
		$Form->checkbox( 'option_getimagesizes', $UserSettings->get('fm_getimagesizes'), T_('Dimensions'), T_('Check to display the pixel dimensions of image files') );
	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Columns') );
		$Form->checkbox( 'option_showtypes', $UserSettings->get('fm_showtypes'), T_('File type'), T_('Based on file extension') );
		$Form->radio_input( 'option_showdate', $UserSettings->get('fm_showdate'), array(
				array( 'value'=>'no', 'label'=>T_('No') ),
				array( 'value'=>'compact', 'label'=>T_('Compact format') ),
				array( 'value'=>'long', 'label'=>T_('Long format') ) ), T_('Last change') );
		$Form->checkbox( 'option_showfsperms', $UserSettings->get('fm_showfsperms'), T_('File permissions'), T_('Unix file permissions') );
		$Form->checkbox( 'option_permlikelsl', $UserSettings->get('fm_permlikelsl'), '', T_('Check to display file permissions like "rwxr-xr-x" rather than short form') );
		$Form->checkbox( 'option_showfsowner', $UserSettings->get('fm_showfsowner'), T_('File Owner'), T_('Unix file owner') );
		$Form->checkbox( 'option_showfsgroup', $UserSettings->get('fm_showfsgroup'), T_('File Group'), T_('Unix file group') );
		$Form->checkbox( 'option_showcreator', $UserSettings->get('fm_showcreator'), T_('Added by'), T_('File creator') );
		$Form->checkbox( 'option_showdownload', $UserSettings->get('fm_showdownload'), T_('Download Count'), T_('Number of times the file has been downloaded through disp=download') );
	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Options') );
		$Form->checkbox( 'option_showhidden', $UserSettings->get('fm_showhidden'), T_('Hidden files'), T_('Check this to show system hidden files. System hidden files start with a dot (.)') );
		$Form->checkbox( 'option_showevocache', $UserSettings->get('fm_showevocache'), '', T_('Check this to show _evocache folders (not recommended)') );
		$Form->checkbox( 'option_dirsattop', !$UserSettings->get('fm_dirsnotattop'), T_('Folders first'), T_('Check to always display folders before files') );
		$Form->checkbox( 'option_recursivedirsize', $UserSettings->get('fm_recursivedirsize'), T_('Folder sizes'), T_('Check to compute recursive size of folders') );
		$Form->radio_input( 'option_allowfiltering', $UserSettings->get('fm_allowfiltering'), array(
				array( 'value'=>'no', 'label'=>T_('Don\'t show') ),
				array( 'value'=>'simple', 'label'=>T_('Simple') ),
				array( 'value'=>'regexp', 'label'=>T_('With regular expressions') ) ), T_('Filter box') );
	$Form->end_fieldset();

if( $current_User->check_perm( 'options', 'edit', false ) )
{	// TODO: better perm check
  /**
	 * @var FileRoot
	 */
	global $fm_FileRoot;

	if( $fm_FileRoot->type == 'collection' )
	{
		echo '<p class="note">'.T_('See also:').' ';
		echo T_('Blog Settings').' &gt; '.T_('Advanced').' &gt; <a href="?ctrl=coll_settings&tab=advanced&blog='.$fm_FileRoot->in_type_ID.'">'
					.T_('Media directory location').'</a>';
	}
}

$Form->end_form( array( array( 'submit', 'actionArray[update_settings]', T_('Save Changes!'), 'SaveButton') ) );

?>
