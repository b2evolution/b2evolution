<?php
/**
 * This file implements the general settings form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
?>

<form class="fform" name="form" action="b2options.php" method="post">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="tab" value="<?php echo $tab; ?>" />
	
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
			form_radio( 'newwhat_to_show', get_settings('what_to_show'),
					array(  array( 'days', T_('days') ),
									array( 'posts', T_('posts') ),
									array( 'paged', T_('posts paged') )
								), T_('Display mode') );
	
			form_text( 'newposts_per_page', get_settings('posts_per_page'), 4, T_('Posts/Days per page'), '', 4 );
	
			form_radio( 'newarchive_mode', get_settings('archive_mode'),
					array(  array( 'monthly', T_('monthly') ),
									array( 'weekly', T_('weekly') ),
									array( 'daily', T_('daily') ),
									array( 'postbypost', T_('post by post') )
								), T_('Archive mode') );
	
			form_checkbox( 'newautobr', get_settings('AutoBR'), T_('Auto-BR'), T_('This option is deprecated, you should avoid using it.') );
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
			<div <?php echo ( $tab == 'regional' ) ? 'style="text-align:center"' : 'class="input"'?>>
				<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search">
				<input type="reset" value="<?php echo T_('Reset') ?>" class="search">
			</div>
		</fieldset>
	</fieldset>
	<?php } ?>

</form>
