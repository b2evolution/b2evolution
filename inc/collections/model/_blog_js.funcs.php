<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Require this file because function evoAlert() is used here
require_js( 'functions.js', 'blog', false, true );

?>
<script type="text/javascript">

function toggleFavorite( obj, coll_urlname )
{
	var me = jQuery( obj );

	jQuery.ajax( {
		url: '<?php echo get_restapi_url(); ?>collections/' + coll_urlname + '/favorite',
		method: 'POST'
	} ).done( function( data )
		{
			if( data.status == 'ok' )
			{
				switch( parseInt( data.setting ) )
				{
					case 1:
						me.html( '<?php echo get_icon( 'star_on', 'imgtag', array( 'class' => 'coll-fav' ) );?>' );
						break;

					default:
						me.html( '<?php echo get_icon( 'star_off', 'imgtag', array( 'class' => 'coll-fav' ) );?>' );
				}
			}
			else
			{
				if( data.errorMsg )
				{
					evoAlert( errorMsg );
				}
			}
		});

	return false;
}

</script>