<?php 
	switch( $next_action )
	{
		case 'create':
			$submit = T_('Create new blog!');
			break;
			
		case 'update':
			$submit = T_('Update blog!');
			break;
	}
?>
<form class="fform" method="post">
	<input type="hidden" name="action" value="<?php echo $next_action; ?>" />
	<input type="hidden" name="blog" value="<?php echo $blog; ?>" />

	<fieldset>
		<div class="label"><label for="blog_name"><?php echo T_('Full Name') ?>:</label></div> 
		<div class="input"><input type="text" name="blog_name" id="blog_name" size="50" maxlength="50" value="<?php echo format_to_output($blog_name, 'formvalue'); ?>" /></div>
	</fieldset>

	<fieldset>
		<div class="label"><label for="blog_shortname"><?php echo T_('Short Name') ?>:</label></div> 
		<div class="input"><input type="text" name="blog_shortname" id="blog_shortname" size="12" maxlength="12" value="<?php echo format_to_output($blog_shortname, 'formvalue'); ?>" /></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_tagline"><?php echo T_('Tagline') ?>:</label></div>
		<div class="input"><input type="text" name="blog_tagline" id="blog_tagline" size="60" maxlength="250" value="<?php echo format_to_output($blog_tagline, 'formvalue') ?>" class="large" />
		<span class="notes"><?php echo T_('This is diplayed under the blog name on the blog template') ?></span></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_description"><?php echo T_('Short Description') ?>:</label></div> 
		<div class="input"><input type="text" name="blog_description" id="blog_description" size="60" maxlength="250" value="<?php echo format_to_output($blog_description, 'formvalue'); ?>" class="large" />
		<span class="notes"><?php echo T_('This is is used in meta tag description and RSS feeds. NO HTML!') ?></span></div>
	</fieldset>

	<fieldset>
		<div class="label"><label for="blog_keywords"><?php echo T_('Keywords') ?>:</label></div> 
		<div class="input"><input type="text" name="blog_keywords" id="blog_keywords" size="60" maxlength="250" value="<?php echo format_to_output($blog_keywords, 'formvalue');  ?>" class="large" />
		<span class="notes"><?php echo T_('This is is used in meta tag keywords. NO HTML!') ?></span></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_longdesc" ><?php echo T_('Long Description') ?>:</label></div> 
		<div class="input"><textarea name="blog_longdesc" id="blog_longdesc" rows="3" cols="50" class="large"><?php echo $blog_longdesc ?></textarea>
		<span class="notes"><?php echo T_('This is displayed on the blog template.') ?></span></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_roll" ><?php echo T_('Blogroll') ?>:</label></div> 
		<div class="input"><textarea name="blog_roll" id="blog_roll" rows="10" cols="50" class="large"><?php echo $blog_roll ?></textarea>
		<span class="notes"><?php echo T_('This is displayed on the blog template.') ?></span></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_lang"><?php echo T_('Main Language') ?>:</label></div> 
		<div class="input"><select name="blog_lang" id="blog_lang"><?php lang_options( $blog_lang )?></select></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_siteurl"><?php echo T_('Site URL') ?>: </label></div> 
		<div class="input"><input type="text" name="blog_siteurl" id="blog_siteurl" size="60" maxlength="120" value="<?php echo format_to_output($blog_siteurl, 'formvalue') ?>" class="large" />
		<span class="notes"><?php echo T_('This is the URL to the directory where the <em>Stub filename</em> and <em>Static filename</em> files live.') ?></span></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_filename"><?php echo T_('Stub Filename') ?>:</label></div> 
		<div class="input"><input type="text" name="blog_filename" id="blog_filename" size="30" maxlength="30" value="<?php echo format_to_output($blog_filename, 'formvalue'); ?>" />
		<span class="notes"><?php echo T_('This is the <strong>file</strong>name of the main file (e-g: blog_b.php) used to display this blog. This is used mainly for static page generation, but setting this incorrectly may also cause navigation to fail.') ?></span></div>
	</fieldset>

	<fieldset>
		<div class="label"><label for="blog_stub"><?php echo T_('Stub Urlname') ?>:</label></div> 
		<div class="input"><input type="text" name="blog_stub" id="blog_stub" size="30" maxlength="30" value="<?php echo format_to_output($blog_stub, 'formvalue'); ?>" />
		<span class="notes"><?php echo T_('This is the <strong>url</strong>name of the main file (e-g: blog_b.php) used to display this blog. A typical setting would be setting this to your Filename without the .php extension, if your webserver supports this. <strong>If you are not sure how to set this, use the same as Stub Filename.</strong> This is used by permalinks, trackback, pingback, etc. Setting this incorrectly may cause these to fail.') ?></span></div>
	</fieldset>

	<fieldset>
		<div class="label"><label for="blog_staticfilename">Static Filename:</label></div> 
		<div class="input"><input type="text" name="blog_staticfilename" id="blog_staticfilename" size="30" maxlength="30" value="<?php echo format_to_output($blog_staticfilename, 'formvalue'); ?>" />
		<span class="notes"><?php echo T_('This is the filename that will be used when you generate a static (.html) version of the blog homepage.') ?></span></div>
	</fieldset>

	<fieldset>
		<div class="input">
			<input type="submit" name="submit" value="<?php echo $submit ?>" class="search">
			<input type="reset" value="Reset" class="search">
		</div>
	</fieldset>

	<div class="clear"></div>
</form>
