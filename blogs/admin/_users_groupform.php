<?php
/**
 * This file implements the UI view for the user group properties.
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

			$query = "SELECT MAX(grp_ID), MIN(grp_ID) FROM T_groups";
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

	<?php

	$Form = & new Form( 'b2users.php' );
	if( $edited_Group->get('ID') == 0 )
	{
		$Form->begin_form( 'fform', T_('Creating new group') );
	}
	else
	{
		$title = ($current_User->check_perm( 'users', 'edit' ) ? T_('Editing group:') : T_('Viewing group:') )
							.' '.
							( isset($edited_grp_oldname) ? $edited_grp_oldname : $edited_Group->dget('name') )
							.' ('.T_('ID').' '.$edited_Group->ID.')';
		$Form->begin_form( 'fform', $title );

    $Form->hidden( 'action', 'groupupdate' );
    $Form->hidden( 'edited_grp_ID', $edited_Group->ID );
    $Form->hidden( 'edited_grp_oldname', isset($edited_grp_oldname) ? $edited_grp_oldname : $edited_Group->dget('name','formvalue') );
	}

	$Form->fieldset( T_('General') );
	$Form->text( 'edited_grp_name', $edited_Group->name, 50, T_('Name'), '', 50, 'large' );
	$Form->fieldset_end();

 	$Form->fieldset( T_('Permissons for members of this group') );

	$Form->radio( 'edited_grp_perm_blogs', $edited_Group->get('perm_blogs'),
			array(  array( 'user', T_('User permissions') ),
							array( 'viewall', T_('View all') ),
							array( 'editall', T_('Full Access') )
						), T_('Blogs') );
	$Form->radio( 'edited_grp_perm_stats', $edited_Group->get('perm_stats'),
			array(  array( 'none', T_('No Access') ),
							array( 'view', T_('View only') ),
							array( 'edit', T_('Full Access') )
						), T_('Statistics') );
	$Form->radio( 'edited_grp_perm_spamblacklist', $edited_Group->get('perm_spamblacklist'),
			array(  array( 'none', T_('No Access') ),
							array( 'view', T_('View only') ),
							array( 'edit', T_('Full Access') )
						), T_('Antispam') );
	$Form->radio( 'edited_grp_perm_options', $edited_Group->get('perm_options'),
			array(  array( 'none', T_('No Access') ),
							array( 'view', T_('View only') ),
							array( 'edit', T_('Full Access') )
						), T_('Global options') );
	$Form->checkbox( 'edited_grp_perm_templates', $edited_Group->get('perm_templates'), T_('Templates'), T_('Check to allow template editing.') );

	if( $edited_Group->get('ID') != 1 )
	{	// Groups others than #1 can be prevented from editing users
		$Form->radio( 'edited_grp_perm_users', $edited_Group->get('perm_users'),
				array(  array( 'none', T_('No Access') ),
								array( 'view', T_('View only') ),
								array( 'edit', T_('Full Access') )
							), T_('User/Group Management') );
	}
	else
	{
		$Form->info( T_('User/Group Management'), T_('Full Access') );
	}
	$Form->fieldset_end();

	if( $current_User->check_perm( 'users', 'edit' ) )
	{
		$Form->buttons( array( array( '', '', T_('Save !'), 'SaveButton' ),
													 array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
	}

	$Form->fieldset_end();
	$Form->end_form();
	?>


</div>