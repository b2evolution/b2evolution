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
?>
<form class="fform" method="post">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="tab" value="advanced" />
	<input type="hidden" name="blog" value="<?php echo $blog; ?>" />

	<fieldset>
		<legend><?php echo T_('Static file generation') ?></legend>
		<?php
			form_text( 'blog_filename', $blog_filename, 30, T_('Source Stub File'), T_('((This help note will be updated))') );
	
			form_text( 'blog_staticfilename', $blog_staticfilename, 30, T_('Static Filename'), T_('This is the filename that will be used when you generate a static (.html) version of the blog homepage.') );
		?>
	</fieldset>
	
	<fieldset>
		<legend><?php echo T_('After each new post...') ?></legend>
		<?php
			form_checkbox( 'blog_pingb2evonet', $blog_pingb2evonet, T_('Ping b2evolution.net'), T_("to get listed on the 'recently updated' list. PLEASE NOTE: If you removed the b2evolution button and the link to b2evolution from your blog, don't even bother enabling this. You will *not* be approved and your blog will be blacklisted. Also, the Full Name of your blog must be written in ISO 8859-1 (Latin-1) charset, otherwise we cannot display it on b2evolution.net. You can use HTML entities (e-g &amp;Kappa;) for non latin chars.") );
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
