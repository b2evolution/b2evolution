<?php 
	require_once( dirname(__FILE__).'/_functions_lang.php' );

	switch( $next_action )
	{
		case 'create':
			$submit = 'Create new blog!';
			break;
			
		case 'update':
			$submit = 'Update blog!';
			break;
	}
?>
<form class="fform" method="post">
	<input type="hidden" name="action" value="<?php echo $next_action; ?>" />
	<input type="hidden" name="blog" value="<?php echo $blog; ?>" />

	<fieldset>
		<div class="label"><label for="blog_name">Full Name:</label></div> 
		<div class="input"><input type="text" name="blog_name" id="blog_name" size="50" maxlength="50" value="<?php echo $blog_name ?>" /></div>
	</fieldset>

	<fieldset>
		<div class="label"><label for="blog_shortname">Short Name:</label></div> 
		<div class="input"><input type="text" name="blog_shortname" id="blog_shortname" size="12" maxlength="12" value="<?php echo $blog_shortname ?>" /></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_tagline">Tagline:</label></div>
		<div class="input"><input type="text" name="blog_tagline" id="blog_tagline" size="60" maxlength="250" value="<?php echo $blog_tagline ?>" class="large" />
		<span class="notes">This is diplayed under the blog name on the blog template</span></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_description">Short Desc:</label></div> 
		<div class="input"><input type="text" name="blog_description" id="blog_description" size="60" maxlength="250" value="<?php echo $blog_description ?>" class="large" />
		<span class="notes">This is is used in meta tag description and RSS feeds. NO HTML!)</span></div>
	</fieldset>

	<fieldset>
		<div class="label"><label for="blog_keywords">Keywords:</label></div> 
		<div class="input"><input type="text" name="blog_keywords" id="blog_keywords" size="60" maxlength="250" value="<?php echo $blog_keywords ?>" class="large" />
		<span class="notes">This is is used in meta tag keywords. NO HTML!)</span></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_longdesc" >Long Desc:</label></div> 
		<div class="input"><textarea name="blog_longdesc" id="blog_longdesc" rows="3" cols="50" class="large"><?php echo $blog_longdesc ?></textarea>
		<span class="notes">This is displayed on the blog template.</span></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_roll" >Blogroll:</label></div> 
		<div class="input"><textarea name="blog_roll" id="blog_roll" rows="10" cols="50" class="large"><?php echo $blog_roll ?></textarea>
		<span class="notes">This is displayed on the blog template.</span></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_lang">Main language:</label></div> 
		<div class="input"><select name="blog_lang" id="blog_lang"><?php lang_options( $blog_lang )?></select></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_siteurl">Site URL: </label></div> 
		<div class="input"><input type="text" name="blog_siteurl" id="blog_siteurl" size="60" maxlength="120" value="<?php echo $blog_siteurl ?>" class="large" />
		<span class="notes">This is the URL to the directory where the <em>blogfilename</em> and <em>blogstaticfilename</em> files live.</span></div>
	</fieldset>
	
	<fieldset>
		<div class="label"><label for="blog_filename">Stub Filename:</label></div> 
		<div class="input"><input type="text" name="blog_filename" id="blog_filename" size="30" maxlength="30" value="<?php echo $blog_filename ?>" />
		<span class="notes">This is the <strong>file</strong>name of the main file (e-g: blog_b.php) used to display this blog. This is used mainly for static page generation, but setting this incorrectly may also cause navigation to fail.</span></div>
	</fieldset>

	<fieldset>
		<div class="label"><label for="blog_stub">Stub Urlname:</label></div> 
		<div class="input"><input type="text" name="blog_stub" id="blog_stub" size="30" maxlength="30" value="<?php echo $blog_stub ?>" />
		<span class="notes">This is the <strong>url</strong>name of the main file (e-g: blog_b.php) used to display this blog. A typical setting would be setting this to your Filename without the .php extension, if your webserver supports this. <strong>If you are not sure how to set this, use the same as Stub Filename.</strong> This is used by permalinks, trackback, pingback, etc. Setting this incorrectly may cause these to fail.</span></div>
	</fieldset>

	<fieldset>
		<div class="label"><label for="blog_staticfilename">Static Filename:</label></div> 
		<div class="input"><input type="text" name="blog_staticfilename" id="blog_staticfilename" size="30" maxlength="30" value="<?php echo $blog_staticfilename ?>" />
		<span class="notes">This is the filename that will be used when you generate a static (.html) version of the blog homepage.</span></div>
	</fieldset>

	<fieldset>
		<div class="input">
			<input type="submit" name="submit" value="<?php echo $submit ?>" class="search">
			<input type="reset" value="Reset" class="search">
		</div>
	</fieldset>

	<div class="clear"></div>
</form>
