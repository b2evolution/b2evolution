<?php
/**
 * blog main params subform
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
?>
<fieldset>
	<legend><?php echo T_('General parameters') ?></legend>
	<?php
		form_text( 'blog_name', $blog_name, 50, T_('Full Name'), T_('Will be displayed on top of the blog.') );
		form_text( 'blog_shortname', $blog_shortname, 12, T_('Short Name'), T_('Will be used in selection menus and throughout the admin interface.') );
		form_select( 'blog_locale', $blog_locale, 'locale_options', T_('Main Locale'), T_('Determines the language of the navigation links on the blog.') );
	?>
</fieldset>

<fieldset>
	<legend><?php echo T_('Access parameters') ?></legend>

	<?php
		form_radio( 'blog_access_type', $blog_access_type,
				array(  array( 'index.php', T_('Through index.php'), 
								$baseurl.$blog_siteurl.'/index.php'.( ($Settings->get('links_extrapath')) ? '/'.$blog_stub : '?blog='.$blog) ),
								array( 'stub', T_('Through stub file'), $baseurl.$blog_siteurl.'/'.$blog_stub ),
							), T_('Preferred access type'), true );
	?>
	
	<fieldset>
		<div class="label"><label for="blog_siteurl"><?php echo T_('Blog Folder URL') ?>: </label></div>
		<div class="input"><code><?php echo $baseurl ?></code><input type="text" name="blog_siteurl" id="blog_siteurl" size="40" maxlength="120" value="<?php echo format_to_output($blog_siteurl, 'formvalue') ?>"/>
		<span class="notes"><?php echo T_('No trailing slash. (If you don\'t know, leave this field empty.)') ?></span></div>
	</fieldset>

	<?php
		form_text( 'blog_stub', $blog_stub, 30, T_('URL blog name'), T_('Used for stub file access') );
	?>
</fieldset>
