<?php
/**
 * Displays blog properties form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
?>
<form class="fform" method="post">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="blog" value="<?php echo $blog; ?>" />

	<?php require( dirname(__FILE__) . '/_blogs_main.subform.php' ); ?>
				
	<fieldset>
		<legend><?php echo T_('Default display options') ?></legend>
		<?php
			form_select( 'blog_default_skin', $blog_default_skin, 'skin_options', T_('Default skin') , T_('This is the default skin that will be used to display this blog.') );

			form_checkbox( 'blog_force_skin', 1-$blog_force_skin, T_('Allow skin switching'), T_("Users will be able to select another skin to view the blog (and their prefered skin will be saved in a cookie).") );

			form_checkbox( 'blog_disp_bloglist', $blog_disp_bloglist, T_('Display blog list'), T_("Check this if you want to display the list of all blogs on your blog page (if your skin or template supports this).") );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Description') ?></legend>
		<?php
			form_text( 'blog_tagline', $blog_tagline, 50, T_('Tagline'), T_('This is diplayed under the blog name on the blog template'), 250 );

			form_text( 'blog_description', $blog_description, 60, T_('Short Description'), T_('This is is used in meta tag description and RSS feeds. NO HTML!'), 250, 'large' );

			form_text( 'blog_keywords', $blog_keywords, 60, T_('Keywords'), T_('This is is used in meta tag keywords. NO HTML!'), 250, 'large' );

		?>

		<fieldset>
			<div class="label"><label for="blog_longdesc" ><?php echo T_('Long Description') ?>:</label></div>
			<div class="input"><textarea name="blog_longdesc" id="blog_longdesc" rows="5" cols="50" class="large"><?php echo $blog_longdesc ?></textarea>
			<span class="notes"><?php echo T_('This is displayed on the blog template.') ?></span></div>
		</fieldset>

		<fieldset>
			<div class="label"><label for="blog_roll" ><?php echo T_('Blogroll text') ?>:</label></div>
			<div class="input"><textarea name="blog_roll" id="blog_roll" rows="5" cols="50" class="large"><?php echo $blog_roll ?></textarea>
			<span class="notes"><?php echo T_('This is displayed on the blog template.') ?></span></div>
		</fieldset>
	</fieldset>

	<fieldset>
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" value="<?php echo T_('Update blog!') ?>" class="search">
				<input type="reset" value="<?php echo T_('Reset') ?>" class="search">
			</div>
		</fieldset>
	</fieldset>

	<div class="clear"></div>
</form>
