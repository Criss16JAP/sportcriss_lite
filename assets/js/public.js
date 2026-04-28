/**
 * SportCriss Lite – JS público
 * Registra impresiones de anuncios via IntersectionObserver.
 * scl_pub.ajax_url es localizado desde Scl_Public::enqueue_assets().
 */
document.addEventListener( 'DOMContentLoaded', function() {
	var anuncios = document.querySelectorAll( '.scl-ad[data-ad-id]' );
	if ( ! anuncios.length ) return;

	var observer = new IntersectionObserver( function( entries ) {
		entries.forEach( function( entry ) {
			if ( ! entry.isIntersecting ) return;
			var el    = entry.target;
			if ( el.dataset.tracked ) return;
			el.dataset.tracked = '1';

			var formData = new FormData();
			formData.append( 'action',  'scl_track_ad' );
			formData.append( 'ad_id',   el.getAttribute( 'data-ad-id' ) );
			formData.append( 'tipo',    'impresion' );
			formData.append( 'nonce',   el.getAttribute( 'data-nonce' ) );
			formData.append( 'pagina',  window.location.href.substring( 0, 500 ) );

			fetch( scl_pub.ajax_url, { method: 'POST', body: formData } );
			observer.unobserve( el );
		} );
	}, { threshold: 0.5 } );

	anuncios.forEach( function( el ) { observer.observe( el ); } );
} );
