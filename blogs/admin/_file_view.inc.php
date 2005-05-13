<?php
/**
 * This file implements the UI for file viewing.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
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
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo $selectedFile->get_name().' ('.T_('Preview').')'; ?></title>
	<link href="skins/legacy/rsc/css/variation.css" rel="stylesheet" type="text/css" title="Variation" />
	<link href="skins/legacy/rsc/css/desert.css" rel="alternate stylesheet" type="text/css" title="Desert" />
	<link href="skins/legacy/rsc/css/legacy.css" rel="alternate stylesheet" type="text/css" title="Legacy" />
	<?php if( is_file( dirname(__FILE__).'/skins/legacy/rsc/css/custom.css' ) ) { ?>
	<link href="skins/legacy/rsc/css/custom.css" rel="alternate stylesheet" type="text/css" title="Custom" />
	<?php } ?>
	<script type="text/javascript" src="../rsc/js/styleswitcher.js"></script>
	<link href="../rsc/css/fileman.css" rel="stylesheet" type="text/css" />
</head>

<body>
	<?php
	 /* 21st century and some people still like to break copy/paste functionnality and stuff like that... :'(
		  onclick="if( history.length > 1 ) { history.back() } else { window.close() }"
			title="<?php echo T_('Click anywhere in this window to go back or close it if no go-back history available.') ?>">
		*/

	if( $selectedFile->is_image() )
	{ // --------------------------------
		// We are displaying an image file:
		// --------------------------------
		echo '<div class="center">';

		if( $imgSize = $selectedFile->get_image_size( 'widthheight' ) )
		{
			echo '<img ';
			if( $alt = $selectedFile->dget( 'alt', 'htmlattr' ) )
			{
				echo 'alt="'.$alt.'" ';
			}
			if( $title = $selectedFile->dget( 'title', 'htmlattr' ) )
			{
				echo 'title="'.$title.'" ';
			}
			echo 'class="framed" src="'.$Fileman->getFileUrl( $selectedFile ).'"'
						.' width="'.$imgSize[0].'" height="'.$imgSize[1].'" />';

			echo '<div class="subline">';
			echo '<p><strong>'.$selectedFile->dget( 'title' ).'</strong></p>';
			echo '<p>'.$selectedFile->dget( 'desc' ).'</p>';
			echo '<p>'.$selectedFile->get_name().' &middot; ';
			echo $selectedFile->get_image_size().' &middot; ';
			echo $selectedFile->get_size_formatted().'</p>';
			echo '</div>';

		}

		echo '</div>';
	}
	elseif( ($buffer = @file( $selectedFile->get_full_path() )) !== false )
	{{{ // --------------------------------
		// display raw file
		// --------------------------------
		param( 'showlinenrs', 'integer', 0 );

		$buffer_lines = count( $buffer );

		echo '<div class="fileheader">';

		echo '<p>';
		echo T_('File').': <strong>'.$selectedFile->get_name().'</strong>';
		echo ' &middot; ';
		echo T_('Title').': <strong>'.$selectedFile->dget( 'title' ).'</strong>';
		echo '</p>';

 		echo '<p>';
		echo T_('Description').': '.$selectedFile->dget( 'desc' );
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
				<a href="<?php echo $Fileman->getLinkFile( $selectedFile ).'&amp;showlinenrs='.(1-$showlinenrs); ?>">

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
		Log::display( '', '', sprintf( T_('The file &laquo;%s&raquo; could not be accessed!'),
																		$Fileman->get_rdfs_path_relto_root( $selectedFile ) ), 'error' );
	}

	debug_info();
	?>

</body>
</html>