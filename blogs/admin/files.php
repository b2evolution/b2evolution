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

require_once dirname(__FILE__).'/_header.php';
require_once dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_class_filemanager.php';

$admin_tab = 'files';
$admin_pagetitle = T_('File Manager').' (alpha)';

param( 'path', 'string', '' );       // the path relative to the root dir
param( 'action', 'string', '' );     // 3.. 2.. 1.. action :)
param( 'selaction', 'string', '' );  // action for selected files/dirs

param( 'file', 'string', '' );       // selected file
param( 'filter', '', NULL );
param( 'filter_regexp', 'integer', NULL );
param( 'order', 'string', NULL );
param( 'asc', '', NULL );

param( 'root', 'string', NULL );   // the root directory from the dropdown box (user_X or blog_X; X is ID - 'user' for current user (default))

if( $current_User->login != 'demouser' && $current_User->level < 10 )
{ // allow demouser, but noone else below level 10
	exit( 'This is alpha. You need user level 10 to play with this.' );
}

if( $action == 'update_settings' )
{ // updating user settings
	$UserSettings->set( 'fm_dirsattop',        param( 'option_dirsattop',        'integer', 0 ) );
	$UserSettings->set( 'fm_permlikelsl',      param( 'option_permlikelsl',      'integer', 0 ) );
	$UserSettings->set( 'fm_recursivedirsize', param( 'option_recursivedirsize', 'integer', 0 ) );
	$UserSettings->set( 'fm_showhidden',       param( 'option_showhidden',       'integer', 0 ) );

	if( $UserSettings->updateDB() )
	{
		$Messages->add( T_('Your user settings have been updated.'), 'note' );
	}
}


// instanciate Filemanager object
$Fileman = new FileManager( $current_User, 'files.php', $root, $path, $filter, $filter_regexp, $order, $asc );


if( $action == '' && $file != '' )
{ // a file is selected/clicked, default action
	$curFile = $Fileman->get_File_by_filename( $file );

	if( !$curFile )
	{
		$Fileman->Messages->add( sprintf( T_('File [%s] could not be accessed!'), $file ) );
	}
	else
	{{{ // do the default action

		?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
		<head>
			<title><?php echo $file.' :: '.T_('b2evolution Filemanager') ?></title>
			<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
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
			if( is_image( $file ) )
			{ // display image file
				?>
				<div class="image">
					<img class="image" src="<?php echo $Fileman->get_File_url( $curFile ) ?>" <?php echo $curFile->get_imgsize( 'string' ) ?> />
				</div>
				<?php
			}
			elseif( $buffer = @file( $curFile->get_path( true ) ) )
			{{{ // display raw file
				param( 'showlinenrs', 'integer', 0 );

				// TODO: check if new window was opened and provide close X in case
				/*<a href="javascript:window.close()"><img class="center" src="<?php echo $admin_url.'img/xross.gif' ?>" width="13" height="13" alt="[X]" title="<?php echo T_('Close this window') ?>" /></a>*/

				echo '<div class="fileheader">';
				echo T_('file').': '.$file.'<br />';

				if( !count($buffer) )
				{
					echo '</div> ** '.T_('empty file').' ** ';
				}
				else
				{
					echo count($buffer).' '.T_('lines').'<br />';
					$linenr_width = strlen( count($buffer)+1 );

					?>
					<noscript type="text/javascript">
						<a href="<?php echo $Fileman->curl().'&amp;file='.$file.'&amp;showlinenrs='.(1-$showlinenrs).'">'
							.( $showlinenrs ? T_('hide line numbers') : T_('show line numbers') ).'</a>';
					?>
					</noscript>
					<script type="text/javascript">
					<!--
					document.write('<a id="togglelinenrs" href="javascript:toggle_linenrs()">toggle</a>');
					//-->
					</script>

					</div>
					<pre class="rawcontent"><?php
					foreach( $buffer as $linenr => $line )
					{
						echo '<span name="linenr" class="linenr">';
						if( $showlinenrs ) echo ' '.str_pad($linenr+1, $linenr_width, ' ', STR_PAD_LEFT).' ';
						echo '</span>'.htmlspecialchars( str_replace( "\t", '  ', $line ) );  // TODO: customize tab-width
					}

					?>

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

				// TODO: stupid thing, document.getElementsByName seems to not work with IE here, so I have to use getElementsByTagName. This could perhaps check for the nodes name though.
				?></pre>

				<?php
			}}}
			else
			{
				printf( '<p class="error">'.T_('File [%s] could not be accessed!').'</p>', $file );
			}
			?>
		</body>
	</html>
	<?php
	exit;
	}}}
}


// Actions for selected files
if( $selaction != '' )
{
	param( 'selectedfiles', 'array', array() );

	if( !count( $selectedfiles ) )
	{
		$Fileman->Messages->add( T_('Nothing selected.') );
	}
	else
	{
		$curFiles = array();
		foreach( $selectedfiles as $file )
		{
			$selectedFiles[] = $Fileman->get_File_by_filename( $file );
		}

		switch( $selaction )
		{
			case T_('Send by mail'):
				echo 'todo: Send selected by mail, query email address..';
				break;

			case T_('Download'):
				param( 'zipname', 'string', '' );
				param( 'exclude_sd', 'integer', 0 );

				if( empty($zipname) )
				{
					$msg_action = '
					<p>
					'.T_('You want to download:').'<ul>';

					foreach( $selectedFiles as $lFile )
					{
						if( $lFile->get_type() == 'dir' )
						{
							$msg_action .= sprintf('<li>'.T_('Directory [%s]'."</li>\n"), $file);
						}
						else $msg_action .= sprintf('<li>'.T_('File [%s]')."</li>\n", $file);
					}

					$msg_action .= '
					</p>
					</div>
					<div class="panelblock">
					<form action="files.php" class="fform" method="post">
					<fieldset>
						<legend>'.T_('Please give a filename and choose zip format:').'</legend>';

						foreach( $selectedfiles as $file )
						{
							$msg_action .= '<input type="hidden" name="selectedfiles[]" value="'.format_to_output( $file, 'formvalue' )."\" />\n";
						}

						$msg_action .= $Fileman->form_hiddeninputs()."\n"
												.form_text( 'zipname', '', 20, T_('Archive filename'), T_('This is the filename that will be send to you.'), 80, '', 'text', false )."\n"
												.form_checkbox( 'exclude_sd', $exclude_sd, T_('Exclude subdirectories'), T_('This will exclude subdirectories of selected directories.'), '', false )."\n"
												.'<div class="input"><input type="submit" name="selaction" value="'.T_('Download').'" class="search" /></div>
					</fielset>
					</form>';
				}
				else
				{ // Downloading
					require( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_class_zip.php' );

					$options = array (
						'basedir' => $Fileman->cwd,
						'inmemory' => 1,
						'recurse' => 1-$exclude_sd,
					);

					$zipfile = new zip_file( $zipname );
					$zipfile->set_options( $options );

					$arraylist = array();
					foreach( $selectedFiles as $File )
					{
						$arraylist[] = $File->get_name();
					}
					$zipfile->add_files( $arraylist );

					$zipfile->create_archive();

					#header('Content-length: ' . filesize($path));
					$zipfile->download_file();
					exit;
					#$Fileman->Messages->add( sprintf(T_('Zipfile [%s] sent to you!'), $selectedfiles[0]), 'note' );

				}

				break;

			case T_('Delete'):
				// TODO: extra confirmation

				foreach( $selectedfiles as $file )
				{
					$Fileman->Messages->add( sprintf(T_('Would delete [%s]'), $file).'..', 'note' );

				}

				break;
		}
	}
}


switch( $action ) // (we catched empty action before)
{ // {{{
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
			$msg_action = '
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

				$msg_action = '
				<p><strong>'.T_('File upload').'</strong></p>
				<p>'.T_('Allowed file types:').' '.implode(', ', $allowedftypes).'</p>
				<p>'.sprintf( T_('Maximum allowed file size: %d KB'), $fileupload_maxk ).'</p>

				<form enctype="multipart/form-data" action="'.$Fileman->curl().'" method="post">
					<input type="hidden" name="MAX_FILE_SIZE" value="'.($fileupload_maxk*1024).'" />
					<input type="hidden" name="action" value="upload" />';

					for( $i = 0; $i < 3; $i++ )
					{
						$msg_action .= '<input name="uploadfile['.$i.']" type="file" size="40" /><br />';
						//'.T_('Description').':	<input type="text" name="imgdesc['.$i.']" size="50" /><br />';
					}

					$msg_action .= '
					<input type="submit" value="'.T_('Upload !').'" class="search" />
					</form>
					';
			}

		}

		break;
} // }}}


// the top menu and header
require dirname(__FILE__).'/_menutop.php';
echo '<br />'.T_('Current directory').': '.$Fileman->cwd_clickable();
if( $Fileman->is_filtering() )
{
	echo '<br />'.T_('Filter').': ['.$Fileman->get_filter().']';
}
require dirname(__FILE__).'/_menutop_end.php';


?>
<div id="filemanmain">
<?php

// output errors, notes and action messages
if( $Fileman->Messages->count( 'all' ) || isset( $msg_action )
		|| $Messages->count( 'all' ) )
{
	?>
	<div class="panelinfo">
		<?php
		$Messages->display( '', '', true, 'error' );
		$Messages->display( '', '', true, 'note' );
		$Fileman->Messages->display( '', '', true, 'error' );
		$Fileman->Messages->display( '', '', true, 'note' );
		if( isset($msg_action) )
		{
			echo $msg_action;
		}
		?>
	</div>
	<?php
}

?>
<div class="panelblock">
<div class="toolbar">
	<?php
	$rootlist = $Fileman->get_roots();

	if( count($rootlist) > 1 )
	{ // provide list
		echo '<form action="files.php" name="roots" class="toolbaritem">'
					.$Fileman->form_hiddeninputs( false );
		echo '<select name="root" onchange="this.form.submit()">';

		foreach( $rootlist as $lroot )
		{
			echo '<option value="'.$lroot['type'].'_'.$lroot['id'].'"';

			if( $root == $lroot['type'].'_'.$lroot['id'] )
			{
				echo ' selected="selected"';
			}

			echo '>'.format_to_output( $lroot['name'] ).'</option>';
		}
		echo '</select><input type="submit" value="'.T_('Change root').'" />'
					."</form>\n";
	}
	?>

	<form action="files.php" name="search" class="toolbaritem">
		<?php echo $Fileman->form_hiddeninputs() ?>
		<input type="text" name="searchfor" value="--todo--" size="20" />
		<input type="submit" value="<?php echo format_to_output( T_('Search'), 'formvalue' ) ?>" />
	</form>

	<div class="toolbaritem_group">
		<?php
		if( $Fileman->is_filtering() )
		{ // "reset filter" form
		?>
		<form action="files.php" name="unfilter" class="toolbaritem">
			<?php echo $Fileman->form_hiddeninputs( NULL, NULL, false, false ) ?>
			<input type="submit" value="<?php echo format_to_output( T_('No Filter'), 'formvalue' ) ?>" />
		</form>
		<?php
		}
		?>

		<form action="files.php" name="filter" class="toolbaritem">
			<?php echo $Fileman->form_hiddeninputs( NULL, NULL, false, false ) ?>
			<input type="text" name="filter" value="<?php echo format_to_output( $Fileman->get_filter( false ), 'formvalue' ) ?>" size="20" />
			<input type="checkbox" name="filter_regexp" title="<?php echo format_to_output( T_('Filter is regular expression'), 'formvalue' ) ?>" value="1"<?php if( $filter_regexp ) echo ' checked="checked"' ?> />
			<input type="submit" value="<?php echo format_to_output( T_('Filter'), 'formvalue' ) ?>" />
		</form>
	</div>

	<div class="clear"></div>
</div>

<form name="FilesForm" action="files.php" method="post">
<table class="grouped">
<thead>
<tr>
	<th colspan="2" class="nobr">
		<a href="<?php echo $Fileman->get_link_home() ?>"><?php echo $Fileman->get_icon( 'home', 'imgtag' ) ?></a>
		&nbsp;
		<a href="<?php echo $Fileman->get_link_parent() ?>"><?php echo $Fileman->get_icon( 'parent', 'imgtag' ) ?></a>
	</th>
	<th><?php echo $Fileman->link_sort( 'name', /* TRANS: file name */ T_('Name') ) ?></th>
	<th><?php echo $Fileman->link_sort( 'type', /* TRANS: file type */ T_('Type') ) ?></th>
	<th><?php echo $Fileman->link_sort( 'size', /* TRANS: file size */ T_('Size') ) ?></th>
	<th><?php echo $Fileman->link_sort( 'lastm', /* TRANS: file's last change / timestamp */ T_('Last change') ) ?></th>
	<th><?php echo $Fileman->link_sort( 'perms', /* TRANS: file's permissions */ T_('Perms') ) ?></th>
	<th><?php echo /* TRANS: file action, (html) view */ T_('Action') ?></th>
</tr>
</thead>

<tbody>
<?php
param( 'checkall', 'integer', 0 );  // Non-Javascript-CheckAll

$i = 0;
$Fileman->sort();
while( $File = $Fileman->get_File_next() )
{ // loop through all Files
	$i++;

	$link_default_js = 'if( (typeof clickedonlink) == \'undefined\' ){ window.open(\''.$Fileman->get_link_curfile()."', 'fileman_default', 'toolbar=0,resizable=yes,";
	if( $r = $File->get_imgsize( 'widthheight' ) )
	{
		$link_default_js .= 'width='.($r[0]+100).',height='.($r[1]+100);
	}
	$link_default_js .= "')}";
	$link_default_js = '';  // temp. disabled

	?>
	<tr<?php if( !($i%2) ) echo ' class="odd"' ?> onclick="document.getElementsByName('selectedfiles[]')[<?php echo $i-1 ?>].click();">
		<td class="checkbox">
			<input title="<?php echo T_('select this file') ?>" type="checkbox" name="selectedfiles[]" value="<?php echo format_to_output( $File->get_name(), 'formvalue' ) ?>" onclick="document.getElementsByName('selectedfiles[]')[<?php echo $i-1 ?>].click();"<?php if( $checkall ) echo ' checked="checked" '?> />
		</td>
		<td class="icon" onclick="window.location.href = '<?php echo $Fileman->get_link_curfile() ?>'">
			<?php /*echo $i++;*/ echo $Fileman->get_icon( 'cfile', 'imgtag' ) ?>
		</td>
		<td class="filename" onclick="<?php echo $link_default_js ?>">
			<!--<noscript type="text/javascript">--><a onclick="clickedonlink = 1;" href="<?php echo $Fileman->get_link_curfile() ?>"><!--/noscript-->
				<?php
					echo $File->get_name();
					disp_cond( $File->get_imgsize(), ' (%s)' )
				?>
			<!--noscript type="text/javascript"--></a><!--/noscript-->
		</td>
		<td class="type"><?php echo $Fileman->get_File_type() ?></td>
		<td class="size"><?php echo $File->get_nicesize() ?></td>
		<td class="timestamp"><?php echo $File->get_lastmod() ?></td>
		<td class="perms"><?php disp_cond( $Fileman->get_link_curfile_editperm(), '<a href="%s">'.$File->get_perms().'</a>' ) ?></td>
		<td class="actions"><?php
			disp_cond( $Fileman->get_link_curfile_edit(), '<a href="%s">'.$Fileman->get_icon( 'edit', 'imgtag' ).'</a>' );
			disp_cond( $Fileman->get_link_curfile_copymove(), '<a href="%s">'.$Fileman->get_icon( 'copymove', 'imgtag' ).'</a>' );
			disp_cond( $Fileman->get_link_curfile_rename(), '<a href="%s">'.$Fileman->get_icon( 'rename', 'imgtag' ).'</a>' );
			disp_cond( $Fileman->get_link_curfile_delete(), '<a href="%s">'.$Fileman->get_icon( 'delete', 'imgtag' ).'</a>' );
			?></td>
	</tr>
	<?php
}
if( $i == 0 )
{ // "the directory is empty"
	?>
	<tr>
	<td colspan="8" class="error">
	<?php
	echo T_('The directory is empty.');
	if( $Fileman->is_filtering() )
	{
		echo '<br />'.T_('Filter').': ['.$Fileman->get_filter().']';
	}

	?>
	</td>
	</tr>
	<?php
}
?>
</tbody>

<?php
if( $i != 0 )
{{{ // Footer with "check all", "with selected: .."
?>
<tfoot>
<tr class="group"><td colspan="8">
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
</td></tr>
</tfoot>
<?php
}}}
?>
</table>
</form>

<div class="toolbar">
	<?php
	param( 'options_show', 'integer', 0 );
	?>
	<form class="toolbaritem" action="files.php" method="post">
		<div id="options_list"<?php if( !$options_show ) echo ' style="display:none"' ?>>
			<?php echo T_('Sort directories at top') ?>
			<input type="checkbox" name="option_dirsattop" value="1"<?php if( $UserSettings->get('fm_dirsattop') ) echo ' checked="checked"' ?> />
			<br />
			<?php echo T_('Show hidden files') ?>
			<input type="checkbox" name="option_showhidden" value="1"<?php if( $UserSettings->get('fm_showhidden') ) echo ' checked="checked"' ?> />
			<br />
			<?php echo T_('File permissions like &quot;ls -l&quot;') ?>
			<input type="checkbox" name="option_permlikelsl" value="1"<?php if( $UserSettings->get('fm_permlikelsl') ) echo ' checked="checked"' ?> />
			<br />
			<?php echo T_('Recursive size of directories') ?>
			<input type="checkbox" name="option_recursivedirsize" value="1"<?php if( $UserSettings->get('fm_recursivedirsize') ) echo ' checked="checked"' ?> />
			<br />

			<?php echo $Fileman->form_hiddeninputs() ?>
			<input type="hidden" name="action" value="update_settings" />
			<input type="hidden" name="options_show" value="1" />
			<div class="input">
			<input type="submit" value="<?php echo T_('Update !') ?>" />
			</div>
		</div>

		<noscript type="text/javascript">
		<a id="options_toggle" href="<?php echo url_add_param( $Fileman->curl(), ( !$options_show ? 'options_show=1' : '' ) ) ?>"><?php
			echo ( $options_show ) ? T_('hide options') : T_('show options') ?></a>
		</noscript>

		<script type="text/javascript">
		<!--
			document.write( '<a id="options_toggle" href="javascript:toggle_options()"><?php echo T_("show options") ?></a>' )

			showoptions = <?php echo ($options_show) ? 'false' : 'true' ?>;
			toggle_options();

			function toggle_options()
			{
				if( showoptions )
				{
					var replace = document.createTextNode('<?php echo /* Warning! This is a Javascript string! */ T_('show options') ?>');
					var display_list = 'none';
					showoptions = false;
				}
				else
				{
					var replace = document.createTextNode('<?php echo /* Warning! This is a Javascript string! */ T_('hide options') ?>');
					var display_list = 'inline';
					showoptions = true;
				}
				document.getElementById('options_list').style.display = display_list;
				document.getElementById('options_toggle').replaceChild(replace, document.getElementById( 'options_toggle' ).firstChild);
			}
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
		<input type="submit" value="<?php echo format_to_output( T_('Create new'), 'formvalue' ) ?>" />
		<?php echo $Fileman->form_hiddeninputs() ?>
		<input type="hidden" name="action" value="createnew" />
	</form>
</div>
</div>

</div>
<?php
require( dirname(__FILE__). '/_footer.php' );
#echo replacePngTags( ob_get_clean(), $img_url);
?>
