<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */
?>
<div class="panelblock">

	<h2><?php echo T_('Editing group:'), ' ', $edited_Group->disp('name') ?></h2>

	<form class="fform" method="post" action="b2users.php">
		<input type="hidden" name="action" value="groupupdate" />
		<input type="hidden" name="edited_grp_ID" value="<?php $edited_Group->disp('ID','formvalue') ?>" />
	
		<fieldset>
			<legend><?php echo T_('General') ?></legend>
			<?php 
				form_text( 'edited_grp_name', $edited_Group->get('name'), 50, T_('Name'), '', 50, 'large' );
			?>
		</fieldset>

		<fieldset>
			<legend><?php echo T_('Permissons for members of this group') ?></legend>
			<?php 
				form_radio( 'edited_grp_perm_stats', $edited_Group->get('perm_stats'), 
						array(  array( 'none', T_('No Access') ),
										array( 'view', T_('View only') ),
										array( 'edit', T_('Full Access') )
									), T_('Stats') );
				form_radio( 'edited_grp_perm_spamblacklist', $edited_Group->get('perm_spamblacklist'), 
						array(  array( 'none', T_('No Access') ),
										array( 'view', T_('View only') ),
										array( 'edit', T_('Full Access') )
									), T_('Antispam') );
				form_radio( 'edited_grp_perm_options', $edited_Group->get('perm_options'),
						array(  array( 'none', T_('No Access') ),
										array( 'view', T_('View only') ),
										array( 'edit', T_('Full Access') )
									), T_('Global options') );
				form_checkbox( 'edited_grp_perm_templates', $edited_Group->get('perm_templates'), T_('Templates'), T_('Check to allow template editing.') );

				if( $edited_Group->get('ID') != 1 )
				{	// Groups others than #1 can be prevented from editing users
					form_radio( 'edited_grp_perm_users', $edited_Group->get('perm_users'),
							array(  array( 'none', T_('No Access') ),
											array( 'view', T_('View only') ),
											array( 'edit', T_('Full Access') )
										), T_('User/Group Management') );
				}
				else
				{
					form_info( T_('User/Group Management'), T_('Full Access') );
				}
			?>
		</fieldset>
	
		<?php 
		if( $current_User->check_perm( 'users', 'edit' ) )
		{ ?>
		<fieldset>
			<fieldset>
				<div class="input">
					<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search">
					<input type="reset" value="<?php echo T_('Reset') ?>" class="search">
				</div>
			</fieldset>
		</fieldset>
		<?php } ?>	
		
		<div class="clear"></div>
	</form>

</div>
