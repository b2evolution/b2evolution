<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */
require( dirname(__FILE__). '/_header.php');
$title = T_('Options');

/* TODO: locales (default / enabling)
	locales admin in the backoffice should READ **everything** from the
	array and WRITE to the array+DB. That way new locales introduced in new
	releases will be transparently added to the enabled locales.
*/

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
		param( 'pref_links_extrapath', 'integer', 0 );
		param( 'pref_permalink_type', 'string', true );

		$query = "UPDATE $tablesettings
							SET posts_per_page = $newposts_per_page,
									what_to_show = '".$DB->escape($newwhat_to_show)."',
									archive_mode = '".$DB->escape($newarchive_mode)."',
									time_difference = $newtime_difference,
									AutoBR = $newautobr,
									pref_newusers_canregister = $pref_newusers_canregister,
									pref_newusers_level = $pref_newusers_level,
									pref_newusers_grp_ID = $pref_newusers_grp_ID,
									pref_links_extrapath = $pref_links_extrapath,
									pref_permalink_type = '".$DB->escape($pref_permalink_type)."'";
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

			<?php form_text( 'newtime_difference', $time_difference, 2, T_('Time difference'), sprintf( '['. T_('in hours'). '] '. T_('If you\'re not on the timezone of your server. Current server time is: %s.'), date_i18n( locale_timefmt(), $servertimenow ) ), 2 );?>

		</fieldset>

		<fieldset>
			<legend><?php echo T_('Default user rights') ?></legend>
			<?php

			form_checkbox( 'pref_newusers_canregister', get_settings('pref_newusers_canregister'), T_('New users can register'), sprintf( T_('Check to allow new users to register themselves.' ) ) );

			form_select_object( 'pref_newusers_grp_ID', get_settings('pref_newusers_grp_ID'), $GroupCache, T_('Group for new users'), T_('Groups determine user roles and permissions.') );

			form_text( 'pref_newusers_level', get_settings('pref_newusers_level'), 1, T_('Level for new users'), sprintf( T_('Levels determine hierarchy of users in blogs.' ) ), 1 );
			?>
		</fieldset>


		<fieldset>
			<legend><?php echo T_('Display options') ?></legend>
			<?php
				form_radio( 'newwhat_to_show', $what_to_show,
						array(  array( 'days', T_('days') ),
										array( 'posts', T_('posts') ),
										array( 'paged', T_('posts paged') )
									), T_('Display mode') );

				form_text( 'newposts_per_page', $posts_per_page, 4, T_('Posts/Days per page'), '', 4 );

				form_radio( 'newarchive_mode', $archive_mode,
						array(  array( 'monthly', T_('monthly') ),
										array( 'weekly', T_('weekly') ),
										array( 'daily', T_('daily') ),
										array( 'postbypost', T_('post by post') )
									), T_('Archive mode') );

				form_checkbox( 'newautobr', $autobr, T_('Auto-BR'), sprintf( T_('Converts line-breaks into &lt;br /&gt; tags.' ) ) );
			?>
		</fieldset>

		<fieldset>
			<legend><?php echo T_('Link options') ?></legend>
			<?php
				form_checkbox( 'pref_links_extrapath', get_settings('pref_links_extrapath'), T_('Use extra-path info'), sprintf( T_('Recommended if your webserver supports it. Links will look like stub/2003/05/20/post_title instead of stub?title=post_title&c=1&tb=1&pb=1&more=1' ) ) );

				form_radio( 'pref_permalink_type', get_settings('pref_permalink_type'),
						array(  array( 'urltitle', T_('Post called up by its URL title (Recommended)') ),
										array( 'pid', T_('Post called up by its ID') ),
										array( 'archive#id', T_('Post on archive page, located by its ID') ),
										array( 'archive#title', T_('Post on archive page, located by its title (for Cafelog compatibility)') )
									), T_('Permalink type'), true );
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

require( dirname(__FILE__). '/_footer.php' );

?>