<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );
?>
<form action="blogs.php" class="fform" method="post">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="tab" value="advanced" />
	<input type="hidden" name="blog" value="<?php echo $edited_Blog->ID ?>" />

	<fieldset>
		<legend><?php echo T_('Static file generation') ?></legend>
		<?php
			form_text( 'blog_staticfilename', $edited_Blog->get( 'staticfilename' ), 30, T_('Static filename'), T_('This is the .html file that will be created when you generate a static version of the blog homepage.') );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Media library') ?></legend>
		<?php
			form_radio( 'blog_media_location', $edited_Blog->get( 'media_location' ),
									array(
										array( 'default',
														T_('Default'),
														sprintf( T_('subdirectory &quot;%s&quot; (URL blog name) of %s'), $edited_Blog->urlname, $basepath.$media_subdir ) ),
										array( 'subdir',
														T_('Subdirectory of media folder'),
														'',
														' <span class="nobr"><code>'.$basepath.$media_subdir.'</code><input type="text" name="blog_media_subdir" size="20" maxlength="255" value="'.$edited_Blog->dget( 'media_subdir', 'formvalue' ).'" /></span>', '' ),
										array( 'custom',
														T_('Custom location'),
														'',
														'<fieldset>'
															.'<div class="label">'.T_('directory').':</div><div class="input"><input type="text" name="blog_media_fullpath" size="50" maxlength="255" value="'.$edited_Blog->dget( 'media_fullpath', 'formvalue' ).'" /></div>'
															.'<div class="label">'.T_('URL').':</div><div class="input"><input type="text" name="blog_media_url" size="50" maxlength="255" value="'.$edited_Blog->dget( 'media_url', 'formvalue' ).'" /></div></fieldset>' )
									), T_('Media dir location'), true
								);

		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('After each new post...') ?></legend>
		<?php
			form_checkbox( 'blog_pingb2evonet', $edited_Blog->get( 'pingb2evonet' ), T_('Ping b2evolution.net'), T_('to get listed on the "recently updated" list on b2evolution.net').' [<a href="http://b2evolution.net/about/terms.html">'.T_('Terms of service').'</a>]' );
			form_checkbox( 'blog_pingtechnorati', $edited_Blog->get( 'pingtechnorati' ), T_('Ping technorati.com'), T_('to give notice of new post.') );
			form_checkbox( 'blog_pingweblogs', $edited_Blog->get( 'pingweblogs' ), T_('Ping weblogs.com'), T_('to give notice of new post.') );
			form_checkbox( 'blog_pingblodotgs', $edited_Blog->get( 'pingblodotgs' ), T_('Ping blo.gs'), T_('to give notice of new post.') );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Advanced options') ?></legend>
		<?php
			form_radio( 'blog_allowcomments', $blog_allowcomments,
					array(  array( 'always', T_('Always on all posts'), T_('Always allow comments on every posts') ), 
									array( 'post_by_post', T_('Can be disabled on a per post basis'),  T_('Comments can be disabled on each post separatly') ),
									array( 'never', T_('No comments are allowed in this blog'), T_('Never allow any comments in this blog') ),
						), T_('Allow comments'), true );
								
			form_checkbox( 'blog_allowtrackbacks', $edited_Blog->get( 'allowtrackbacks' ), T_('Allow trackbacks'), T_("Allow other bloggers to send trackbacks to this blog, letting you know when they refer to it. This will also let you send trackbacks to other blogs.") );
			form_checkbox( 'blog_allowpingbacks', $edited_Blog->get( 'allowpingbacks' ), T_('Allow pingbacks'), T_("Allow other bloggers to send pingbacks to this blog, letting you know when they refer to it. This will also let you send pingbacks to other blogs.") );
		?>
	</fieldset>

	<?php form_submit(); ?>
</form>