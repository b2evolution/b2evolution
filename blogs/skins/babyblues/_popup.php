<?php
	if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

	while( $Item = $MainList->get_item() ) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php locale_charset() ?>" />
	<title><?php $Blog->disp('name', 'htmlhead') ?> - feedback on '<?php $Item->title() ?>'</title>
	<base href="<?php skinbase(); // Base URL for this skin. You need this to fix relative links! ?>" />
	<style type="text/css" media="screen">
		@import url( 'style.css' );
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
		require( dirname(__FILE__).'/_feedback.php' );
?>
</div>

<div><strong><span style="color: #40B4C3">::</span> <a href="javascript:window.close()">close this window</a></strong></div>

</body>
</html>
<?php } ?>