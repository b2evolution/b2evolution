<?php
$title = _('Blogs');
$standalone=1;
require_once (dirname(__FILE__).'/b2header.php'); // this will actually load blog params for req blog
set_param( 'action' );

switch($action) 
{
	case 'new':
		include(dirname(__FILE__).'/'.$b2inc.'/_menutop.php');
		include(dirname(__FILE__).'/'.$b2inc.'/_menutop_end.php');
		if ($user_level < 9) 
		{
			die( _('You have no right to edit the blogs.') );
		}
		echo "<div class=\"panelblock\">\n";
		echo '<h2>', _('New blog'), ":</h2>\n";
		// EDIT FORM:
		set_param( 'blog_name', 'string', 'new weblog' );
		set_param( 'blog_shortname', 'string', 'new blog' );
		set_param( 'blog_tagline', 'string', '' );
		set_param( 'blog_description', 'string', '' );
		set_param( 'blog_longdesc', 'string', '' );
		set_param( 'blog_lang', 'string', $default_language );
		set_param( 'blog_siteurl', 'string', $baseurl );
		set_param( 'blog_filename', 'string', 'new_file.php' );
		set_param( 'blog_staticfilename', 'string', '' );
		set_param( 'blog_stub', 'string', 'new_file.php' );
		set_param( 'blog_roll', 'string', '' );
		set_param( 'blog_keywords', 'string', '' );
		set_param( 'blog_UID', 'string', '' );
		$next_action = 'create';
		include($b2inc."/_blogs_form.php");
		echo '</div>';
		break;
	
		
		
	case 'create':
		include(dirname(__FILE__).'/'.$b2inc.'/_menutop.php');
		include(dirname(__FILE__).'/'.$b2inc.'/_menutop_end.php');
		if ($user_level < 9) {
			die( _('You have no right to edit the blogs.') );
		}
	
		set_param( 'blog_name', 'string', true );
		set_param( 'blog_shortname', 'string', true );
		set_param( 'blog_tagline', 'string', '' );
		set_param( 'blog_description', 'string', '' );
		set_param( 'blog_longdesc', 'string', '' );
		set_param( 'blog_lang', 'string', 'en' );
		set_param( 'blog_siteurl', 'string', true );
		set_param( 'blog_filename', 'string', true );
		set_param( 'blog_staticfilename', 'string', '' );
		set_param( 'blog_stub', 'string', '' );
		set_param( 'blog_roll', 'string', '' );
		set_param( 'blog_keywords', 'string', '' );
		set_param( 'blog_UID', 'string', '' );
	
		echo "<p>Creating blog...</p>";
		
		$blog_ID = blog_create( $blog_name, $blog_shortname, $blog_siteurl, $blog_filename, 
									$blog_stub,  $blog_staticfilename, 
									$blog_tagline, $blog_description, $blog_longdesc, $blog_lang, $blog_roll, 
									$blog_keywords, $blog_UID ) or mysql_oops( $query );
		
	
		// Quick hack to create a stub file:
		if( $blog_siteurl == $baseurl )
		{
			echo '<p>', _('Trying to create stub file'), '</p>';
			// Determine the edit folder:
			$current_folder = str_replace( '\\', '/', dirname(__FILE__) );
			$last_pos = 0;
			while( $pos = strpos( $current_folder, $backoffice_subdir, $last_pos ) )
			{	// make sure we use the last occurrence
				$edit_folder = substr( $current_folder, 0, $pos-1 );
				$last_pos = $pos+1;
			}
	
			$stub_contents = file( $edit_folder.'/stub.model' );
			echo '<p>', _('Loading'), ': ', $stub_contents, '</p>';
			
			if( empty( $stub_contents ) )
			{
					echo '<p class="error">', _('Could not load stub model.'), '</p>';
			}	
			else
			{
				$new_stub_file = $edit_folder.'/'.$blog_filename;
				echo '<p>', _('Creating'), ': ', $new_stub_file, '</p>';
				$f = fopen( $new_stub_file , "w" );
				if( $f == false )
				{
					echo '<p class="error">Cannot create!</p>';
				}
				else
				{
					$found = false;
					foreach( $stub_contents as $idx => $stub_line )
					{
						$stub_line = ereg_replace( '\$blog *= *.+;', '$blog = '.$blog_ID.';', $stub_line );
						fwrite( $f, $stub_line);
					}
					fclose($f);
				}
				
				if( isset($default_stub_mod) ) 
				{
					printf( _('<p>Changing mod to %o</p>'), $default_stub_mod );
					if( ! chmod( $new_stub_file, $default_stub_mod ) )
					{
						echo '<p class="error">', _('Warning'), ': ', _('chmod failed!'), '</p>';
					}
				}
				
				if( isset($default_stub_owner) ) 
				{
					printf( _('<p>Changing owner to %s</p>'), $default_stub_owner );
					if( ! chmod( $new_stub_file, $default_stub_owner ) )
					{
						echo '<p class="error">', _('Warning'), ': ', _('chown failed!'), '</p>';
					}
				}
			}
		}
		
		?>
		<p><strong><?php printf( _('You should <a href="%s">create categories</a> for this blog now!'), 'b2categories.php?action=newcat&blog_ID='.$blog_ID ); ?></strong></p>
		<?php
		break;
	
	
	case 'edit':
		include(dirname(__FILE__).'/'.$b2inc.'/_menutop.php');
		include(dirname(__FILE__).'/'.$b2inc.'/_menutop_end.php');
		if ($user_level < 9) {
			die( _('You have no right to edit the blogs.') );
		}
		echo "<div class=\"panelblock\">\n";
		echo '<h2>', _('Blog params for:'), ' ', get_bloginfo('name'), "</h2>\n";
		// EDIT FORM:
		$blog_name = get_bloginfo('name');
		$blog_shortname = get_bloginfo('shortname');
		$blog_tagline = get_bloginfo('tagline');
		$blog_description = get_bloginfo('description');
		$blog_longdesc = get_bloginfo('longdesc');
		$blog_lang = get_bloginfo('lang');
		$blog_siteurl = get_bloginfo('siteurl');
		$blog_filename = get_bloginfo('filename');
		$blog_staticfilename = get_bloginfo('staticfilename');
		$blog_stub = get_bloginfo('stub');
		$blog_roll = get_bloginfo('blogroll');
		$blog_keywords = get_bloginfo('keywords');
		$next_action = 'update';
		include($b2inc."/_blogs_form.php");
		echo '</div>';
		break;
		
		
		
		
	case 'update':
		if ($user_level < 9) {
			die( _('You have no right to edit the blogs.') );
		}
	
		set_param( 'blog', 'integer', true );
		set_param( 'blog_name', 'string', true );
		set_param( 'blog_shortname', 'string', true );
		set_param( 'blog_tagline', 'string', '' );
		set_param( 'blog_description', 'string', '' );
		set_param( 'blog_longdesc', 'string', '' );
		set_param( 'blog_lang', 'string', 'en' );
		set_param( 'blog_siteurl', 'string', true );
		set_param( 'blog_filename', 'string', true );
		set_param( 'blog_staticfilename', 'string', '' );
		set_param( 'blog_stub', 'string', '' );
		set_param( 'blog_roll', 'string', '' );
		set_param( 'blog_keywords', 'string', '' );
		set_param( 'blog_UID', 'string', '' );
	
		blog_update( $blog, $blog_name, $blog_shortname, $blog_siteurl, $blog_filename, $blog_stub,
									 $blog_staticfilename, 
									$blog_tagline, $blog_description, $blog_longdesc, $blog_lang, $blog_roll, 
									$blog_keywords, $blog_UID ) or mysql_oops( $query );
		
		header( 'Location: b2blogs.php' );
		exit();
		break;
	
	
	
	
	case 'GenStatic':
		include(dirname(__FILE__).'/'.$b2inc.'/_menutop.php');
		include(dirname(__FILE__).'/'.$b2inc.'/_menutop_end.php');
	?>
		<div class="panelinfo">
			<p><?php echo _('Blog'), ': ', get_bloginfo('name') ?></p>
	<?php
		if ($user_level < 2) 
		{
			die( _('You have no right to generate static pages.') );
		}
	
		$staticfilename = get_bloginfo('staticfilename');
		if( empty( $staticfilename ) )
		{
			echo '<p>', _('You haven\'t set a static filename for this blog!'), "</p>\n</div>\n";
			break;
		}
	
		// Determine the edit folder:
		$current_folder = str_replace( '\\', '/', dirname(__FILE__) );
		$last_pos = 0;
		while( $pos = strpos( $current_folder, $backoffice_subdir, $last_pos ) )
		{	// make sure we use the last occurrence
			$edit_folder = substr( $current_folder, 0, $pos-1 );
			$last_pos = $pos+1;
		}
	
		$filename = $edit_folder.'/'.get_bloginfo('filename');
		$staticfilename = $edit_folder.'/'.$staticfilename; 
		
		printf( _('Generating page from <strong>%s</strong> to <strong>%s</strong>...'), $filename, $staticfilename );
		echo "<br />\n";
		flush();
		
		ob_start();
		require $filename;	
		$page = ob_get_contents();
		ob_end_clean();
	
		echo _('Writing to file...'), '<br />', "\n";
	
		$fp = fopen ( $staticfilename, "w");  
		fwrite($fp, $page);
		fclose($fp);
	
		echo _('Done.'), '<br />', "\n";
	?>
		</div>
	<?php 
		
		break;
	
	
	default:
		include(dirname(__FILE__).'/'.$b2inc.'/_menutop.php');
		include(dirname(__FILE__).'/'.$b2inc.'/_menutop_end.php');
		if ($user_level < 9) {
			die( _('You have no right to edit the blogs.') );
		}
		
}

// List the blogs:
include($b2inc."/_blogs_list.php"); 
include($b2inc."/_footer.php"); 
?>