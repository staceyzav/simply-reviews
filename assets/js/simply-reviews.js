( function () {
	'use strict';

	document.querySelectorAll( '.sr-slider' ).forEach( function ( slider ) {
		var slidesTrack = slider.querySelector( '.sr-slides' );
		var slides      = Array.from( slider.querySelectorAll( '.sr-slide' ) );
		var dots        = Array.from( slider.querySelectorAll( '.sr-dot' ) );
		var current     = 0;
		var autoplay    = parseInt( slider.dataset.autoplay, 10 ) || 0;
		var timer;

		if ( slides.length <= 1 ) return;

		function goTo( index ) {
			dots[ current ] && dots[ current ].classList.remove( 'is-active' );

			current = ( ( index % slides.length ) + slides.length ) % slides.length;

			slidesTrack.style.transform = 'translateX(-' + ( current * 100 ) + '%)';
			dots[ current ] && dots[ current ].classList.add( 'is-active' );
		}

		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				clearInterval( timer );
				goTo( parseInt( this.dataset.slide, 10 ) );
			} );
		} );

		if ( autoplay > 0 ) {
			timer = setInterval( function () {
				goTo( current + 1 );
			}, autoplay * 1000 );

			slider.addEventListener( 'mouseenter', function () { clearInterval( timer ); } );
			slider.addEventListener( 'mouseleave', function () {
				timer = setInterval( function () { goTo( current + 1 ); }, autoplay * 1000 );
			} );
		}
	} );
} )();
