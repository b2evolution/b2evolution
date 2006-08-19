<?php
/**
 * This file implements the UI for file viewing.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @todo skin compliant header!
 *
 * @version $Id$
 */

/**
 * Load config, init and get the {@link $mode mode param}.
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'/_main.inc.php';


// Check permission:
if( ! isset($current_User) )
{
	debug_die( 'No permissions to view file (not logged in)!' );
}
$current_User->check_perm( 'files', 'view', true );

// Load params
param( 'viewtype', 'string', true );
param( 'root', 'string', true ); // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))
param( 'path', 'string', true );

// Load fileroot infos
$FileRootCache = & get_Cache( 'FileRootCache' );
$FileRoot = & $FileRootCache->get_by_ID( $root );

// Create file object
$selected_File = & new File( $FileRoot->type , $FileRoot->in_type_ID, $path );

header( 'Content-type: text/html; charset='.$io_charset );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<title><?php echo $selected_File->get_name().' ('.T_('Preview').')'; ?></title>
	<script type="text/javascript" src="../rsc/js/styleswitcher.js"></script>
	<link href="../rsc/css/viewfile.css" rel="stylesheet" type="text/css" />
</head>

<body>
	<?php

switch( $viewtype )
{
	case 'image':
		{{{ // Display image file:
			echo '<div class="center">';

			if( $imgSize = $selected_File->get_image_size( 'widthheight' ) )
			{
				echo '<img ';
				if( $alt = $selected_File->dget( 'alt', 'htmlattr' ) )
				{
					echo 'alt="'.$alt.'" ';
				}
				if( $title = $selected_File->dget( 'title', 'htmlattr' ) )
				{
					echo 'title="'.$title.'" ';
				}
				echo 'class="framed" src="'.$selected_File->get_url().'"'
							.' width="'.$imgSize[0].'" height="'.$imgSize[1].'" />';

				echo '<div class="subline">';
				echo '<p><strong>'.$selected_File->dget( 'title' ).'</strong></p>';
				echo '<p>'.$selected_File->dget( 'desc' ).'</p>';
				echo '<p>'.$selected_File->get_name().' &middot; ';
				echo $selected_File->get_image_size().' &middot; ';
				echo $selected_File->get_size_formatted().'</p>';
				echo '</div>';

			}

			echo '</div>';
		}}}
		break;

	case 'text':

		if( ($buffer = @file( $selected_File->get_full_path() )) !== false )
		{{{ // Display raw file
			param( 'showlinenrs', 'integer', 0 );

			$buffer_lines = count( $buffer );

			echo '<div class="fileheader">';

			echo '<p>';
			echo T_('File').': <strong>'.$selected_File->get_name().'</strong>';
			echo ' &middot; ';
			echo T_('Title').': <strong>'.$selected_File->dget( 'title' ).'</strong>';
			echo '</p>';

	 		echo '<p>';
			echo T_('Description').': '.$selected_File->dget( 'desc' );
			echo '</p>';


			if( !$buffer_lines )
			{
				echo '<p>** '.T_('Empty file!').' ** </p></div>';
			}
			else
			{
				echo '<p>';
				printf( T_('%d lines'), $buffer_lines );

				$linenr_width = strlen( $buffer_lines+1 );

				echo ' [';
				?>
				<noscript type="text/javascript">
					<a href="<?php echo $selected_File->get_url().'&amp;showlinenrs='.(1-$showlinenrs); ?>">

					<?php echo $showlinenrs ? T_('Hide line numbers') : T_('Show line numbers');
					?></a>
				</noscript>
				<script type="text/javascript">
					<!--
					document.write('<a id="togglelinenrs" href="javascript:toggle_linenrs()">toggle</a>');

					showlinenrs = <?php var_export( !$showlinenrs ); ?>;

					toggle_linenrs();

					function toggle_linenrs()
					{
						if( showlinenrs )
						{
							var replace = document.createTextNode('<?php echo TS_('Show line numbers') ?>');
							showlinenrs = false;
							var text = document.createTextNode( '' );
							for( var i = 0; i<document.getElementsByTagName("span").length; i++ )
							{
								if( document.getElementsByTagName("span")[i].hasChildNodes() )
									document.getElementsByTagName("span")[i].firstChild.data = '';
								else
								{
									document.getElementsByTagName("span")[i].appendChild( text );
								}
							}
						}
						else
						{
							var replace = document.createTextNode('<?php echo TS_('Hide line numbers') ?>');
							showlinenrs = true;
							for( var i = 0; i<document.getElementsByTagName("span").length; i++ )
							{
								var text = String(i+1);
								var upto = <?php echo $linenr_width ?>-text.length;
								for( var j=0; j<upto; j++ ){ text = ' '+text; }
								if( document.getElementsByTagName("span")[i].hasChildNodes() )
									document.getElementsByTagName("span")[i].firstChild.data = ' '+text+' ';
								else
									document.getElementsByTagName("span")[i].appendChild( document.createTextNode( ' '+text+' ' ) );
							}
						}

						document.getElementById('togglelinenrs').replaceChild(replace, document.getElementById( 'togglelinenrs' ).firstChild);
					}
					-->
				</script>
				<?php

				echo ']</p>';
				echo '</div>';

				echo '<pre class="rawcontent">';

				for( $i = 0; $i < $buffer_lines; $i++ )
				{
					echo '<span name="linenr" class="linenr">';
					if( $showlinenrs )
					{
						echo ' '.str_pad($i+1, $linenr_width, ' ', STR_PAD_LEFT).' ';
					}
					echo '</span>'.htmlspecialchars( str_replace( "\t", '  ', $buffer[$i] ) );  // TODO: customize tab-width
				}

	  		echo '</pre>';

				echo '<div class="eof">** '.T_('End Of File').' **</div>';
			}
		}}}
		else
		{
			Log::display( '', '', sprintf( T_('The file &laquo;%s&raquo; could not be accessed!'), $selected_File->get_rdfs_rel_path( $selected_File ) ), 'error' );
		}
		break;

	default:
			Log::display( '', '', sprintf( T_('The file &laquo;%s&raquo; could not be accessed!'), $selected_File->get_name() ), 'error' );
		break;
}

debug_info();
?>

</body>
</html>

<?php
/*
 * $Log$
 * Revision 1.10  2006/08/19 07:56:29  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.9  2006/05/19 18:15:04  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.8.2.1  2006/05/19 15:06:23  fplanque
 * dirty sync
 *
 * Revision 1.8  2006/04/29 01:24:04  blueyed
 * More decent charset support;
 * unresolved issues include:
 *  - front office still forces the blog's locale/charset!
 *  - if there's content in utf8, it cannot get displayed with an I/O charset of latin1
 *
 * Revision 1.7  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.6  2006/04/04 22:20:29  blueyed
 * "Gracefully" die, if no $current_User set
 *
 * Revision 1.5  2006/03/12 23:08:53  fplanque
 * doc cleanup
 *
 * Revision 1.4  2006/03/12 03:03:32  blueyed
 * Fixed and cleaned up "filemanager".
 *
 * Revision 1.3  2006/02/23 21:11:47  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.2  2005/12/16 13:50:49  blueyed
 * FileRoot::get_by_ID() from post-phoenix
 *
 * Revision 1.1  2005/12/14 19:36:16  fplanque
 * Enhanced file management
 *
 * Revision 1.17  2005/11/21 18:33:19  fplanque
 * Too many undiscussed changes all around: Massive rollback! :((
 * As said before, I am only taking CLEARLY labelled bugfixes.
 *
 */
?>