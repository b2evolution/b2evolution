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

<form class="fform" name="form" action="b2options.php" method="post">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="tab" value="<?php echo $tab; ?>" />
	
	<fieldset>
		<legend><?php echo T_('Upload options') ?></legend>
		<?php
			form_checkbox( 'upload_enabled', $Settings->get('upload_enabled'), T_('Enable upload'), T_('Check to allow uploading files in general.' ) );
			form_text( 'upload_realpath', $Settings->get('upload_realpath'), 40, T_('Real path'), T_('relative to ' ).$basepath, 255 );
			form_text( 'upload_url', $Settings->get('upload_url'), 40, T_('Url'), T_('relative to ' ).$baseurl, 255 );
			form_text( 'upload_allowedext', $Settings->get('upload_allowedext'), 40, T_('Allowed extensions'), T_('seperated by space' ), 255 );
			form_text( 'upload_maxkb', $Settings->get('upload_maxkb'), 6, T_('Maximal allowed filesize'), T_('KB'), 7 );
	
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