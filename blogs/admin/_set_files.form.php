<?php
/**
 * This file implements the UI view for the file settings.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );
?>

<form class="fform" name="form" action="fileset.php" method="post">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="tab" value="<?php echo $tab; ?>" />

	<fieldset>
		<legend><?php echo T_('Upload options') ?></legend>
		<?php
			form_checkbox( 'upload_enabled',
											$Settings->get('upload_enabled'),
											T_('Enable upload'),
											T_('Check to allow uploading files in general.' ) );
			form_text( 'upload_allowedext',
									$Settings->get('upload_allowedext'),
									40,
									T_('Allowed file extensions'),
									T_('Seperated by space.' )
									.' '.T_('Leave it empty to disable this check.')
									.' '.sprintf( /* TRANS: %s gets replaced with an example setting */ T_('E.g. &laquo;%s&raquo;'), $Settings->getDefault( 'upload_allowedext' ) ),
									255 );
			form_text( 'upload_allowedmime',
									$Settings->get('upload_allowedmime'),
									40,
									T_('Allowed MIME type'),
									T_('Seperated by space.' )
									.' '.T_('Leave it empty to disable this check.')
									.' '.sprintf( /* TRANS: %s gets replaced with an example setting */ T_('E.g. &laquo;%s&raquo;'), $Settings->getDefault( 'upload_allowedmime' ) ),
									255 );
			form_text( 'upload_maxkb', $Settings->get('upload_maxkb'), 6, T_('Maximal allowed filesize'), T_('KB'), 7 );

			// TODO: check/transform $upload_url
			// TODO: check/transform $upload_realpath

			#form_select_object( 'newusers_grp_ID', $Settings->get('newusers_grp_ID'), $GroupCache, T_('Group for new users'), T_('Groups determine user roles and permissions.') );

			#form_text( 'newusers_level', $Settings->get('newusers_level'), 1, T_('Level for new users'), sprintf( T_('Levels determine hierarchy of users in blogs.' ) ), 1 );
		?>
	</fieldset>

	<?php if( $current_User->check_perm( 'options', 'edit' ) )
	{ ?>
	<fieldset class="submit">
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search" />
				<input type="reset" value="<?php echo T_('Reset') ?>" class="search" />
			</div>
		</fieldset>
	</fieldset>
	<?php } ?>

</form>