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
			?>
		</fieldset>
	
		<fieldset>
			<fieldset>
				<div class="input">
					<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search">
					<input type="reset" value="<?php echo T_('Reset') ?>" class="search">
				</div>
			</fieldset>
		</fieldset>
	
		<div class="clear"></div>
	</form>

</div>
