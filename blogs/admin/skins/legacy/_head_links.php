<link href="variation.css" rel="stylesheet" type="text/css" title="Variation" />
<link href="desert.css" rel="alternate stylesheet" type="text/css" title="Desert" />
<link href="legacy.css" rel="alternate stylesheet" type="text/css" title="Legacy" />
<?php if( is_file( dirname(__FILE__).'/custom.css' ) ) { ?>
<link href="custom.css" rel="alternate stylesheet" type="text/css" title="Custom" />
<?php } ?>
<script type="text/javascript" src="styleswitcher.js"></script>
<?php
if( $mode == 'sidebar' )
{ // Include CSS overrides for sidebar: ?>
	<link href="sidebar.css" rel="stylesheet" type="text/css" />
<?php
}
?>
