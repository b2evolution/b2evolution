<?php
/**
 * This file implements the UI controller for browsing the Custom skin template editing.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @todo This file should get removed in favour of the file manager that should also handle editing evoskin files!
 *
 * @package admin
 * @author This file built upon code from original b2 - http://cafelog.com/
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$AdminUI->set_path( 'templates' );

// Check permission:
$current_User->check_perm( 'templates', '', true );

param( 'action', 'string' );
param( 'error', 'string' );
param( 'file', 'string' );
param( 'a', 'string' );


switch($action)
{
	case 'update':
		if( $demo_mode )
		{
			$Messages->add( 'Sorry, you cannot update the custom skin in demo mode!', 'error' );
			// no break
		}
		else
		{
			// Determine the edit folder:
			$edit_folder = get_path('skins').'custom/';

			param( 'newcontent', 'html' );
			$f = fopen( $edit_folder.$file, "w+" );
			fwrite($f,$newcontent);
			fclose($f);

			header("Location: admin.php?ctrl=templates&file=$file&a=te");
			exit();

			break;
		}


	default:
		// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
		$AdminUI->disp_html_head();

		// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
		$AdminUI->disp_body_top();

		// Determine the edit folder:
		$edit_folder = get_path('skins').'custom/';

		$file = trim($file);
		if( !empty($file))
		{
			echo '<div class="panelblock">';

			echo T_('Listing:').' <strong>'.$edit_folder.$file.'</strong>';

			if( ereg( '([^-A-Za-z0-9._]|\.\.)', $file ) )
			{
				echo '<p>', T_('Invalid filename!'), '</p>';
			}
			elseif( !is_file($edit_folder.$file) )
			{
					echo '<p>', T_('Oops, no such file !'), '</p>';
			}
			else
			{

				$f = fopen( $edit_folder.$file, 'r');
				$content = fread($f,filesize($edit_folder.$file));
				//	$content = template_simplify($content);
				$content = htmlspecialchars($content);
				//	$content = str_replace("</textarea","&lt;/textarea",$content);

				if ($a == 'te')	echo '<em> [ ', T_('File edited!'), ' ]</em>';

				if (!$error)
				{
					echo '<p>'.T_('Be careful what you do, editing this file could break your template! Do not edit what\'s between <code>&lt;?php</code> and <code>?&gt;</code> if you don\'t know what you\'re doing!').'</p>';
					$Form = & new Form( NULL, 'template_checkchanges', 'post', 'none' );
					$Form->begin_form();
						$Form->hidden_ctrl();
						?>
						<fieldset class="input"><img src="<?php echo $rsc_url; ?>img/blank.gif" alt="" width="1" height="1" /><textarea cols="80" rows="20" class="large" name="newcontent" tabindex="1"><?php echo $content ?></textarea></fieldset>
						<input type="hidden" name="action" value="update" />
						<input type="hidden" name="file" value="<?php echo $file ?>" />
						<br />
						<?php
						if( is_writable($edit_folder.$file) )
						{
							echo '<input type="submit" name="submit" class="SaveButton" value="', T_('Save !'), '" tabindex="2" />';
						}
						else
						{
							echo '<input type="button" name="oops" class="search" value="', T_('(you cannot update that file/template: must make it writable, e.g. CHMOD 766)'), '" tabindex="2" />';
						}
					$Form->end_form();
				}
			}
			echo "</div>\n";
		}
		?>

		<div class="panelblock">
		<p><?php echo T_('This screen allows you to edit the <strong>custom skin</strong> (located under /skins/custom). ') ?></p>
		<p><?php echo T_('You can edit any of the following files (provided it\'s writable by the server, e.g. CHMOD 766)') ?>:</p>
		<?php
			// Determine the edit folder:
			if( empty($edit_folder) ) $edit_folder = get_path('skins').'custom/';
			//lists all files in edit directory
			if( !is_dir($edit_folder) )
			{
				echo '<div class="panelinfo"><p>'.sprintf( T_('Directory %s not found.'), $edit_folder ).'</p></div>';
			}
			else
			{ // $edit_folder exists
				echo '<ul>';
				$this_dir = dir( $edit_folder );
				while ($this_file = $this_dir->read())
				{
					if( is_file($edit_folder.$this_file) )
					{
						echo '<li><a href="?ctrl=templates&amp;file='.$this_file.'">'.$this_file.'</a>';
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
						echo '</li>';
					}
				}
				echo '</ul>';
			}

		echo '<p>'.T_('Note: of course, you can also edit the files/templates in your text editor and upload them. This online editor is only meant to be used when you don\'t have access to a text editor...').'</p>';

		echo '</div>';
	break;
}

/* </Template> */

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();
?>
