<?php
/**
 * The Filemanager
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @todo: thumbnail view
 * @todo: PHPInfo (special permission)
 * @todo: directly run PHP-code (eval)
 *
 * @package admin
 */

require_once( dirname(__FILE__).'/_header.php' );
require( dirname(__FILE__).'/'.$admin_dirout.'/'.$core_subdir.'/_class_filemanager.php' );
#ob_start();

$admin_tab = 'files';
$admin_pagetitle = T_('File Manager').' (alpha)';

param( 'cd', 'string', '' );         // the path relative to the root dir
param( 'action', 'string', '' );     // 3.. 2.. 1.. action :)
param( 'selaction', 'string', '' );  // action for selected files/dirs

param( 'file', 'string', '' );       // selected file
param( 'order', 'string', 'name' );
param( 'asc', 'string', '#' );

if( $current_User->level < 10 )
{
	die( 'This is alpha. You need user level 10 to play with this.' );
}

if( $action == 'update_settings' )
{
	param( 'option_dirsattop', 'integer', 0 );
	$UserSettings->set( 'fm_dirsattop', $option_dirsattop );
		
	if( $UserSettings->updateDB() )
	{
		$Messages->add( T_('User preferences updated').'.', 'note' );
	}
}


$Fileman = new FileManager( $current_User, 'files.php', $cd, $order, $asc );

if( $action == '' && $file != '' )
{ // a file is selected/clicked, default action
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<title><?php echo $file.'&mdash;'.T_('b2evolution Filemanager') ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<link href="<?php echo $admin_url ?>/admin.css" rel="stylesheet" type="text/css" />
	<style type="text/css">
	<!--
	div.image { text-align:center;clear:both;margin:1ex; }
	img.image { border:1px dashed #d91;padding:1ex; }
	.linenr { background-color: #ff0; font-weight:bold; }
	-->
	</style>
	</head>

	<body><!-- onclick="javascript:window.close()" title="<?php echo T_('Click anywhere in this window to close it.') ?>">-->

	<?php
		if( preg_match( '/\.(jpe?g|gif|png|swf)$/i', $file) )
		{ // image
			?>
			<div class="image">
			<img class="image" src="<?php $Fileman->cdisp_file( $file, 'url' ) ?>" <?php $Fileman->cdisp_file( $file, 'imgsize', 'string' ) ?>>
			</div>
			<?php
		}
		else
		{ // display raw file
			param( 'showlinenrs', 'integer', 0 );
			$buffer = file( $Fileman->cget_file( $file, 'path' ) );

			// TODO: check if new window was opened and provide close X in case
			/*<a href="javascript:window.close()"><img class="center" src="<?php echo $admin_url.'/img/xross.gif' ?>" width="13" height="13" alt="[X]" title="<?php echo T_('Close this window') ?>" /></a>*/

			echo T_('file').': '.$file.'<br />';

			if( !count($buffer) )
			{
				echo ' ** '.T_('empty file').' ** ';
			}
			else
			{
				echo count($buffer).' '.T_('lines').'<br />';

				$linenr_width = strlen( count($buffer)+1 );

				?>
				<noscript type="text/javascript">
					<a href="<?php echo $_SERVER['PHP_SELF'].'?cd='.$cd.'&amp;file='.$file.'&amp;showlinenrs='.(1-$showlinenrs).'">'
						.( $showlinenrs ? T_('hide line numbers') : T_('show line numbers') ).'</a>';
				?>
				</noscript>
				<script type="text/javascript">
				<!--
				document.write('<a id="togglelinenrs" href="javascript:toggle_linenrs()">toggle</a>');
				//-->
				</script>

				<pre><?php
				foreach( $buffer as $linenr => $line )
				{
					echo '<span name="linenr" class="linenr">';
					if( $showlinenrs ) echo ' '.str_pad($linenr+1, $linenr_width, ' ', STR_PAD_LEFT).' ';
					echo '</span>'.htmlspecialchars( str_replace( "\t", '  ', $line ) );  // TODO: customize tab-width
				}
			}

			// TODO: stupid thing, document.getElementsByName seems to not work with IE here, so I have to use getElementsByTagName. This could perhaps check for the nodes name though.
			?></pre>

			<script type="text/javascript">
			<!--
			showlinenrs = true;
			toggle_linenrs();
			function toggle_linenrs()
			{
				if( showlinenrs )
				{
					var replace = document.createTextNode('<?php echo /* This is a Javascript string! */ T_('show line numbers') ?>');
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
					var replace = document.createTextNode('<?php echo /* This is a Javascript string! */ T_('hide line numbers') ?>');
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
		?>

	</body>
</html>
<?php
exit;
}


// Actions for selected files
if( $selaction != '' )
{
	param( 'selectedfiles', 'array', array() );
	param( 'sel_recursive', 'array', array() );

	// map informations
	foreach( $selectedfiles as $nr => $name )
	{
		$withsubdirs[ $name ] = in_array( $name, $sel_recursive );

	}

	if( !count( $selectedfiles ) )
	{
		$Fileman->Messages->add( T_('Nothing selected.') );
	}
	else switch( $selaction )
	{
		case T_('Send by mail'):
			echo 'todo: Send selected by mail, query email address..';
			break;

		case T_('Download'):
			param( 'zipname', 'string', '' );
			param( 'exclude_sd', 'integer', 0 );

			if( empty($zipname) )
			{
				require( dirname(__FILE__).'/_menutop.php' );
				require( dirname(__FILE__).'/_menutop_end.php' );
				?>
				<div class="panelblock">
				<div class="panelinfo">
				<p>
				<?php
				echo T_('You want to download:').'<ul>';
				foreach( $selectedfiles as $file )
				{
					if( $Fileman->cisdir( $file ) )
					{
						printf('<li>'.T_('Directory [%s]'), $file).( $withsubdirs[$file] ? ' ('.T_('with subdirectories').')' : '').'</li>';
					}
					else printf('<li>'.T_('File [%s]'), $file).( $withsubdirs[$file] ? ' ('.T_('with subdirectories').')' : '').'</li>';
				}
				?>
				</p>
				</div>
				<form action="files.php" class="fform" method="post">
				<fieldset>
					<legend><?php echo T_('Please give a filename and choose zip format:') ?></legend>

					<?php
					foreach( $selectedfiles as $file )
					{?>
					<input type="hidden" name="selectedfiles[]" value="<?php echo format_to_output( $file, 'formvalue' ) ?>" />
					<?php
					}
					
					$Fileman->form_hiddeninputs();
					
					form_text( 'zipname', '', 20, T_('Archive filename'), T_('This is the filename that will be send to you.') );
					form_checkbox( 'exclude_sd', $exclude_sd, T_('Exclude subdirectories'), T_('This will exclude subdirectories of selected directories.') );
					?>
					<div class="input"><input type="submit" name="selaction" value="<?php echo T_('Download') ?>" class="search" /></div>
				</fielset>
				</form>
				</div>
				<?php
				require( dirname(__FILE__). '/_footer.php' );
			}
			else
			{ // Downloading
				require( dirname(__FILE__).'/'.$admin_dirout.'/'.$core_subdir.'/_class_zip.php' );

				$options = array (
					'basedir' => $Fileman->cwd,
					'inmemory' => 1,
					'recurse' => 1-$exclude_sd,
				);

				$zipfile = new zip_file( $zipname );
				$zipfile->set_options( $options );
				$zipfile->add_files( $Fileman->arraylist() );
				$zipfile->create_archive();

				#header('Content-length: ' . filesize($path));
				$zipfile->download_file();
				exit;
				#$Fileman->Messages->add( sprintf(T_('Zipfile [%s] sent to you!'), $selectedfiles[0]), 'note' );

			}

			break;

		case T_('Delete'):
			// TODO: extra confirmation?

			foreach( $selectedfiles as $file )
			{
				$Fileman->Messages->add( sprintf(T_('Would delete [%s]'), $file).( $withsubdirs[$file] ? ' ('.T_('with subdirectories').')' : '').'..', 'note' );

			}

			break;
	}
}

switch( $action ) // (we catched empty action before)
{
	case 'createnew':  // create new file/dir
		param( 'createnew', 'string', '' );
		param( 'createname', 'string', '' );

		if( $createnew == 'dir' )
		{
			if( $Fileman->createdir( $createname ) )
			{
				$Fileman->reloadpage();
			}
			break;
		}
		elseif( $createnew == 'file' )
		{
			param( 'createname', 'string', '' );
			if( $Fileman->createfile( $createname ) )
			{
				$Fileman->reloadpage();
			}
			break;
		}
		break;

	case 'delete':
		param( 'file', 'string', '' );

		$Fileman->Messages->add( 'Would delete '.$file.' and reload..', 'note' );
		/*if( $Fileman->delete( $file ) )
		{
			$Fileman->reloadpage();
		}*/

		break;

	case 'rename':
		param( 'file', 'string', '' );

		echo 'todo: Rename dialog..';
		break;

	case 'editperm':
		param( 'file', 'string', '' );
		param( 'chmod', 'string', '' );

		if( empty($chmod) )
		{
			$message = '
			<form action="files.php">
			'.$Fileman->form_hiddeninputs().'
			<input type="hidden" name="file" value="'.format_to_output( $file, 'formvalue' ).'" />
			<input type="text" name="chmod" value="'.$Fileman->cget_file( $file, 'perms', 'octal' ).'" maxlength="3" size="3" />
			<input type="submit" name="action" value="editperm" />
			</form>';
		}
		else
		{
			$oldperm = $Fileman->cget_file( $file, 'perms' );
			pre_dump( $Fileman->cdo_file( $file, 'chmod', $chmod ), 'chmod!');
		}

		break;

	case 'upload':
		// Check permissions:
		if( !$Fileman->perm( 'upload' ) )
		{
			$Fileman->Messages->add( T_('You have no permissions to upload into this directory.') );
		}
		else
		{

			if( isset($_FILES) && count( $_FILES ) )
			{
				echo 'Uploaded';

				pre_dump( $_FILES, 'uploaded' );
				
				/*if( isset( $_FILES[''] ) )
				switch( $
				UPLOAD_ERR_OK
				Value: 0; There is no error, the file uploaded with success.
				
				UPLOAD_ERR_INI_SIZE
				Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
				
				UPLOAD_ERR_FORM_SIZE
				Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form.
				
				UPLOAD_ERR_PARTIAL
				Value: 3; The uploaded file was only partially uploaded.
				
				UPLOAD_ERR_NO_FILE
				Value: 4; No file was uploaded.*/

			}
			else
			{
				$allowedftypes = preg_split( '/\s+/', trim( $fileupload_allowedtypes) );
	
				$message = '
				<p><strong>'.T_('File upload').'</strong></p>
				<p>'.T_('Allowed file types:').' '.implode(', ', $allowedftypes).'</p>
				<p>'.sprintf( T_('Maximum allowed file size: %d KB'), $fileupload_maxk ).'</p>
				
				<form enctype="multipart/form-data" action="'.$Fileman->curl().'" method="post">
					<input type="hidden" name="MAX_FILE_SIZE" value="'.($fileupload_maxk*1024).'" />
					<input type="hidden" name="action" value="upload" />';
					
					for( $i = 0; $i < 3; $i++ )
					{
						$message .= '<input name="uploadfile['.$i.']" type="file" size="40" /><br />';
						//'.T_('Description').':	<input type="text" name="imgdesc['.$i.']" size="50" /><br />';
					}
					
					$message .= '
					<input type="submit" value="'.T_('Upload !').'" class="search" />
					</form>
					';
			}

		}

		break;
}


require( dirname(__FILE__).'/_menutop.php' );
echo T_('Current directory').': '.$Fileman->cwd_clickable();
require( dirname(__FILE__).'/_menutop_end.php' );

?>
<div id="filemanmain">
<?php
if( $Fileman->Messages->count( 'all' ) || isset( $message ) )
{
	?>
	<div class="fm_messages">
	<?php
	$Fileman->Messages->display( '', '', true, 'error' );
	$Fileman->Messages->display( '', '', true, 'note' );
	if( isset($message) )
	{
		echo $message;
	}
	?>
	</div>
	<?php
}
?>
<div class="toolbar">
	<form action="files.php" name="search" class="toolbaritem">
		<?php echo $Fileman->form_hiddeninputs() ?>
		<input type="text" name="searchfor" value="--todo--" size="20" />
		<input type="submit" value="<?php echo format_to_output( T_('Search'), 'formvalue' ) ?>" />
	</form>
	<form action="files.php" name="filter" class="toolbaritem">
		<?php echo $Fileman->form_hiddeninputs() ?>
		<input type="text" name="filter" value="--todo--" size="20" />
		<input type="submit" value="<?php echo format_to_output( T_('Filter'), 'formvalue' ) ?>" />
	</form>

	<div class="clear"></div>
</div>

<form name="FilesForm" action="files.php" method="post">
<table class="fileman">

<tr>
	<th colspan="2" style="white-space:nowrap;">
		<a href="<?php $Fileman->cdisp('link', 'home') ?>"><?php echo $Fileman->icon( 'home', 'imgtag' ) ?></a>
		&nbsp;
		<a href="<?php $Fileman->cdisp('link', 'parent') ?>"><?php echo $Fileman->icon( 'parent', 'imgtag' ) ?></a>
	</th>
	<th><?php echo $Fileman->link_sort( 'name', /* TRANS: file name */ T_('Name') ) ?></th>
	<th><?php echo $Fileman->link_sort( 'type', /* TRANS: file type */ T_('Type') ) ?></th>
	<th><?php echo $Fileman->link_sort( 'size', /* TRANS: file size */ T_('Size') ) ?></th>
	<th><?php echo $Fileman->link_sort( 'lastm', /* TRANS: file's last change / timestamp */ T_('Last change') ) ?></th>
	<th><?php echo $Fileman->link_sort( 'perms', /* TRANS: file's permissions */ T_('Perms') ) ?></th>
	<th><?php echo /* TRANS: file action, (html) view */ T_('Action') ?></th>
</tr>

<?php
param( 'checkall', 'integer', 0 );  // Non-Javascript-CheckAll

$i = 0;
while( $Fileman->next() )
{
	$i++;

	$link_default_js = 'if( (typeof clickedonlink) == \'undefined\' ){ window.open(\''.$Fileman->cget('link')."', 'fileman_default', 'toolbar=0,resizable=yes,";
	if( $r = $Fileman->cget('imgsize', 'widthheight') )
	{
		$link_default_js .= 'width='.($r[0]+100).',height='.($r[1]+100);
	}
	$link_default_js .= "')}";
	$link_default_js = '';

	?>
	<tr style="background:<?php echo ( $i%2 ) ? '#fff' : '#eee' ?>" onmouseout="this.style.background='<?php echo ( $i%2 ) ? '#fff' : '#eee' ?>'" onmouseover="this.style.background='#ddd'" onclick="document.getElementsByName('selectedfiles[]')[<?php echo $i-1 ?>].click();">
		<td class="checkbox">
			<input title="<?php echo T_('select this file') ?>" type="checkbox" name="selectedfiles[]" value="<?php echo format_to_output( $Fileman->cget('name'), 'formvalue' ) ?>" onclick="document.getElementsByName('selectedfiles[]')[<?php echo $i-1 ?>].click();"<?php if( $checkall ) echo ' checked="checked" '?> />
		</td>
		<td class="icon" onclick="window.location.href = '<?php $Fileman->cdisp('link') ?>'">
			<?php /*echo $i++;*/ $Fileman->cdisp('iconimg') ?>
		</td>
		<td class="filename" onclick="<?php echo $link_default_js ?>">
			<!--<noscript type="text/javascript">--><a onclick="clickedonlink = 1;" href="<?php $Fileman->cdisp('link') ?>"><!--/noscript-->
				<?php	$Fileman->cdisp('name'); $Fileman->cdisp('imgsize', '', ' (%s)') ?>
			<!--noscript type="text/javascript"--></a><!--/noscript-->
			<?php
			if( $Fileman->cisdir() )
			{
				echo '<a href="'.$Fileman->cget('link').'" title="'.T_('open in new window').'" target="_blank">[new]</a>';
			}

			?>
		</td>
		<td class="type"><?php $Fileman->cdisp('type') ?></td>
		<td class="size"><?php $Fileman->cdisp('nicesize') ?></td>
		<td class="timestamp"><?php $Fileman->cdisp('lastmod') ?></td>
		<td class="perms"><?php $Fileman->cdisp( 'link_editperm', '', '<a href="%s">'.$Fileman->cget('perms', 'octal').'</a>' ) ?></td>
		<td class="actions"><?php
			$Fileman->cdisp( 'link_edit', '', '<a href="%s">'.$Fileman->icon( 'edit', 'imgtag' ).'</a>' );
			$Fileman->cdisp( 'link_copymove', '', '<a href="%s">'.$Fileman->icon( 'copymove', 'imgtag' ).'</a>' );
			$Fileman->cdisp( 'link_rename', '', '<a href="%s">'.$Fileman->icon( 'rename', 'imgtag' ).'</a>' );
			$Fileman->cdisp( 'link_delete', '', '<a href="%s">'.$Fileman->icon( 'delete', 'imgtag' ).'</a>' );
			// TODO: action link
			?></td>
	</tr>
	<?php
}
if( $i == 0 )
{ // empty directory
	?>
	<tr>
	<td colspan="8" class="left">
	<?php echo T_('The directory is empty.') ?>
	</td>
	</tr>
	<?php
}

?>
<tr class="bottomrow">

<td colspan="8">
<?php
if( $i != 0 )
{
	?>
		<script type="text/javascript">
		<!--
		document.write('<a href="#" onclick="toggleCheckboxes(\'FilesForm\', \'selectedfiles[]\');" title="<?php echo T_('(un)selects all checkboxes using Javascript') ?>"><span id="checkallspan_0"><?php echo T_('(un)check all')?></span></a>');
		//-->
		</script>
		<noscript type="text/javascript">
			<a href="<?php
			echo url_add_param( $Fileman->curl(), 'checkall='. ( $checkall ? '0' : '1' ) );
			echo '">';
			echo ($checkall) ? T_('uncheck all') : T_('check all');
			?></a>
		</noscript>
		&mdash; <strong><?php echo T_('with selected files:') ?> </strong>
		<?php echo $Fileman->form_hiddeninputs() ?>
		<input type="submit" name="selaction" value="<?php echo T_('Delete') ?>" onclick="return confirm('<?php echo /* This is a Javascript string! */ T_('Do you really want to delete the selected files?') ?>')" />
		<input type="submit" name="selaction" value="<?php echo T_('Download') ?>" />
		<input type="submit" name="selaction" value="<?php echo T_('Send by mail') ?>" />
<?php
}
?>
</td>

</tr>
</table>
</form>

<div class="toolbar">
	<form id="options" class="fm_options" action="files.php" method="post">
		<div id="options_list">
			<?php echo T_('sort directories at top') ?>
			<input type="checkbox" name="option_dirsattop" value="1"<?php if( $UserSettings->get('fm_dirsattop') ) echo ' checked="checked"' ?> />
			<br />

			<?php echo $Fileman->form_hiddeninputs() ?>
			<input type="hidden" name="action" value="update_settings" />
			<div class="input">
			<input type="submit" value="<?php echo T_('Update !') ?>" />
			</div>
		</div>
		<script type="text/javascript">
		<!--
			document.write( '<a id="options_title" href="javascript:toggle_options()"><?php echo T_("show options") ?></a>' )
		// -->
		</script>
	</form>

	<form action="files.php" name="filter" class="toolbaritem">
		<?php echo $Fileman->form_hiddeninputs() ?>
		<input type="hidden" name="action" value="upload" />
		<input type="submit" value="<?php echo format_to_output( T_('Upload a file/image'), 'formvalue' ) ?>" />
	</form>

	<form action="files.php" name="filter" class="toolbaritem">
		<select name="createnew">
			<option value="file"><?php echo T_('file') ?></option>
			<option value="dir"><?php echo T_('directory') ?></option>
		</select>
		<input type="text" name="createname" value="" size="20" />
		<?php echo $Fileman->form_hiddeninputs() ?>
		<input type="hidden" name="action" value="createnew" />
		<input type="submit" value="<?php echo format_to_output( T_('Create new'), 'formvalue' ) ?>" />
	</form>
</div>

<script type="text/javascript">
<!--
showoptions = true;
toggle_options();
function toggle_options()
{
	if( showoptions )
	{
		var replace = document.createTextNode('<?php echo /* This is a Javascript string! */ T_('show options') ?>');
		var display_list = 'none';
		var display_border = '0';
		showoptions = false;
	}
	else
	{
		var replace = document.createTextNode('<?php echo /* This is a Javascript string! */ T_('hide options') ?>');
		var display_list = 'inline';
		var display_border = '1px solid #d91';
		showoptions = true;
	}
	document.getElementById('options').style.border = display_border;
	document.getElementById('options_list').style.display = display_list;
	document.getElementById('options_title').replaceChild(replace, document.getElementById( 'options_title' ).firstChild);
}
-->
</script>

</div>
<?php
require( dirname(__FILE__). '/_footer.php' );
#echo replacePngTags( ob_get_clean(), $img_url);
?>
