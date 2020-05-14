<!DOCTYPE html>
<html lang="<?php echo ( empty( $locale_lang ) ? 'en' : $locale_lang ); ?>">

	<head>
		<base href="<?php echo get_script_baseurl(); ?>">
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>

	<body>
		<div style="background-color: #fdd; padding: 1ex; margin-bottom: 1ex;">
			<h1 style="color:#f00;">503 The server is currently overloaded.</h1>
			<p>Please try again in a few minutes.</p>
		</div>

		<div style="background-color: #ddd; padding: 1ex; margin-bottom: 1ex;">
			<h3>Error details:</h3> 
			<?php echo  $additional_info;?>
		</div>

	</body>
</html>