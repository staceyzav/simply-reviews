( function () {
	'use strict';

	document.querySelectorAll( '.sr-slider' ).forEach( function ( slider ) {
		var viewport    = slider.querySelector( '.sr-viewport' );
		var slidesTrack = slider.querySelector( '.sr-slides' );
		var slides      = Array.from( slider.querySelectorAll( '.sr-slide' ) );
		var dots        = Array.from( slider.querySelectorAll( '.sr-dot' ) );
		var current     = 0;
		var autoplay    = parseInt( slider.dataset.autoplay, 10 ) || 0;
		var timer;

		if ( slides.length <= 1 ) return;

		function setWidths() {
			var w = slider.offsetWidth;
			slides.forEach( function ( slide ) { slide.style.width = w + 'px'; } );
			setHeight( current );
		}

		function setHeight( index ) {
			var h = slides[ index ] ? slides[ index ].offsetHeight : 0;
			if ( h ) viewport.style.height = h + 'px';
		}

		setWidths();
		window.addEventListener( 'resize', setWidths );

		function goTo( index ) {
			dots[ current ] && dots[ current ].classList.remove( 'is-active' );

			current = ( ( index % slides.length ) + slides.length ) % slides.length;

			slidesTrack.style.transform = 'translateX(-' + ( current * slider.offsetWidth ) + 'px)';
			dots[ current ] && dots[ current ].classList.add( 'is-active' );
			setHeight( current );
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

// ── YouTube video lightbox ────────────────────────────────────────────────

( function () {
	'use strict';

	var lightbox, frame;

	function buildLightbox() {
		lightbox = document.createElement( 'div' );
		lightbox.className = 'sr-lightbox';
		lightbox.setAttribute( 'hidden', '' );
		lightbox.setAttribute( 'role', 'dialog' );
		lightbox.setAttribute( 'aria-modal', 'true' );
		lightbox.setAttribute( 'aria-label', 'Video review' );
		lightbox.innerHTML =
			'<div class="sr-lightbox__overlay"></div>' +
			'<div class="sr-lightbox__inner">' +
				'<button class="sr-lightbox__close" aria-label="Close video">&times;</button>' +
				'<div class="sr-lightbox__frame"></div>' +
			'</div>';
		document.body.appendChild( lightbox );

		frame = lightbox.querySelector( '.sr-lightbox__frame' );

		lightbox.querySelector( '.sr-lightbox__overlay' ).addEventListener( 'click', closeLightbox );
		lightbox.querySelector( '.sr-lightbox__close' ).addEventListener( 'click', closeLightbox );
	}

	function openLightbox( ytId ) {
		if ( ! lightbox ) buildLightbox();
		frame.innerHTML =
			'<iframe src="https://www.youtube.com/embed/' + ytId +
			'?autoplay=1&rel=0" allow="autoplay; fullscreen" allowfullscreen></iframe>';
		lightbox.removeAttribute( 'hidden' );
		document.body.style.overflow = 'hidden';
		lightbox.querySelector( '.sr-lightbox__close' ).focus();
	}

	function closeLightbox() {
		if ( ! lightbox ) return;
		lightbox.setAttribute( 'hidden', '' );
		frame.innerHTML = '';
		document.body.style.overflow = '';
	}

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' ) closeLightbox();
	} );

	document.addEventListener( 'click', function ( e ) {
		var thumb = e.target.closest( '.sr-video-thumb' );
		if ( ! thumb ) return;
		openLightbox( thumb.dataset.ytId );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key !== 'Enter' && e.key !== ' ' ) return;
		var thumb = e.target.closest( '.sr-video-thumb' );
		if ( ! thumb ) return;
		e.preventDefault();
		openLightbox( thumb.dataset.ytId );
	} );
} )();
