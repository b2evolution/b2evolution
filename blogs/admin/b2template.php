<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */
require_once(dirname(__FILE__). '/_header.php');
$title = T_('Custom skin template editing');

// Check permission:
$current_User->check_perm( 'templates', '', true );

param( 'action', 'string' );
param( 'error', 'string' );
param( 'file', 'string' );
param( 'a', 'string' );


switch($action)
{

case "update":
	// Determine the edit folder:
	$edit_folder = get_path('skins'). '/custom';

	param( 'newcontent', 'html' );
	$f = fopen( $edit_folder. '/'. $file, "w+" );
	fwrite($f,$newcontent);
	fclose($f);

	header("Location: b2template.php?file=$file&a=te");
	exit();

	break;

default:
	require(dirname(__FILE__). '/_menutop.php');
	require(dirname(__FILE__). '/_menutop_end.php');

	// Determine the edit folder:
	$edit_folder = get_path('skins'). '/custom';

	$file = trim($file);
	if( !empty($file))
	{
		echo '<div class="panelblock">';

		echo T_('Listing:'), ' <strong>', $edit_folder, '/', $file, '</strong>';

		if( ereg( '([^-A-Za-z0-9._]|\.\.)', $file ) )
		{
			echo '<p>', T_('Invalid filename!'), '</p>';
		}
		elseif( !is_file($edit_folder. '/'. $file) )
		{
				echo '<p>', T_('Oops, no such file !'), '</p>';
		}
		else
		{

			$f = fopen( $edit_folder. '/'. $file, 'r');
			$content = fread($f,filesize($edit_folder. '/'. $file));
			//	$content = template_simplify($content);
			$content = htmlspecialchars($content);
			//	$content = str_replace("</textarea","&lt;/textarea",$content);

			if ($a == 'te')	echo '<em> [ ', T_('File edited!'), ' ]</em>';

			if (!$error) {
			?>
			<p><?php echo T_('Be careful what you do, editing this file could break your template! Do not edit what\'s between <code>&lt;?php</code> and <code>?&gt;</code> if you don\'t know what you\'re doing!') ?></p>
			<form name="template" action="b2template.php" method="post">
				<textarea cols="80" rows="20" style="width:100%" name="newcontent" tabindex="1"><?php echo $content ?></textarea>
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="file" value="<?php echo $file ?>" />
				<br />
				<?php
				if( is_writable($edit_folder. '/'. $file) )
				{
					echo '<input type="submit" name="submit" class="search" value="', T_('update template !'), '" tabindex="2" />';
				}
				else
				{
					echo '<input type="button" name="oops" class="search" value="', T_('(you cannot update that file/template: must make it writable, e.g. CHMOD 766)'), '" tabindex="2" />';
				}
				?>
			</form>
			<?php
			}
		}
		echo "</div>\n";
	}
?>

	<div class="panelblock">
	<p><?php echo T_('This screen allows you to edit the <strong>custom skin</strong> (located under /skins/custom). ') ?></p>
	<p><?php echo T_('You can edit any of the following files (provided it\'s writable by the server, e.g. CHMOD 766)') ?>:</p>
	<ul>
<?php
	// Determine the edit folder:
	if( empty($edit_folder) ) $edit_folder = get_path('skins'). '/custom';
	//lists all files in edit directory
	$this_dir = dir( $edit_folder );
	while ($this_file = $this_dir->read())
	{
		if( is_file($edit_folder. '/'. $this_file) )
		{
			?>
			<li><a href="b2template.php?file=<?php echo $this_file; ?>"><?php echo $this_file; ?></a>
			<?php
			switch( $this_file )
			{
				case '_archives.php':
					echo '- ', T_('This is the template that displays the links to the archives for a blog');
					break;
				case '_categories.php':
					echo '- ', T_('This is the template that displays the (recursive) list of (sub)categories');
					break;
				case '_feedback.php':
					echo '- ', T_('This is the template that displays the feedback for a post');
					break;
				case '_lastcomments.php':
					echo '- ', T_('This is the template that displays the last comments for a blog');
					break;
				case '_main.php':
					echo '- ', T_('This is the main template. It displays the blog.');
					break;
				case '_stats.php':
					echo '- ', T_('This is the template that displays stats for a blog');
					break;
				case 'comment_popup.php':
					echo '- ', T_('This is the page displayed in the comment popup');
					break;
				case 'pingback_popup.php':
					echo '- ', T_('This is the page displayed in the pingback popup');
					break;
				case 'trackback_popup.php':
					echo '- ', T_('This is the page displayed in the trackback popup');
					break;
			}
		?>
			</li>
		<?php }
	}
?>
</ul>

<p>	<?php echo T_('Note: of course, you can also edit the files/templates in your text editor and upload them. This online editor is only meant to be used when you don\'t have access to a text editor...') ?>
</p>

	</div>
	<?php
break;
}

/* </Template> */
require( dirname(__FILE__). '/_footer.php' );
 ?>