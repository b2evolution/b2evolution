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
	if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );
?>
<form action="b2blogs.php" class="fform" method="post">
	<input type="hidden" name="action" value="<?php echo $next_action ?>" />
	<input type="hidden" name="blog" value="<?php echo $blog; ?>" />

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
			if( $Settings->get('default_blog_ID') && ($Settings->get('default_blog_ID') != $blog) )
			{
				$defblog = $BlogCache->get_by_ID($Settings->get('default_blog_ID'));
				$defblog = $defblog->dget('shortname');
			}
			form_radio( 'blog_access_type', $blog_access_type,
					array(
						array( 'default', T_('Default blog on index.php'),
										$blog_siteurl.'/index.php'.( isset($defblog)
											? '  ['. /* TRANS: current default blog */ T_('Current default is:').' '.$defblog.']'
											: '' )
						),
						array( 'index.php', T_('Other blog through index.php'),
										$blog_siteurl.'/index.php'.( $Settings->get('links_extrapath')
											? '/'.$blog_stub
											: '?blog='.$blog)
						),
						array( 'stub', T_('Other blog through stub file (Advanced)'),
										$blog_siteurl.'/'.$blog_stub.' &nbsp; '.T_('You MUST create a stub file for this to work.')
						),
					), T_('Preferred access type'), true );
		
			form_radio( 'blog_siteurl_type', $blog_siteurl_type,
					array(
						array( 'relative',
										T_('relative to baseurl').': <code>'.$baseurl.'</code><input type="text" name="blog_siteurl_relative" size="40" maxlength="120" value="'.( $blog_siteurl_type == 'relative' ? format_to_output($blog_siteurl_relative, 'formvalue') : '' ).'" />'
						),
						array( 'absolute',
										T_('absolute URL').': <input type="text" name="blog_siteurl_absolute" size="40" maxlength="120" value="'.( $blog_siteurl_type == 'absolute' ? format_to_output($blog_siteurl_absolute, 'formvalue') : '' ).'" />'
						)
					),
					T_('Blog Folder URL'), true, T_('No trailing slash. (If you don\'t know, leave this field empty.)') );
					
			form_text( 'blog_stub', $blog_stub, 20, T_('URL blog name / Stub name'), T_('Used in URLs to identify this blog. This should be the stub filename if you use stub file access.'), 30 );
		?>
	</fieldset>
				
	<fieldset>
		<legend><?php echo T_('Default display options') ?></legend>
		<?php
			form_select( 'blog_default_skin', $blog_default_skin, 'skin_options', T_('Default skin') , T_('This is the default skin that will be used to display this blog.') );

			form_checkbox( 'blog_force_skin', 1-$blog_force_skin, T_('Allow skin switching'), T_('Users will be able to select another skin to view the blog (and their prefered skin will be saved in a cookie).') );

			form_checkbox( 'blog_disp_bloglist', $blog_disp_bloglist, T_('Display public blog list'), T_('Check this if you want to display the list of all blogs on your blog page (if your skin supports this).') );

			form_checkbox( 'blog_in_bloglist', $blog_in_bloglist, T_('Include in public blog list'), T_('Check this if you want to this blog to be displayed in the list of all public blogs.') );
		
			form_select_object( 'blog_linkblog', $blog_linkblog, $BlogCache, T_('Default linkblog'), T_('Will be displayed next to this blog (if your skin supports this).'), true );
		?>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('Description') ?></legend>
		<?php
			form_text( 'blog_tagline', $blog_tagline, 50, T_('Tagline'), T_('This is diplayed under the blog name on the blog template.'), 250 );
		?>
		
		<fieldset>
			<div class="label"><label for="blog_longdesc" ><?php echo T_('Long Description') ?>:</label></div>
			<div class="input"><textarea name="blog_longdesc" id="blog_longdesc" rows="8" cols="50" class="large"><?php echo $blog_longdesc ?></textarea>
			<span class="notes"><?php echo T_('This is displayed on the blog template.') ?></span></div>
		</fieldset>

		<?php
			form_text( 'blog_description', $blog_description, 60, T_('Short Description'), T_('This is is used in meta tag description and RSS feeds. NO HTML!'), 250, 'large' );

			form_text( 'blog_keywords', $blog_keywords, 60, T_('Keywords'), T_('This is is used in meta tag keywords. NO HTML!'), 250, 'large' );

		?>

		<fieldset>
			<div class="label"><label for="blog_notes" ><?php echo T_('Notes') ?>:</label></div>
			<div class="input"><textarea name="blog_notes" id="blog_notes" rows="8" cols="50" class="large"><?php echo $blog_notes ?></textarea>
			<span class="notes"><?php echo T_('Additional info.') ?></span></div>
		</fieldset>
	
	</fieldset>
	
	<fieldset class="submit">
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" value="<?php echo ($next_action == 'create') ? T_('Create new blog!') : T_('Update blog!') ?>" class="search">
				<input type="reset" value="<?php echo T_('Reset') ?>" class="search">
			</div>
		</fieldset>
	</fieldset>

	<div class="clear"></div>
</form>
