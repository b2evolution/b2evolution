<?php
	/*
	 * This is the template that displays the contents of the feedback popup
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display the stats, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?disp=stats
	 */
	if(substr(basename($_SERVER['SCRIPT_FILENAME']),0,1)=='_')
		die("Please, do not access this page directly.");

	while( $Item = $MainList->get_item() ) 
	{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php	$Blog->disp('name', 'htmlhead') ?> - feedback on '<?php $Item->title( '', '', false, 'htmlhead' ) ?>'</title>
	<base href="<?php skinbase(); // Base URL for this skin. You need this to fix relative links! ?>" />
	<style type="text/css" media="screen">
		@import url( 'layout2b.css' );
	</style>
	<link rel="stylesheet" type="text/css" media="print" href="print.css" />
</head>
<body>

<div id="contentcomments">

<?php
	// this includes the feedback and a form to add a new comment depending on request
	$disp_comments = 1;					// Display the comments if requested
	$disp_comment_form = 1;			// Display the comments form if comments requested
	$disp_trackbacks = 1;				// Display the trackbacks if requested
	$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
	$disp_pingbacks = 1;				// Display the pingbacks if requested
	require( dirname(__FILE__).'/_feedback.php)' );
?>
</div>

<div><strong><span style="color: #0099CC">::</span> <a href="javascript:window.close()">close this window</a></strong></div>

</body>
</html>
<?php } ?>