<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */
require( dirname(__FILE__).'/_header.php');
$title = T_('Options');

param( 'action', 'string' );

switch($action) 
{
	case 'update':
		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );
	
		param( 'newposts_per_page', 'integer', true );
		param( 'newwhat_to_show', 'string', true );
		param( 'newarchive_mode', 'string', true );
		param( 'newtime_difference', 'integer', true );
		param( 'newautobr', 'integer', true );
		param( 'pref_newusers_canregister', 'integer', 0 );
		param( 'pref_newusers_grp_ID', 'integer', true );
		param( 'pref_newusers_level', 'integer', true );
		
		$query = "UPDATE $tablesettings 
							SET posts_per_page=$newposts_per_page, 
									what_to_show='$newwhat_to_show', 
									archive_mode='$newarchive_mode', 
									time_difference=$newtime_difference, 
									AutoBR=$newautobr, 
									pref_newusers_canregister = $pref_newusers_canregister,
									pref_newusers_level = $pref_newusers_level,
									pref_newusers_grp_ID = $pref_newusers_grp_ID";
		mysql_query($query) or mysql_oops( $query );
		$querycount++;
		
		header ("Location: b2options.php");
	
	break;
	
	default:
		require(dirname(__FILE__).'/_menutop.php');
		require(dirname(__FILE__).'/_menutop_end.php');
	
		// Check permission:
		$current_User->check_perm( 'options', 'view', true );
		?>
		
	<div class="panelblock">

		<form class="fform" name="form" action="b2options.php" method="post">
		<input type="hidden" name="action" value="update" />
	
		<fieldset>
			<legend><?php echo T_('Regional settings') ?></legend>
		
			<?php form_text( 'newtime_difference', $time_difference, 2, T_('Time difference'), sprintf( T_('If you\'re not on the timezone of your server. Current server time is: %s.'), date_i18n( locale_timefmt(), $servertimenow ) ), 2 );?>

			<?php // form_select( 'edited_user_grp_ID', $edited_User->Group->get('ID'), 'groups_options', T_('User group') );?>
	
		</fieldset>

		<fieldset>
			<legend><?php echo T_('Default user rights') ?></legend>
			<?php
			
			 form_checkbox( 'pref_newusers_canregister', get_settings('pref_newusers_canregister'), T_('New users can register'), sprintf( T_('Check to allow new users to register themselves.' ) ) );
			 
			 form_select( 'pref_newusers_grp_ID', get_settings('pref_newusers_grp_ID'), 'groups_options', T_('Group for new users'), T_('Groups determine user roles and permissions.') );
			 
			 form_text( 'pref_newusers_level', get_settings('pref_newusers_level'), 1, T_('Level for new users'), sprintf( T_('Levels determine hierarchy of users in blogs.' ) ), 1 );
			?>
		</fieldset>
	
		
		<fieldset>
			<legend><?php echo T_('Post options') ?></legend>
			<?php
				form_radio( 'newwhat_to_show', $what_to_show,
						array(  array( 'days', T_('days') ),
										array( 'posts', T_('posts') ),
										array( 'paged', T_('posts paged') )
									), T_('Display mode') );

				form_text( 'newposts_per_page', $posts_per_page, 4, T_('Posts/Days per page'), '', 4 );

				form_radio( 'newarchive_mode', $archive_mode,
						array(  array( 'daily', T_('daily') ),
										array( 'weekly', T_('weekly') ),
										array( 'monthly', T_('monthly') ),
										array( 'postbypost', T_('post by post') )
									), T_('Archive mode') );

	 			form_checkbox( 'newautobr', $autobr, T_('Auto-BR'), sprintf( T_('Converts line-breaks into &lt;br /&gt; tags.' ) ) );
			?>
		</fieldset>
	
		<?php if( $current_User->check_perm( 'options', 'edit' ) ) 
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
	
		</form>
	</div>
	
	<?php
	
	break;
}

require( dirname(__FILE__).'/_footer.php' ); 

?>