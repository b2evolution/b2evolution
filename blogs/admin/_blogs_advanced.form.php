<?php
	/**
	 * Advanced blog properties subform
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package admin
	 */
	if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );
?>
<form action="b2blogs.php" class="fform" method="post">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="tab" value="advanced" />
	<input type="hidden" name="blog" value="<?php echo $blog; ?>" />

	<fieldset>
		<legend><?php echo T_('Static file generation') ?></legend>
		<?php
			form_text( 'blog_staticfilename', $blog_staticfilename, 30, T_('Static filename'), T_('This is the .html file that will be created when you generate a static version of the blog homepage.') );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Media library') ?></legend>
		<?php
			form_radio( 'blog_media_radio', $blog_media_radio,
									array(
										array( 'default', T_('Default'), '', sprintf( T_('subdirectory of %s'), $basepath.'/'.$media_subdir ) ),
										array( 'subdir', T_('Subdirectory of media folder'), '<code>'.$basepath.'/'.$media_subdir.'</code><input type="text" name="blog_media_dir_rel" />', '' ),
										array( 'custom', T_('Custom location'), // TODO: better style!! (own div.class for such cases?)
														'<fieldset>'
															.'<div class="label">'.T_('directory').':</div><div class="input"><input type="text" name="blog_media_dir_abs" size="50" /></div>'
															.'<div class="label">'.T_('URL').':</div><div class="input"><input type="text" name="blog_media_url" size="50" /></div></fielset>' )
									), T_('Media dir location'), true
								);

		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('After each new post...') ?></legend>
		<?php
			form_checkbox( 'blog_pingb2evonet', $blog_pingb2evonet, T_('Ping b2evolution.net'), T_('to get listed on the "recently updated" list on b2evolution.net').' [<a href="http://b2evolution.net/about/terms.html">'.T_('Terms of service').'</a>]' );
			form_checkbox( 'blog_pingtechnorati', $blog_pingtechnorati, T_('Ping technorati.com'), T_('to give notice of new post.') );
			form_checkbox( 'blog_pingweblogs', $blog_pingweblogs, T_('Ping weblogs.com'), T_('to give notice of new post.') );
			form_checkbox( 'blog_pingblodotgs', $blog_pingblodotgs, T_('Ping blo.gs'), T_('to give notice of new post.') );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Advanced options') ?></legend>
		<?php
			form_checkbox( 'blog_allowtrackbacks', $blog_allowtrackbacks, T_('Allow trackbacks'), T_("Allow other bloggers to send trackbacks to this blog, letting you know when they refer to it. This will also let you send trackbacks to other blogs.") );
			form_checkbox( 'blog_allowpingbacks', $blog_allowpingbacks, T_('Allow pingbacks'), T_("Allow other bloggers to send pingbacks to this blog, letting you know when they refer to it. This will also let you send pingbacks to other blogs.") );
		?>
	</fieldset>

	<fieldset class="submit">
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" value="<?php echo T_('Update blog!') ?>" class="search">
				<input type="reset" value="<?php echo T_('Reset') ?>" class="search">
			</div>
		</fieldset>
	</fieldset>

	<div class="clear"></div>
</form>
