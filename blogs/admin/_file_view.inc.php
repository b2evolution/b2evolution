<?php
/**
 * This file implements the UI for file viewing.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo $selectedFile->getName().' :: '.$app_name.' '.T_('Filemanager'); ?></title>
	<link href="variation.css" rel="stylesheet" type="text/css" title="Variation" />
	<link href="desert.css" rel="alternate stylesheet" type="text/css" title="Desert" />
	<link href="legacy.css" rel="alternate stylesheet" type="text/css" title="Legacy" />
	<?php if( is_file( dirname(__FILE__).'/custom.css' ) ) { ?>
	<link href="custom.css" rel="alternate stylesheet" type="text/css" title="Custom" />
	<?php } ?>
	<script type="text/javascript" src="styleswitcher.js"></script>
	<link href="fileman.css" rel="stylesheet" type="text/css" />
</head>
<body><!-- onclick="javascript:window.close()" title="<?php echo T_('Click anywhere in this window to close it.') ?>">-->

	<?php
	if( $imgSize = $selectedFile->getImageSize( 'string' ) ) // TODO: check
	{	// --------------------------------
		// We are displaying an image file:
		// --------------------------------
		?>
		<div class="center">
			<?php echo $selectedFile->getName() ?><br />
			<img alt="<?php echo T_('The selected image') ?>"
				class="framed"
				src="<?php echo $Fileman->getFileUrl( $selectedFile ) ?>"
				<?php echo $imgSize; ?> /><br />
		</div>
		<?php
	}
	elseif( ($buffer = @file( $selectedFile->getPath() )) !== false )
	{{{ // display raw file
		param( 'showlinenrs', 'integer', 0 );

		$buffer_lines = count( $buffer );

		// TODO: check if new window was opened and provide close X in case
		/*<a href="javascript:window.close()"><img class="center" src="<?php echo $admin_url.'img/xross.gif' ?>" width="13" height="13" alt="[X]" title="<?php echo T_('Close this window') ?>" /></a>*/

		echo '<div class="fileheader">';
		echo T_('File').': '.$selectedFile->getName().'<br />';
		$selectedFile->ID();

		if( !$buffer_lines )
		{
			echo '</div> ** '.T_('empty file').' ** ';
		}
		else
		{
			printf( T_('%d lines'), $buffer_lines ).'<br />';
			$linenr_width = strlen( $buffer_lines+1 );

			?>
			<noscript type="text/javascript">
				<a href="<?php echo $Fileman->getLinkFile( $selectedFile ).'&amp;showlinenrs='.(1-$showlinenrs); ?>">

				<?php echo $showlinenrs ?
										T_('hide line numbers') :
										T_('show line numbers');
				?></a>
			</noscript>
			<script type="text/javascript">
			<!--
			document.write('<a id="togglelinenrs" href="javascript:toggle_linenrs()">toggle</a>');
			//-->
			</script>

			</div>
			<pre class="rawcontent"><?php

			for( $i = 0; $i < $buffer_lines; $i++ )
			{
				echo '<span name="linenr" class="linenr">';
				if( $showlinenrs )
				{
					echo ' '.str_pad($i+1, $linenr_width, ' ', STR_PAD_LEFT).' ';
				}
				echo '</span>'.htmlspecialchars( str_replace( "\t", '  ', $buffer[$i] ) );  // TODO: customize tab-width
			}

			?>

			<script type="text/javascript">
			<!--
			showlinenrs = <?php var_export( !$showlinenrs ); ?>;
			toggle_linenrs();
			function toggle_linenrs()
			{
				if( showlinenrs )
				{
					var replace = document.createTextNode('<?php echo /* TRANS: Warning this is a javascript string */ T_('show line numbers') ?>');
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
					var replace = document.createTextNode('<?php echo /* TRANS: Warning this is a javascript string */ T_('hide line numbers') ?>');
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

		}
		?></pre>

		<?php
	}}}
	else
	{
		Log::display( '', '', sprintf( T_('The file &laquo;%s&raquo; could not be accessed!'),
																		$Fileman->getFileSubpath( $selectedFile ) ), 'error' );
	}
	?>
</body>
</html>