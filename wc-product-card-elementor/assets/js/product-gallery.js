/**
 * PDP Product Gallery — vanilla JS.
 *
 * Features:
 * - Slide navigation (thumbnail click, prev/next buttons)
 * - Mobile swipe (touch events, momentum-free but responsive)
 * - Lightbox for images and YouTube video (nocookie embed)
 * - Image zoom (click to zoom, drag to pan, double-tap/click to reset)
 * - Pinch-zoom on mobile (two-finger zoom + pan)
 * - Focus trap inside lightbox
 * - Keyboard: Escape closes, ArrowLeft/Right navigates
 * - YouTube iframe loaded lazily after interaction
 * - aria-hidden, aria-current, aria-live managed throughout
 * - Scoped per gallery instance — multiple galleries on one page work independently
 *
 * Architecture:
 * - One WCPCEGallery instance per .wcpce-gallery element
 * - Initialised on DOMContentLoaded + Elementor frontend/init
 * - No global selectors, no shared state between instances
 *
 * @package WC_Product_Card_Elementor
 * @since 2.0.0
 */

( function () {
	'use strict';

	/* =========================================================================
	 * Constants
	 * ======================================================================= */

	var SWIPE_THRESHOLD    = 40;   // px horizontal movement to trigger slide
	var SWIPE_MAX_VERTICAL = 80;   // px vertical movement that cancels a swipe
	var ZOOM_MAX           = 4;    // maximum zoom level
	var ZOOM_MIN           = 1;    // minimum zoom level (no zoom)
	var ZOOM_STEP_CLICK    = 2.5;  // zoom level on single click/tap
	var DOUBLE_TAP_MS      = 300;  // ms window for double-tap detection
	var YT_NOCOOKIE_BASE   = 'https://www.youtube-nocookie.com/embed/';

	/* =========================================================================
	 * Utility helpers
	 * ======================================================================= */

	/**
	 * Returns all focusable elements inside a container.
	 *
	 * @param {Element} container
	 * @returns {Element[]}
	 */
	function getFocusable( container ) {
		return Array.prototype.slice.call(
			container.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]), ' +
				'select:not([disabled]), textarea:not([disabled]), ' +
				'[tabindex]:not([tabindex="-1"])'
			)
		);
	}

	/**
	 * Clamps a number between min and max.
	 *
	 * @param {number} value
	 * @param {number} min
	 * @param {number} max
	 * @returns {number}
	 */
	function clamp( value, min, max ) {
		return Math.min( Math.max( value, min ), max );
	}

	/**
	 * Returns the distance between two touch points.
	 *
	 * @param {Touch} t1
	 * @param {Touch} t2
	 * @returns {number}
	 */
	function touchDistance( t1, t2 ) {
		var dx = t1.clientX - t2.clientX;
		var dy = t1.clientY - t2.clientY;
		return Math.sqrt( dx * dx + dy * dy );
	}

	/**
	 * Returns the midpoint between two touch points.
	 *
	 * @param {Touch} t1
	 * @param {Touch} t2
	 * @returns {{ x: number, y: number }}
	 */
	function touchMidpoint( t1, t2 ) {
		return {
			x: ( t1.clientX + t2.clientX ) / 2,
			y: ( t1.clientY + t2.clientY ) / 2,
		};
	}

	/* =========================================================================
	 * WCPCEGallery — one instance per .wcpce-gallery element
	 * ======================================================================= */

	/**
	 * @param {Element} root The .wcpce-gallery element.
	 */
	function WCPCEGallery( root ) {
		this.root        = root;
		this.widgetId    = root.getAttribute( 'data-widget-id' ) || '';
		this.totalSlides = parseInt( root.getAttribute( 'data-total' ) || '0', 10 );
		this.imageLightboxOn = root.getAttribute( 'data-lightbox' ) === '1';
		this.videoLightboxOn = root.getAttribute( 'data-video-lightbox' ) === '1';
		this.lightboxOn  = this.imageLightboxOn || this.videoLightboxOn;
		this.zoomOn      = root.getAttribute( 'data-zoom' ) === '1';
		this.currentIdx  = 0;

		// DOM references.
		this.track      = root.querySelector( '.wcpce-gallery__slides-track' );
		this.slides     = root.querySelectorAll( '.wcpce-gallery__slide' );
		this.thumbBtns  = root.querySelectorAll( '.wcpce-gallery__thumb-btn' );
		this.prevBtn    = root.querySelector( '.wcpce-gallery__nav--prev' );
		this.nextBtn    = root.querySelector( '.wcpce-gallery__nav--next' );
		// The lightbox is rendered OUTSIDE .wcpce-gallery (to escape stacking
		// context) — find it by its ID which includes the widget ID.
		this.lightbox   = document.getElementById( 'wcpce-gallery-lb-' + this.widgetId );

		// Lightbox inner references (may be null when lightbox is off).
		this.lbContent  = this.lightbox ? this.lightbox.querySelector( '.wcpce-gallery__lightbox-content' ) : null;
		this.lbClose    = this.lightbox ? this.lightbox.querySelector( '.wcpce-gallery__lightbox-close' ) : null;
		this.lbPrev     = this.lightbox ? this.lightbox.querySelector( '.wcpce-gallery__lightbox-nav--prev' ) : null;
		this.lbNext     = this.lightbox ? this.lightbox.querySelector( '.wcpce-gallery__lightbox-nav--next' ) : null;
		this.lbBackdrop = this.lightbox ? this.lightbox.querySelector( '.wcpce-gallery__lightbox-backdrop' ) : null;

		// Zoom state.
		this.zoomLevel  = 1;
		this.panX       = 0;
		this.panY       = 0;
		this.isDragging = false;
		this.dragStart  = { x: 0, y: 0 };
		this.panStart   = { x: 0, y: 0 };
		this.zoomImg    = null;
		this.zoomDocumentEventsBound = false;

		// Pinch state.
		this.pinchStart       = 0;
		this.pinchZoomStart   = 1;
		this.pinchMidStart    = { x: 0, y: 0 };
		this.pinchPanStart    = { x: 0, y: 0 };

		// Double-tap state.
		this.lastTapTime = 0;
		this.lastTapX    = 0;
		this.lastTapY    = 0;

		// Swipe state.
		this.swipeTouchStart = null;
		this.swipeStartX     = 0;
		this.swipeStartY     = 0;
		this.swipeActive     = false;

		// The element to return focus to when lightbox closes.
		this.lightboxOpener = null;

		this.init();
	}

	/* -----------------------------------------------------------------------
	 * Initialisation
	 * --------------------------------------------------------------------- */

	WCPCEGallery.prototype.init = function () {
		if ( this.totalSlides < 2 ) {
			// Single slide: only wire up lightbox click if enabled.
			if ( this.lightboxOn ) {
				this.bindSingleSlideLightbox();
			}
			return;
		}

		this.bindThumbnails();
		this.bindNav();
		this.bindSwipe();
		this.bindKeyboard();

		if ( this.lightboxOn ) {
			this.bindLightbox();
		}
	};

	/* -----------------------------------------------------------------------
	 * Slide switching
	 * --------------------------------------------------------------------- */

	/**
	 * Switches to the slide at the given index.
	 *
	 * @param {number} index
	 * @param {boolean} [announce=true] Whether to update aria-live.
	 */
	WCPCEGallery.prototype.goTo = function ( index, announce ) {
		if ( index === this.currentIdx ) {
			return;
		}

		var prev = this.currentIdx;
		this.currentIdx = clamp( index, 0, this.totalSlides - 1 );

		// Stop any playing YouTube video in the slide we're leaving.
		this.stopVideoInSlide( this.slides[ prev ] );

		// Move the track.
		this.track.style.transform = 'translateX(-' + ( this.currentIdx * 100 ) + '%)';

		// Update slide visibility semantics and manage focusability of controls
		// in hidden slides.
		for ( var i = 0; i < this.slides.length; i++ ) {
			var isActive = i === this.currentIdx;
			this.slides[ i ].classList.toggle( 'wcpce-gallery__slide--active', isActive );
			this.slides[ i ].setAttribute( 'aria-hidden', isActive ? 'false' : 'true' );
			if ( isActive ) {
				this.slides[ i ].removeAttribute( 'inert' );
			} else {
				this.slides[ i ].setAttribute( 'inert', '' );
			}

			var playBtn = this.slides[ i ].querySelector( '.wcpce-gallery__play-btn' );
			if ( playBtn ) {
				if ( isActive ) {
					playBtn.removeAttribute( 'tabindex' );
				} else {
					playBtn.setAttribute( 'tabindex', '-1' );
				}
			}
		}

		// Update thumbnail aria-current.
		for ( var j = 0; j < this.thumbBtns.length; j++ ) {
			var isThumbActive = j === this.currentIdx;
			this.thumbBtns[ j ].setAttribute( 'aria-current', isThumbActive ? 'true' : 'false' );
			this.thumbBtns[ j ].closest( '.wcpce-gallery__thumb' ).classList.toggle(
				'wcpce-gallery__thumb--active', isThumbActive
			);
		}

		// Announce slide change via aria-live (on the track).
		if ( announce !== false ) {
			this.track.setAttribute( 'aria-label',
				this.formatSlideLabel( this.currentIdx + 1, this.totalSlides )
			);
		}

	};

	/**
	 * Returns a localised slide label string.
	 *
	 * @param {number} current 1-based index.
	 * @param {number} total
	 * @returns {string}
	 */
	WCPCEGallery.prototype.formatSlideLabel = function ( current, total ) {
		return 'Afbeelding ' + current + ' van ' + total;
	};

	WCPCEGallery.prototype.prev = function () {
		this.goTo( this.currentIdx > 0 ? this.currentIdx - 1 : this.totalSlides - 1 );
	};

	WCPCEGallery.prototype.next = function () {
		this.goTo( this.currentIdx < this.totalSlides - 1 ? this.currentIdx + 1 : 0 );
	};

	/* -----------------------------------------------------------------------
	 * Video helpers
	 * --------------------------------------------------------------------- */

	/**
	 * Stops any YouTube iframe playing inside a slide.
	 *
	 * Replaces the iframe src with an empty string to stop playback without
	 * removing the element from the DOM.
	 *
	 * @param {Element} slide
	 */
	WCPCEGallery.prototype.stopVideoInSlide = function ( slide ) {
		if ( ! slide ) {
			return;
		}
		var iframe = slide.querySelector( 'iframe.wcpce-gallery__yt-iframe' );
		if ( iframe ) {
			iframe.src = '';
		}
	};

	/**
	 * Builds the YouTube nocookie embed URL for a video ID.
	 *
	 * @param {string} youtubeId
	 * @returns {string}
	 */
	WCPCEGallery.prototype.buildYouTubeUrl = function ( youtubeId ) {
		return YT_NOCOOKIE_BASE + encodeURIComponent( youtubeId ) + '?autoplay=1&rel=0';
	};

	/* -----------------------------------------------------------------------
	 * Thumbnail binding
	 * --------------------------------------------------------------------- */

	WCPCEGallery.prototype.bindThumbnails = function () {
		var self = this;
		for ( var i = 0; i < this.thumbBtns.length; i++ ) {
			( function ( btn, idx ) {
				btn.addEventListener( 'click', function () {
					self.goTo( idx );
				} );
			} )( this.thumbBtns[ i ], i );
		}
	};

	/* -----------------------------------------------------------------------
	 * Prev / Next nav buttons
	 * --------------------------------------------------------------------- */

	WCPCEGallery.prototype.bindNav = function () {
		var self = this;
		if ( this.prevBtn ) {
			this.prevBtn.addEventListener( 'click', function () {
				self.prev();
			} );
		}
		if ( this.nextBtn ) {
			this.nextBtn.addEventListener( 'click', function () {
				self.next();
			} );
		}
	};

	/* -----------------------------------------------------------------------
	 * Swipe (touch)
	 * --------------------------------------------------------------------- */

	WCPCEGallery.prototype.bindSwipe = function () {
		var self       = this;
		// Bind swipe to the slides container, not the stage wrapper.
		// Stage is now a flex row (image + nav buttons); swipe only on the image area.
		var stage      = this.root.querySelector( '.wcpce-gallery__slides' );
		var trackWidth = 0; // Cached slide width for real-time drag offset.
		if ( ! stage ) {
			return;
		}

		stage.addEventListener( 'touchstart', function ( e ) {
			// Only handle single-finger touches (two fingers = pinch in lightbox).
			if ( e.touches.length !== 1 ) {
				return;
			}
			self.swipeTouchStart = e.touches[ 0 ];
			self.swipeStartX     = e.touches[ 0 ].clientX;
			self.swipeStartY     = e.touches[ 0 ].clientY;
			self.swipeActive     = false; // Not confirmed horizontal yet.
			self.swipeConfirmed  = false;
			trackWidth           = self.track.parentElement ? self.track.parentElement.offsetWidth : 0;
		}, { passive: true } );

		stage.addEventListener( 'touchmove', function ( e ) {
			if ( ! self.swipeTouchStart || e.touches.length !== 1 ) {
				return;
			}
			var dx = e.touches[ 0 ].clientX - self.swipeStartX;
			var dy = e.touches[ 0 ].clientY - self.swipeStartY;

			// Direction not yet confirmed — wait for clear horizontal intent.
			if ( ! self.swipeConfirmed ) {
				if ( Math.abs( dx ) < 6 && Math.abs( dy ) < 6 ) {
					return; // Too small to determine direction.
				}
				if ( Math.abs( dy ) > Math.abs( dx ) ) {
					// Vertical — hand off to browser scroll, cancel swipe.
					self.swipeTouchStart = null;
					return;
				}
				// Confirmed horizontal swipe.
				self.swipeConfirmed = true;
				self.swipeActive    = true;
				self.track.classList.add( 'is-dragging' );
			}

			// Prevent page scroll during horizontal swipe.
			e.preventDefault();

			// Real-time drag feedback — shift track by drag distance.
			if ( trackWidth > 0 ) {
				var baseOffset = -( self.currentIdx * 100 );
				var dragPct    = ( dx / trackWidth ) * 100;
				// Rubber-band resistance at edges.
				var atStart = self.currentIdx === 0 && dx > 0;
				var atEnd   = self.currentIdx === self.totalSlides - 1 && dx < 0;
				if ( atStart || atEnd ) {
					dragPct = dragPct * 0.3; // Resistance
				}
				self.track.style.transform = 'translateX(' + ( baseOffset + dragPct ) + '%)';
			}
		}, { passive: false } );

		stage.addEventListener( 'touchend', function ( e ) {
			self.track.classList.remove( 'is-dragging' );

			if ( ! self.swipeActive || ! self.swipeTouchStart ) {
				self.swipeTouchStart = null;
				self.swipeActive     = false;
				self.swipeConfirmed  = false;
				// Snap back to current position if no swipe confirmed.
				self.track.style.transform = 'translateX(-' + ( self.currentIdx * 100 ) + '%)';
				return;
			}

			var changedTouch = e.changedTouches[ 0 ];
			var dx           = changedTouch.clientX - self.swipeStartX;
			var dy           = changedTouch.clientY - self.swipeStartY;

			self.swipeActive     = false;
			self.swipeConfirmed  = false;
			self.swipeTouchStart = null;

			if ( Math.abs( dy ) > SWIPE_MAX_VERTICAL ) {
				self.track.style.transform = 'translateX(-' + ( self.currentIdx * 100 ) + '%)';
				return;
			}
			if ( Math.abs( dx ) < SWIPE_THRESHOLD ) {
				// Not far enough — snap back to current.
				self.track.style.transform = 'translateX(-' + ( self.currentIdx * 100 ) + '%)';
				return;
			}

			// goTo restores the transform via translateX.
			if ( dx < 0 ) {
				self.next();
			} else {
				self.prev();
			}
		}, { passive: true } );

		// Cancel swipe on touchcancel (e.g. incoming call).
		stage.addEventListener( 'touchcancel', function () {
			self.track.classList.remove( 'is-dragging' );
			self.swipeTouchStart = null;
			self.swipeActive     = false;
			self.swipeConfirmed  = false;
			self.track.style.transform = 'translateX(-' + ( self.currentIdx * 100 ) + '%)';
		}, { passive: true } );
	};

	/* -----------------------------------------------------------------------
	 * Keyboard navigation
	 * --------------------------------------------------------------------- */

	WCPCEGallery.prototype.bindKeyboard = function () {
		var self = this;

		this.root.addEventListener( 'keydown', function ( e ) {
			// Only act when focus is inside this gallery and lightbox is closed.
			if ( self.lightbox && self.lightbox.getAttribute( 'aria-hidden' ) === 'false' ) {
				return; // Lightbox has its own keyboard handler.
			}
			if ( e.key === 'ArrowLeft' ) {
				self.prev();
				e.preventDefault();
			} else if ( e.key === 'ArrowRight' ) {
				self.next();
				e.preventDefault();
			}
		} );
	};

	/* -----------------------------------------------------------------------
	 * Lightbox
	 * --------------------------------------------------------------------- */

	/**
	 * Wires up lightbox for a single-slide gallery (no nav buttons).
	 */
	WCPCEGallery.prototype.bindSingleSlideLightbox = function () {
		var self  = this;
		var slide = this.root.querySelector( '.wcpce-gallery__slide' );
		if ( ! slide ) {
			return;
		}

		this.bindLightboxTrigger( slide, 0 );
		this.bindLightboxControls();
	};

	WCPCEGallery.prototype.bindLightbox = function () {
		var self = this;

		// Wire up each slide as a lightbox trigger.
		for ( var i = 0; i < this.slides.length; i++ ) {
			this.bindLightboxTrigger( this.slides[ i ], i );
		}

		this.bindLightboxControls();
	};

	/**
	 * Makes a slide open the lightbox on click/tap.
	 *
	 * @param {Element} slide
	 * @param {number}  index
	 */
	WCPCEGallery.prototype.bindLightboxTrigger = function ( slide, index ) {
		var self       = this;
		var imageWrap  = slide.querySelector( '.wcpce-gallery__main-image-wrap' );
		var playBtn    = slide.querySelector( '.wcpce-gallery__play-btn' );

		// Image click → open lightbox at this index (image mode).
		if ( imageWrap && ! playBtn && this.imageLightboxOn ) {
			imageWrap.style.cursor = 'zoom-in';
			imageWrap.addEventListener( 'click', function () {
				self.openLightbox( index, 'image' );
			} );
		}

		// Play button click → open lightbox in video mode.
		if ( playBtn && this.videoLightboxOn ) {
			playBtn.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				self.openLightbox( index, 'video' );
			} );
		}
	};

	WCPCEGallery.prototype.bindLightboxControls = function () {
		var self = this;

		if ( this.lbClose ) {
			this.lbClose.addEventListener( 'click', function () {
				self.closeLightbox();
			} );
		}

		if ( this.lbBackdrop ) {
			this.lbBackdrop.addEventListener( 'click', function () {
				self.closeLightbox();
			} );
		}

		if ( this.lbPrev ) {
			this.lbPrev.addEventListener( 'click', function () {
				self.lightboxNav( -1 );
			} );
		}

		if ( this.lbNext ) {
			this.lbNext.addEventListener( 'click', function () {
				self.lightboxNav( 1 );
			} );
		}

		// Keyboard inside lightbox.
		this.lightbox.addEventListener( 'keydown', function ( e ) {
			switch ( e.key ) {
				case 'Escape':
					self.closeLightbox();
					e.preventDefault();
					break;
				case 'ArrowLeft':
					self.lightboxNav( -1 );
					e.preventDefault();
					break;
				case 'ArrowRight':
					self.lightboxNav( 1 );
					e.preventDefault();
					break;
				case 'Tab':
					self.trapFocus( e );
					break;
			}
		} );
	};

	/**
	 * Opens the lightbox at the given slide index.
	 *
	 * @param {number} index
	 * @param {'image'|'video'} mode
	 */
	WCPCEGallery.prototype.openLightbox = function ( index, mode ) {
		if ( ! this.lightbox || ! this.lbContent ) {
			return;
		}

		this.lightboxOpener = document.activeElement;
		this.lightboxIndex  = index;
		this.lightboxMode   = mode;

		this.renderLightboxContent( index, mode );

		this.lightbox.setAttribute( 'aria-hidden', 'false' );
		document.body.style.overflow = 'hidden';

		// Focus the close button.
		if ( this.lbClose ) {
			this.lbClose.focus();
		}
	};

	WCPCEGallery.prototype.closeLightbox = function () {
		if ( ! this.lightbox ) {
			return;
		}

		// Stop any playing video.
		if ( this.lbContent ) {
			var iframe = this.lbContent.querySelector( 'iframe' );
			if ( iframe ) {
				iframe.src = '';
			}
			this.lbContent.innerHTML = '';
		}

		// Reset zoom.
		this.resetZoom();
		this.zoomImg = null;

		this.lightbox.setAttribute( 'aria-hidden', 'true' );
		document.body.style.overflow = '';

		// Return focus to the element that opened the lightbox.
		if ( this.lightboxOpener && typeof this.lightboxOpener.focus === 'function' ) {
			this.lightboxOpener.focus();
		}
	};

	/**
	 * Navigates the lightbox by direction (-1 = prev, +1 = next).
	 *
	 * @param {number} dir
	 */
	WCPCEGallery.prototype.lightboxNav = function ( dir ) {
		if ( ! this.lightbox || this.lightbox.getAttribute( 'aria-hidden' ) !== 'false' ) {
			return;
		}
		if ( ! this.imageLightboxOn ) {
			return;
		}

		var newIndex = this.lightboxIndex + dir;
		if ( newIndex < 0 ) {
			newIndex = this.totalSlides - 1;
		} else if ( newIndex >= this.totalSlides ) {
			newIndex = 0;
		}

		// Stop current video before switching.
		if ( this.lbContent ) {
			var iframe = this.lbContent.querySelector( 'iframe' );
			if ( iframe ) {
				iframe.src = '';
			}
		}

		this.resetZoom();

		var newSlide = this.slides[ newIndex ];
		var isVideo  = newSlide && newSlide.classList.contains( 'wcpce-gallery__slide--video' );

		this.lightboxIndex = newIndex;
		this.lightboxMode  = isVideo ? 'video' : 'image';

		this.renderLightboxContent( newIndex, this.lightboxMode );

		// Sync the main gallery to match.
		this.goTo( newIndex );
	};

	/**
	 * Renders the content inside the lightbox for a given slide.
	 *
	 * @param {number} index
	 * @param {'image'|'video'} mode
	 */
	WCPCEGallery.prototype.renderLightboxContent = function ( index, mode ) {
		if ( ! this.lbContent ) {
			return;
		}

		this.lbContent.innerHTML = '';
		this.resetZoom();
		this.zoomImg = null;

		var slide = this.slides[ index ];
		if ( ! slide ) {
			return;
		}

		if ( mode === 'video' ) {
			var videoSlide = slide.querySelector( '.wcpce-gallery__video-slide' );
			var youtubeId  = videoSlide ? videoSlide.getAttribute( 'data-youtube-id' ) : '';

			if ( youtubeId ) {
				var iframe       = document.createElement( 'iframe' );
				iframe.className = 'wcpce-gallery__yt-iframe';
				iframe.src       = this.buildYouTubeUrl( youtubeId );
				iframe.setAttribute( 'allowfullscreen', '' );
				iframe.setAttribute( 'allow', 'autoplay; fullscreen' );
				iframe.setAttribute( 'loading', 'lazy' );
				this.lbContent.appendChild( iframe );
			}
		} else {
			// Image mode: clone the full-size image from the slide.
			var img = slide.querySelector( '.wcpce-gallery__image' );
			if ( img ) {
				var lbImg       = document.createElement( 'img' );
				lbImg.src       = img.currentSrc || img.src;
				lbImg.alt       = img.alt || '';
				lbImg.className = 'wcpce-gallery__lb-image';
				lbImg.style.cursor = this.zoomOn ? 'zoom-in' : 'default';
				this.lbContent.appendChild( lbImg );

				if ( this.zoomOn ) {
					this.bindZoom( lbImg );
				}
			}
		}
	};

	/* -----------------------------------------------------------------------
	 * Focus trap inside lightbox
	 * --------------------------------------------------------------------- */

	WCPCEGallery.prototype.trapFocus = function ( e ) {
		var focusable = getFocusable( this.lightbox );
		if ( focusable.length === 0 ) {
			e.preventDefault();
			return;
		}
		var first = focusable[ 0 ];
		var last  = focusable[ focusable.length - 1 ];

		if ( e.shiftKey ) {
			if ( document.activeElement === first ) {
				last.focus();
				e.preventDefault();
			}
		} else {
			if ( document.activeElement === last ) {
				first.focus();
				e.preventDefault();
			}
		}
	};

	/* -----------------------------------------------------------------------
	 * Zoom (desktop: click to zoom, drag to pan, click again to reset)
	 * --------------------------------------------------------------------- */

	WCPCEGallery.prototype.ensureZoomDocumentEvents = function () {
		if ( this.zoomDocumentEventsBound ) {
			return;
		}

		var self = this;

		document.addEventListener( 'mousemove', function ( e ) {
			if ( ! self.isDragging || ! self.zoomImg ) {
				return;
			}
			var dx    = e.clientX - self.dragStart.x;
			var dy    = e.clientY - self.dragStart.y;
			self.panX = self.panStart.x + dx;
			self.panY = self.panStart.y + dy;
			self.applyZoomTransform( self.zoomImg );
		} );

		document.addEventListener( 'mouseup', function () {
			if ( self.isDragging && self.zoomImg ) {
				self.isDragging = false;
				self.zoomImg.style.cursor = self.zoomLevel > 1 ? 'grab' : 'zoom-in';
			}
		} );

		this.zoomDocumentEventsBound = true;
	};

	WCPCEGallery.prototype.bindZoom = function ( img ) {
		var self = this;
		this.zoomImg = img;
		this.ensureZoomDocumentEvents();

		// Mouse: click to toggle zoom, drag to pan while zoomed.
		img.addEventListener( 'mousedown', function ( e ) {
			if ( self.zoomLevel > 1 ) {
				self.isDragging = true;
				self.dragStart  = { x: e.clientX, y: e.clientY };
				self.panStart   = { x: self.panX, y: self.panY };
				img.style.cursor = 'grabbing';
				e.preventDefault();
			}
		} );

		img.addEventListener( 'click', function ( e ) {
			if ( self.isDragging ) {
				return; // Was a drag, not a click.
			}
			if ( self.zoomLevel > 1 ) {
				self.resetZoom( img );
				img.style.cursor = 'zoom-in';
			} else {
				// Zoom in centred on click point.
				var rect    = img.getBoundingClientRect();
				var clickX  = e.clientX - rect.left - rect.width / 2;
				var clickY  = e.clientY - rect.top - rect.height / 2;
				self.zoomLevel = ZOOM_STEP_CLICK;
				self.panX = -clickX * ( self.zoomLevel - 1 );
				self.panY = -clickY * ( self.zoomLevel - 1 );
				self.clampPan( img );
				self.applyZoomTransform( img );
				img.style.cursor = 'grab';
			}
		} );

		// Touch: pinch to zoom, double-tap to reset.
		this.bindPinchZoom( img );
	};

	/* -----------------------------------------------------------------------
	 * Pinch-zoom (mobile)
	 * --------------------------------------------------------------------- */

	WCPCEGallery.prototype.bindPinchZoom = function ( img ) {
		var self = this;

		img.addEventListener( 'touchstart', function ( e ) {
			if ( e.touches.length === 2 ) {
				// Two fingers — pinch start.
				e.preventDefault();
				self.pinchStart     = touchDistance( e.touches[ 0 ], e.touches[ 1 ] );
				self.pinchZoomStart = self.zoomLevel;
				self.pinchMidStart  = touchMidpoint( e.touches[ 0 ], e.touches[ 1 ] );
				self.pinchPanStart  = { x: self.panX, y: self.panY };
			} else if ( e.touches.length === 1 ) {
				// Single tap — check for double-tap.
				var now  = Date.now();
				var tapX = e.touches[ 0 ].clientX;
				var tapY = e.touches[ 0 ].clientY;

				if (
					now - self.lastTapTime < DOUBLE_TAP_MS &&
					Math.abs( tapX - self.lastTapX ) < 30 &&
					Math.abs( tapY - self.lastTapY ) < 30
				) {
					// Double-tap: toggle zoom.
					e.preventDefault();
					if ( self.zoomLevel > 1 ) {
						self.resetZoom( img );
					} else {
						var rect    = img.getBoundingClientRect();
						var tapImgX = tapX - rect.left - rect.width / 2;
						var tapImgY = tapY - rect.top - rect.height / 2;
						self.zoomLevel = ZOOM_STEP_CLICK;
						self.panX = -tapImgX * ( self.zoomLevel - 1 );
						self.panY = -tapImgY * ( self.zoomLevel - 1 );
						self.clampPan( img );
						self.applyZoomTransform( img );
					}
				}

				self.lastTapTime = now;
				self.lastTapX    = tapX;
				self.lastTapY    = tapY;

				// Pan while zoomed with single finger.
				if ( self.zoomLevel > 1 ) {
					self.isDragging = true;
					self.dragStart  = { x: tapX, y: tapY };
					self.panStart   = { x: self.panX, y: self.panY };
				}
			}
		}, { passive: false } );

		img.addEventListener( 'touchmove', function ( e ) {
			if ( e.touches.length === 2 ) {
				e.preventDefault();
				var currentDist = touchDistance( e.touches[ 0 ], e.touches[ 1 ] );
				var scale       = currentDist / self.pinchStart;
				self.zoomLevel  = clamp( self.pinchZoomStart * scale, ZOOM_MIN, ZOOM_MAX );

				// Pan to keep the midpoint fixed during pinch.
				var currentMid = touchMidpoint( e.touches[ 0 ], e.touches[ 1 ] );
				self.panX = self.pinchPanStart.x + ( currentMid.x - self.pinchMidStart.x );
				self.panY = self.pinchPanStart.y + ( currentMid.y - self.pinchMidStart.y );
				self.clampPan( img );
				self.applyZoomTransform( img );
			} else if ( e.touches.length === 1 && self.isDragging ) {
				if ( self.zoomLevel > 1 ) {
					e.preventDefault(); // prevent scroll while panning zoomed image
					var dx    = e.touches[ 0 ].clientX - self.dragStart.x;
					var dy    = e.touches[ 0 ].clientY - self.dragStart.y;
					self.panX = self.panStart.x + dx;
					self.panY = self.panStart.y + dy;
					self.clampPan( img );
					self.applyZoomTransform( img );
				}
			}
		}, { passive: false } );

		img.addEventListener( 'touchend', function () {
			self.isDragging = false;
			if ( self.zoomLevel <= 1 ) {
				self.resetZoom( img );
			}
		}, { passive: true } );
	};

	/**
	 * Applies the current zoom level and pan offset to an image.
	 *
	 * @param {HTMLImageElement} img
	 */
	WCPCEGallery.prototype.applyZoomTransform = function ( img ) {
		if ( ! img ) {
			return;
		}
		img.style.transform = 'scale(' + this.zoomLevel + ') translate(' +
			( this.panX / this.zoomLevel ) + 'px, ' +
			( this.panY / this.zoomLevel ) + 'px)';
		img.style.transformOrigin = 'center center';
	};

	/**
	 * Clamps pan offsets so the image doesn't pan outside its bounds.
	 *
	 * @param {HTMLImageElement} img
	 */
	WCPCEGallery.prototype.clampPan = function ( img ) {
		if ( ! img || this.zoomLevel <= 1 ) {
			this.panX = 0;
			this.panY = 0;
			return;
		}
		var rect     = img.getBoundingClientRect();
		var maxPanX  = ( rect.width * ( this.zoomLevel - 1 ) ) / 2;
		var maxPanY  = ( rect.height * ( this.zoomLevel - 1 ) ) / 2;
		this.panX    = clamp( this.panX, -maxPanX, maxPanX );
		this.panY    = clamp( this.panY, -maxPanY, maxPanY );
	};

	/**
	 * Resets zoom and pan to their default state.
	 *
	 * @param {HTMLImageElement} [img] When provided, also resets the transform.
	 */
	WCPCEGallery.prototype.resetZoom = function ( img ) {
		this.zoomLevel  = 1;
		this.panX       = 0;
		this.panY       = 0;
		this.isDragging = false;
		if ( img ) {
			img.style.transform = '';
		} else if ( this.lbContent ) {
			var lbImg = this.lbContent.querySelector( '.wcpce-gallery__lb-image' );
			if ( lbImg ) {
				lbImg.style.transform = '';
			}
		}
	};

	/* =========================================================================
	 * Bootstrap — initialise all gallery instances on the page
	 * ======================================================================= */

	function initGallery( galleryEl ) {
		if ( ! galleryEl || galleryEl.getAttribute( 'data-wcpce-gallery-init' ) === '1' ) {
			return;
		}
		galleryEl.setAttribute( 'data-wcpce-gallery-init', '1' );
		new WCPCEGallery( galleryEl );
	}

	function initAllGalleries() {
		var galleries = document.querySelectorAll( '.wcpce-gallery' );
		for ( var i = 0; i < galleries.length; i++ ) {
			initGallery( galleries[ i ] );
		}
	}

	// Standard DOMContentLoaded.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAllGalleries );
	} else {
		initAllGalleries();
	}

	// Elementor frontend init hook (covers editor preview and Theme Builder).
	if (
		window.elementorFrontend &&
		window.elementorFrontend.hooks &&
		typeof window.elementorFrontend.hooks.addAction === 'function'
	) {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/wcpce_product_gallery.default', function ( $scope ) {
			var root = $scope[ 0 ];
			if ( ! root ) {
				return;
			}

			if ( root.classList && root.classList.contains( 'wcpce-gallery' ) ) {
				initGallery( root );
				return;
			}

			var galleries = root.querySelectorAll( '.wcpce-gallery' );
			for ( var i = 0; i < galleries.length; i++ ) {
				initGallery( galleries[ i ] );
			}
		} );
	}

} )();
