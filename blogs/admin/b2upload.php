<?php
/**
 * b2 File Upload
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 * @author original hack by shockingbird.com
 */

/**
 * Includes:
 */
require_once (dirname(__FILE__).'/_header.php');

// Check permissions:
$current_User->check_perm( 'upload', 'any', true );

?><html xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php echo T_('b2evo') ?> &gt; <?php echo T_('upload images/files') ?></title>
	<link href="variation.css" rel="stylesheet" type="text/css" title="Variation" />
	<link href="desert.css" rel="alternate stylesheet" type="text/css" title="Desert" />
	<link href="legacy.css" rel="alternate stylesheet" type="text/css" title="Legacy" />
	<?php if( is_file( dirname(__FILE__).'/custom.css' ) ) { ?>
	<link href="custom.css" rel="alternate stylesheet" type="text/css" title="Custom" />
	<?php } ?>
	<script type="text/javascript" src="styleswitcher.js"></script>
	<script type="text/javascript">
	<!-- // idocs.com's popup tutorial rules !
	function targetopener(blah, closeme, closeonly) {
		if (! (window.focus && window.opener))return true;
		window.opener.focus();
		if (! closeonly)window.opener.document.post.content.value += blah;
		if (closeme)window.close();
		return false;
	}
	//-->
	</script>
</head>
<body>
<div class="panelblock">
<?php

if (!isset($_POST['submit']))
{
	$i = explode(" ",$fileupload_allowedtypes);
	$i = implode(", ",array_slice($i, 1, count($i)-2));
	?>
	<p><strong><?php echo T_('File upload') ?></strong></p>
	<p><?php echo T_('Allowed file types:'), $i ?></p>
	<p><?php printf( T_('Maximum allowed file size: %d KB'), $fileupload_maxk ); ?></p>
	<form action="b2upload.php" method="post" enctype="multipart/form-data">
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $fileupload_maxk*1024 ?>" />
	<input type="file" name="img1" size="30" class="uploadform" />

	<p><?php echo T_('Description') ?>:<br />
	<input type="text" name="imgdesc" size="30" class="uploadform" /></p>
	
	<input type="submit" name="submit" value="<?php echo T_('Upload !') ?>" class="search" />
	</form>
</div>
</body>
</html>
<?php exit();
}

 //Makes sure they choose a file

//print_r($HTTP_POST_FILES);
//die();

if (!empty($HTTP_POST_VARS)) { //$img1_name != "") {

	$imgalt = (isset($HTTP_POST_VARS['imgalt'])) ? $HTTP_POST_VARS['imgalt'] : '';

	$img1_name = (strlen($imgalt)) ? $HTTP_POST_VARS['imgalt'] : $HTTP_POST_FILES['img1']['name'];
	$img1_type = (strlen($imgalt)) ? $HTTP_POST_VARS['img1_type'] : $HTTP_POST_FILES['img1']['type'];
	$img1_size = (strlen($imgalt)) ? $HTTP_POST_VARS['img1_size'] : $HTTP_POST_FILES['img1']['size'];
	$imgdesc = str_replace('"', '&amp;quot;', $HTTP_POST_VARS['imgdesc']);

	$imgtype = explode(".",$img1_name);
	$imgtype = " ".$imgtype[count($imgtype)-1]." ";

	if (!ereg(strtolower($imgtype), strtolower($fileupload_allowedtypes))) {
	    die(sprintf( T_('File %s: type %s is not allowed.'), $img1_name, $imgtype ));
	}

	if (strlen($imgalt)) {
		$pathtofile = $fileupload_realpath."/".$imgalt;
		$img1 = $HTTP_POST_VARS['img1'];
	} else {
		$pathtofile = $fileupload_realpath."/".$img1_name;
		$img1 = $HTTP_POST_FILES['img1']['tmp_name'];
	}

	// makes sure not to upload duplicates, rename duplicates
	$i = 1;
	$pathtofile2 = $pathtofile;
	$tmppathtofile = $pathtofile2;
	$img2_name = $img1_name;

	while (file_exists($pathtofile2)) {
	    $pos = strpos($tmppathtofile, '.'.trim($imgtype));
	    $pathtofile_start = substr($tmppathtofile, 0, $pos);
	    $pathtofile2 = $pathtofile_start.'_'.zeroise($i++, 2).'.'.trim($imgtype);
	    $img2_name = explode('/', $pathtofile2);
	    $img2_name = $img2_name[count($img2_name)-1];
	}

	if (file_exists($pathtofile) && !strlen($imgalt)) {
		$i = explode(" ",$fileupload_allowedtypes);
		$i = implode(", ",array_slice($i, 1, count($i)-2));
		move_uploaded_file($img1, $pathtofile2) 
		 or die( T_('Couldn\'t upload your file to:').' '.$pathtofile2);
	
	// duplicate-renaming function contributed by Gary Lawrence Murphy
	?>
	<p><strong><?php echo T_('Duplicate File?') ?></strong></p>
	<p><strong><em><?php printf( T_('The filename "%s" already exists!'), $img1_name ); ?></em></strong></p>
	<p><?php printf( T_('Filename "%s" moved to "%s"'), $img1, $pathtofile2.'/'.$img2_name ); ?></p>
	<p><?php echo T_('Confirm or rename:') ?></p>
	<form action="b2upload.php" method="post" enctype="multipart/form-data">
	<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $fileupload_maxk*1024 ?>" />
	<input type="hidden" name="img1_type" value="<?php echo $img1_type;?>" />
	<input type="hidden" name="img1_size" value="<?php echo $img1_size;?>" />
	<input type="hidden" name="img1_name" value="<?php echo $img2_name;?>" />
	<input type="hidden" name="img1" value="<?php echo $pathtofile2;?>" />
	<?php echo T_('Alternate name') ?>:<br /><input type="text" name="imgalt" size="30" class="uploadform" value="<?php echo $img2_name;?>" /><br />
	<br />
	<?php echo T_('Description') ?>:<br /><input type="text" name="imgdesc" size="30" class="uploadform" value="<?php echo $imgdesc;?>" />
	<br />
	<p><input type="submit" name="submit" value="<?php echo T_('Confirm !') ?>" class="search" /></p>
	</form>
</div>
</body>
</html><?php die();

	}

	if (!strlen($imgalt)) {
		move_uploaded_file($img1, $pathtofile) //Path to your images directory, chmod the dir to 777
		 or die( T_('Couldn\'t upload your file to:').' '.$pathtofile);
	} else {
		rename($img1, $pathtofile)
		or die( T_('Couldn\'t upload your file to:').' '.$pathtofile);
	}

}


if( ereg('image/', $img1_type) )
{ // uploaded file is an image
	$piece_of_code = "&lt;img src=&quot;$fileupload_url/$img1_name&quot;";
	if( $img_dimensions = getimagesize( $pathtofile ) )
	{ // add 'width="xx" height="xx"
		$piece_of_code .= ' width=&quot;'.$img_dimensions[0].'&quot; height=&quot;'.$img_dimensions[1].'&quot;';
	}
	$piece_of_code .= ' alt=&quot;'.$imgdesc.'&quot; /&gt;';
}
else
{
	$piece_of_code = "&lt;a href=&quot;$fileupload_url/$img1_name&quot; title=&quot;$imgdesc&quot; /&gt;$imgdesc&lt;/a&gt;"; 
}

?>

<p><strong><?php echo T_('File uploaded !') ?></strong></p>
<p><?php printf( T_('Your file <strong>"%s"</strong> was uploaded successfully !'), $img1_name ); ?></p>
<p><?php echo T_('Here\'s the code to display it:') ?></p>
<p><form action="b2upload.php">
<!--<textarea cols="25" rows="3" wrap="virtual"><?php echo "&lt;img src=&quot;$fileupload_url/$img1_name&quot; border=&quot;0&quot; alt=&quot;&quot; /&gt;"; ?></textarea>-->
<input type="text" name="imgpath" value="<?php echo $piece_of_code; ?>" size="40" class="large" /><br />
<input type="button" name="close" value="<?php echo T_('Add the code to your post !') ?>" class="search" onClick="targetopener('<?php echo $piece_of_code; ?>')" />
</form>
</p>
<p><strong><?php echo T_('Image Details') ?></strong>: <br />
<?php echo T_('Name') ?>: 
<?php echo "$img1_name"; ?>
<br />
<?php echo T_('Size') ?>: 
<?php echo round($img1_size/1024,2); ?> KB
<br />
<?php echo T_('Type') ?>: 
<?php echo "$img1_type"; ?>
</p>
<p align="right">
<form action="b2upload.php">
<input type="button" name="close" value="<?php echo T_('Close this window') ?>" class="search" onClick="window.close()" />
</form>
</p>
</div>
</body>
</html>