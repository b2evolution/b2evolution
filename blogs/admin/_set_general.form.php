<?php
/**
 * Displays the general settings form
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
		<legend><?php echo T_('Default user rights') ?></legend>
		<?php
			form_checkbox( 'newusers_canregister', $Settings->get('newusers_canregister'), T_('New users can register'), T_('Check to allow new users to register themselves.' ) );

			form_select_object( 'newusers_grp_ID', $Settings->get('newusers_grp_ID'), $GroupCache, T_('Group for new users'), T_('Groups determine user roles and permissions.') );

			form_text( 'newusers_level', $Settings->get('newusers_level'), 1, T_('Level for new users'), T_('Levels determine hierarchy of users in blogs.' ), 1 );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Display options') ?></legend>
		<?php
			form_select_object( 'default_blog_ID', $Settings->get('default_blog_ID'), $BlogCache, T_('Default blog to display'), T_('This blog will be displayed on index.php .'), true );

			form_radio( 'what_to_show', $Settings->get('what_to_show'),
					array(  array( 'days', T_('days') ),
									array( 'posts', T_('posts') ),
									array( 'paged', T_('posts paged') )
								), T_('Display mode') );

			form_text( 'posts_per_page', $Settings->get('posts_per_page'), 4, T_('Posts/Days per page'), '', 4 );

			form_radio( 'archive_mode', $Settings->get('archive_mode'),
					array(  array( 'monthly', T_('monthly') ),
									array( 'weekly', T_('weekly') ),
									array( 'daily', T_('daily') ),
									array( 'postbypost', T_('post by post') )
								), T_('Archive mode') );

			form_checkbox( 'AutoBR', $Settings->get('AutoBR'), T_('Auto-BR'), T_('This option is deprecated, you should avoid using it.') );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Link options') ?></legend>
		<?php
			form_checkbox( 'links_extrapath', $Settings->get('links_extrapath'), T_('Use extra-path info'), sprintf( T_('Recommended if your webserver supports it. Links will look like \'stub/2003/05/20/post_title\' instead of \'stub?title=post_title&c=1&tb=1&pb=1&more=1\'.' ) ) );

			form_radio( 'permalink_type', $Settings->get('permalink_type'),
					array(  array( 'urltitle', T_('Post called up by its URL title (Recommended)'), T_('Fallback to ID when no URL title available.') ),
									array( 'pid', T_('Post called up by its ID') ),
									array( 'archive#id', T_('Post on archive page, located by its ID') ),
									array( 'archive#title', T_('Post on archive page, located by its title (for Cafelog compatibility)') )
								), T_('Permalink type'), true );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Security options') ?></legend>
		<?php
			form_text( 'user_minpwdlen', (int)$Settings->get('user_minpwdlen'), 1, T_('Minimum password length'), T_('for users.'), 2 );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Miscellaneous options') ?></legend>
		<?php
			// TODO: better name?!
			form_text( 'reloadpage_timeout', (int)$Settings->get('reloadpage_timeout'), 2, T_('Reload-page timeout'), T_('time in seconds before a request to the same URI from the same IP and useragent is considered as new hit.'), 5 );
		?>
	</fieldset>

	<?php if( $current_User->check_perm( 'options', 'edit' ) )
	{ 
		form_submit();
	} 
	?>

</form>
