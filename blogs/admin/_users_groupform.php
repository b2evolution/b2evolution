<?php
/**
 * Displays group properties form
 *
 * Called by {@link b2users.php}
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );
?>
<div class="panelblock">
	<div style="float:right;">
	<?php
		if( $group > 0 )
		{	// Links to next/previous group

			$prevgroupid = 0;
			$nextgroupid = 0;

			$query = "SELECT MAX(grp_ID), MIN(grp_ID) FROM $tablegroups";
			$gminmax = $DB->get_row( $query, ARRAY_A );

			foreach( $GroupCache->cache as $fgroup )
			{ // find prev/next id
				#pre_dump( $fgroup->ID );
				if( $fgroup->ID < $group )
				{
					if( $fgroup->ID > $prevgroupid )
					{
						$prevgroupid = $fgroup->ID;
						$prevgroupname = $fgroup->name;
					}
				}
				elseif( $fgroup->ID > $group )
				{
					if( $fgroup->ID < $nextgroupid || $nextgroupid == 0 )
					{
						$nextgroupid = $fgroup->ID;
						$nextgroupname = $fgroup->name;
					}
				}
			}

			echo ( $group != $gminmax['MIN(grp_ID)'] ) ? '<a title="'.T_('first group').'" href="?group='.$gminmax['MIN(grp_ID)'].'">[&lt;&lt;]</a>' : '[&lt;&lt;]';
			echo ( $prevgroupid ) ? '<a title="'.T_('previous group').' ('.$prevgroupname.')" href="?group='.$prevgroupid.'">[&lt;]</a>' : '[&lt;]';
			echo ( $nextgroupid ) ? '<a title="'.T_('next group').' ('.$nextgroupname.')" href="?group='.$nextgroupid.'">[&gt;]</a>' : '[&gt;]';
			echo ( $group != $gminmax['MAX(grp_ID)'] ) ? '<a title="'.T_('last group').'" href="?group='.$gminmax['MAX(grp_ID)'].'">[&gt;&gt;]</a>' : '[&gt;&gt;]';
		}
		?>

	<a title="<?php echo T_('Close group profile'); ?>" href="b2users.php"><img src="img/close.gif" alt="X" width="14" height="14" title="<?php echo T_('Close group profile'); ?>" class="middle" /></a></div>
	<h2><?php
	if( $edited_Group->get('ID') == 0 )
	{
		echo T_('Creating new group');
	}
	else
	{
		echo ($current_User->check_perm( 'users', 'edit' )) ? T_('Editing group:') : T_('Viewing group:');
		echo ' '.( isset($edited_grp_oldname) ? $edited_grp_oldname : $edited_Group->get('name') ).' ('.T_('ID').' '.$edited_Group->get('ID').')';
	}
	?></h2>

	<form class="fform" method="post" action="b2users.php">
		<input type="hidden" name="action" value="groupupdate" />
		<input type="hidden" name="edited_grp_ID" value="<?php $edited_Group->disp('ID','formvalue') ?>" />

		<fieldset>
			<legend><?php echo T_('General') ?></legend>
			<input type="hidden" name="edited_grp_oldname" value="<?php echo ( isset($edited_grp_oldname) ? $edited_grp_oldname : $edited_Group->get('name') ) ?>" />
			<?php
				form_text( 'edited_grp_name', $edited_Group->get('name'), 50, T_('Name'), '', 50, 'large' );
			?>
		</fieldset>

		<fieldset>
			<legend><?php echo T_('Permissons for members of this group') ?></legend>
			<?php
				form_radio( 'edited_grp_perm_blogs', $edited_Group->get('perm_blogs'),
						array(  array( 'user', T_('User permissions') ),
										array( 'viewall', T_('View all') ),
										array( 'editall', T_('Full Access') )
									), T_('Blogs') );
				form_radio( 'edited_grp_perm_stats', $edited_Group->get('perm_stats'),
						array(  array( 'none', T_('No Access') ),
										array( 'view', T_('View only') ),
										array( 'edit', T_('Full Access') )
									), T_('Statistics') );
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
		{ 
			form_submit();
		} ?>

		<div class="clear"></div>
	</form>

</div>