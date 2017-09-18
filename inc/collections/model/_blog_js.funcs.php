<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Require this file because function evoAlert() is used here
require_js( 'functions.js', 'blog', false, true );

?>
<script type="text/javascript">
jQuery( document ).on( 'click', 'a.evo_post_fav_btn', function()
{
	var me = jQuery( this );
	var favorite_link = jQuery( this );
	var coll_urlname = favorite_link.data( 'coll' );
	var favorite_status = favorite_link.data( 'favorite' );

	if( coll_urlname != undefined && favorite_status != undefined )
	{
		evo_rest_api_request( 'collections/' + coll_urlname + '/favorite', { setting: favorite_status },
				function( data )
				{
					if( data.status == 'ok' )
					{
						var icon_container = jQuery( 'span', favorite_link );
						switch( parseInt( data.setting ) )
						{
							case 1:
								//icon_container.removeClass( 'fa-star-o' );
								//icon_container.addClass( 'fa-star' );
								me.html( '<?php echo format_to_js( get_icon( 'star_on', 'imgtag', array( 'class' => 'coll-fav' ) ) );?>' );
								break;

							default:
								//icon_container.removeClass( 'fa-star' );
								//icon_container.addClass( 'fa-star-o' );
								me.html( '<?php echo format_to_js( get_icon( 'star_off', 'imgtag', array( 'class' => 'coll-fav' ) ) );?>' );
						}
						favorite_link.data( 'favorite', data.setting == 1 ? 0 : 1 );
					}
					else
					{
						if( data.errorMsg )
						{
							evoAlert( errorMsg );
						}
					}
				}, 'PUT' );
	}

	return false;
} );
</script>