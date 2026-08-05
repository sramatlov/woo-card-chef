/**
 * WCPCE Product Accordion — product-accordion.js
 *
 * Handles:
 * - Outer accordion initial state plus open/close with aria-expanded and hidden
 * - FAQ inner accordion (same pattern, scoped to .wcpce-accordion__faq)
 * - Lipscore review-count sync via MutationObserver
 * - Hash-jump to reviews section (#lipscore-review-list, #tab-lipscorereviews, #reviews)
 * - Scroll-to-trigger on open (offset for sticky header)
 * - Elementor editor double-init guard
 *
 * Follows the project progressive-enhancement rule: core HTML is server-rendered;
 * this script only adds interaction. Deferred — initialises on DOMContentLoaded
 * and Elementor's frontend/element_ready hook.
 *
 * @package WC_Product_Card_Elementor
 * @since 2.4.0
 */

( function () {
	'use strict';

	// -------------------------------------------------------------------------
	// Init guard
	// -------------------------------------------------------------------------

	/**
	 * Initialises a single .wcpce-accordion element.
	 * Guards against double-init from both DOMContentLoaded and elementorFrontend.
	 *
	 * @param {HTMLElement} accordionEl
	 */
	function initAccordion( accordionEl ) {
		if ( accordionEl.dataset.wcpceAccordionInit === '1' ) {
			return;
		}
		accordionEl.dataset.wcpceAccordionInit = '1';

		applyInitialOuterState( accordionEl );
		initOuterAccordion( accordionEl );
		initFaqAccordion( accordionEl );
		initLipscoreSync( accordionEl );
	}

	// -------------------------------------------------------------------------
	// Outer accordion
	// -------------------------------------------------------------------------

	/**
	 * Applies the configured open/closed state after server-rendered fallback.
	 * PHP renders all panels open so content remains available without JS.
	 *
	 * @param {HTMLElement} accordionEl
	 */
	function applyInitialOuterState( accordionEl ) {
		var defaultOpen = accordionEl.getAttribute( 'data-default-open' ) || 'none';
		var items       = accordionEl.querySelectorAll( ':scope > .wcpce-accordion__item' );

		items.forEach( function ( item ) {
			var trigger = item.querySelector( ':scope > .wcpce-accordion__heading > .wcpce-accordion__trigger' );
			var panel   = trigger ? document.getElementById( trigger.getAttribute( 'aria-controls' ) ) : null;
			if ( ! trigger || ! panel ) return;

			var section = item.getAttribute( 'data-section' ) || '';
			if ( section && section === defaultOpen ) {
				openItem( item, trigger, panel );
			} else {
				closeItem( item, trigger, panel );
			}
		} );
	}

	/**
	 * Wires up the outer accordion triggers.
	 * Multiple sections can be open at the same time (NNG recommendation).
	 *
	 * @param {HTMLElement} accordionEl
	 */
	function initOuterAccordion( accordionEl ) {
		var triggers = accordionEl.querySelectorAll( ':scope > .wcpce-accordion__item > .wcpce-accordion__heading > .wcpce-accordion__trigger' );

		triggers.forEach( function ( trigger ) {
			trigger.addEventListener( 'click', function () {
				var item  = trigger.closest( '.wcpce-accordion__item' );
				var panel = document.getElementById( trigger.getAttribute( 'aria-controls' ) );
				if ( ! item || ! panel ) return;

				var isOpen = item.classList.contains( 'is-open' );

				if ( isOpen ) {
					closeItem( item, trigger, panel );
				} else {
					openItem( item, trigger, panel );
					scrollToTrigger( trigger );
				}
			} );
		} );
	}

	/**
	 * Opens an accordion item.
	 *
	 * @param {HTMLElement} item
	 * @param {HTMLElement} trigger
	 * @param {HTMLElement} panel
	 */
	function openItem( item, trigger, panel ) {
		item.classList.add( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'true' );
		panel.removeAttribute( 'hidden' );
	}

	/**
	 * Closes an accordion item.
	 *
	 * @param {HTMLElement} item
	 * @param {HTMLElement} trigger
	 * @param {HTMLElement} panel
	 */
	function closeItem( item, trigger, panel ) {
		item.classList.remove( 'is-open' );
		trigger.setAttribute( 'aria-expanded', 'false' );
		panel.setAttribute( 'hidden', '' );
	}

	/**
	 * Scrolls the viewport so the trigger is visible below the sticky header.
	 * Uses a small delay so layout is settled after the panel opens.
	 *
	 * @param {HTMLElement} trigger
	 */
	function scrollToTrigger( trigger ) {
		setTimeout( function () {
			var offset    = 100;
			var targetTop = trigger.getBoundingClientRect().top + window.pageYOffset - offset;

			if ( targetTop < window.pageYOffset - 5 ) {
				window.scrollTo( { top: targetTop, behavior: 'smooth' } );
			}
		}, 60 );
	}

	// -------------------------------------------------------------------------
	// FAQ inner accordion
	// -------------------------------------------------------------------------

	/**
	 * Wires up the FAQ inner accordion triggers within a .wcpce-accordion__faq.
	 * FAQ items toggle independently; multiple can be open at the same time.
	 *
	 * @param {HTMLElement} accordionEl
	 */
	function initFaqAccordion( accordionEl ) {
		var faqItems = accordionEl.querySelectorAll( '.wcpce-accordion__faq-item' );

		faqItems.forEach( function ( item ) {
			var trigger = item.querySelector( '.wcpce-accordion__faq-trigger' );
			var panel   = trigger ? document.getElementById( trigger.getAttribute( 'aria-controls' ) ) : null;

			if ( ! trigger || ! panel ) return;

			closeItem( item, trigger, panel );

			trigger.addEventListener( 'click', function () {
				var isOpen = item.classList.contains( 'is-open' );

				if ( isOpen ) {
					item.classList.remove( 'is-open' );
					trigger.setAttribute( 'aria-expanded', 'false' );
					panel.setAttribute( 'hidden', '' );
				} else {
					item.classList.add( 'is-open' );
					trigger.setAttribute( 'aria-expanded', 'true' );
					panel.removeAttribute( 'hidden' );
				}
			} );
		} );
	}

	// -------------------------------------------------------------------------
	// Lipscore review-count sync
	// -------------------------------------------------------------------------

	/**
	 * Syncs the Lipscore review count from the native WC tab label into the
	 * accordion trigger. Lipscore updates the count asynchronously, so we use
	 * a MutationObserver on the original tab label node plus a short polling
	 * interval as a belt-and-braces approach.
	 *
	 * @param {HTMLElement} accordionEl
	 */
	function initLipscoreSync( accordionEl ) {
		var reviewsItem = accordionEl.querySelector( '.wcpce-accordion__item--reviews' );
		if ( ! reviewsItem ) return;

		var trigger     = reviewsItem.querySelector( '.wcpce-accordion__trigger' );
		var triggerText = trigger ? trigger.querySelector( '.wcpce-accordion__trigger-text' ) : null;
		if ( ! triggerText ) return;

		// Store the base label (without count) from the rendered trigger text.
		// We assume the rendered text is the configured label, with no count yet.
		var baseLabel = triggerText.textContent.trim();

		/**
		 * Reads the count from the Lipscore WC tab label node and updates the
		 * accordion trigger text.
		 */
		function syncCount() {
			var countEl = document.querySelector( '#js-lipscore-reviews-tab-count .lipscore-review-count' )
				|| document.querySelector( '#js-lipscore-reviews-tab-count' );

			if ( ! countEl ) return;

			var countText = ( countEl.textContent || '' ).replace( /\u00a0/g, ' ' ).trim();
			if ( ! countText ) return;

			// Normalise to "(N)" format.
			var formatted = countText;
			if ( /^\d+$/.test( countText ) ) {
				formatted = '(' + countText + ')';
			} else if ( ! /^\(.*\)$/.test( countText ) ) {
				var match = countText.match( /\d+/ );
				if ( match ) formatted = '(' + match[0] + ')';
			}

			if ( formatted && triggerText.textContent !== baseLabel + ' ' + formatted ) {
				triggerText.textContent = baseLabel + ' ' + formatted;
			}
		}

		// Observe the Lipscore count node for DOM changes.
		var countHolder = document.querySelector( '#js-lipscore-reviews-tab-count' );
		if ( countHolder && window.MutationObserver ) {
			var observer = new MutationObserver( syncCount );
			observer.observe( countHolder, { childList: true, subtree: true, characterData: true, attributes: true } );
		}

		// Polling fallback: run 20 times over 10 seconds.
		var attempts    = 0;
		var syncInterval = setInterval( function () {
			syncCount();
			if ( ++attempts >= 20 ) clearInterval( syncInterval );
		}, 500 );

		window.addEventListener( 'load', function () {
			syncCount();
			setTimeout( syncCount, 300 );
			setTimeout( syncCount, 1000 );
			setTimeout( syncCount, 2000 );
		} );
	}

	// -------------------------------------------------------------------------
	// Hash-jump to reviews section
	// -------------------------------------------------------------------------

	/**
	 * Opens the reviews accordion section and scrolls to the Lipscore review list.
	 * Called on page load (initial hash) and on click events targeting reviews.
	 *
	 * @param {HTMLElement} accordionEl
	 */
	function openReviewsAndScroll( accordionEl ) {
		var reviewsItem = accordionEl.querySelector( '.wcpce-accordion__item--reviews' );
		if ( ! reviewsItem ) return false;

		var trigger = reviewsItem.querySelector( '.wcpce-accordion__trigger' );
		var panel   = trigger ? document.getElementById( trigger.getAttribute( 'aria-controls' ) ) : null;
		if ( ! trigger || ! panel ) return false;

		if ( ! reviewsItem.classList.contains( 'is-open' ) ) {
			openItem( reviewsItem, trigger, panel );
		}

		// Scroll to the Lipscore review list inside the panel.
		requestAnimationFrame( function () {
			setTimeout( function () {
				var reviewList = panel.querySelector( '#lipscore-review-list, [id^="lipscore-review-list"], .lipscore-review-list-container' );
				var target     = reviewList || trigger;
				var offset     = 140;
				var top        = target.getBoundingClientRect().top + window.pageYOffset - offset;
				window.scrollTo( { top: top, behavior: 'smooth' } );
			}, 80 );
		} );

		return true;
	}

	/**
	 * Returns true when the given link should trigger a jump to the reviews section.
	 *
	 * @param {HTMLElement|null} link
	 * @returns {boolean}
	 */
	function isReviewJumpLink( link ) {
		if ( ! link ) return false;

		if ( link.closest( '#js-lipscore-reviews-tab' ) ) return true;

		var href = link.getAttribute( 'href' ) || '';
		var hash = '';
		try {
			hash = new URL( href, window.location.href ).hash || '';
		} catch ( e ) {
			if ( href.charAt( 0 ) === '#' ) hash = href;
		}

		hash = hash.toLowerCase();
		if (
			hash === '#lipscore-review-list' ||
			hash.indexOf( 'lipscore-review-list' ) !== -1 ||
			hash === '#tab-lipscorereviews' ||
			hash === '#reviews'
		) return true;

		var connectedId = ( link.getAttribute( 'data-ls-connected-review-list-id' ) || '' ).toLowerCase();
		return connectedId === 'lipscore-review-list';
	}

	// -------------------------------------------------------------------------
	// Bootstrap
	// -------------------------------------------------------------------------

	/**
	 * Finds all uninitialised .wcpce-accordion elements and initialises them.
	 */
	function initAllAccordions() {
		var elements = document.querySelectorAll( '.wcpce-accordion' );
		elements.forEach( initAccordion );
	}

	/**
	 * Sets up global hash-jump and page-load review jump after accordions are ready.
	 */
	function initGlobalListeners() {
		// Handle initial page hash.
		window.addEventListener( 'load', function () {
			var hash = ( window.location.hash || '' ).toLowerCase();
			if (
				hash === '#lipscore-review-list' ||
				hash.indexOf( 'lipscore-review-list' ) !== -1 ||
				hash === '#tab-lipscorereviews' ||
				hash === '#reviews'
			) {
				document.querySelectorAll( '.wcpce-accordion' ).forEach( function ( el ) {
					openReviewsAndScroll( el );
				} );
			}
		} );

		// Handle click events on review-jump links (capture phase for priority).
		document.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a' );
			if ( ! isReviewJumpLink( link ) ) return;

			var opened = false;
			document.querySelectorAll( '.wcpce-accordion' ).forEach( function ( el ) {
				if ( openReviewsAndScroll( el ) ) opened = true;
			} );

			if ( opened ) event.preventDefault();
		}, true );
	}

	// DOMContentLoaded path.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAllAccordions();
			initGlobalListeners();
		} );
	} else {
		initAllAccordions();
		initGlobalListeners();
	}

	// Elementor editor / Theme Builder preview path.
	if (
		typeof window.elementorFrontend !== 'undefined' &&
		window.elementorFrontend.hooks &&
		typeof window.elementorFrontend.hooks.addAction === 'function'
	) {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/wcpce_product_accordion.default', function ( $el ) {
			var el = $el && $el[0] ? $el[0].querySelector( '.wcpce-accordion' ) : null;
			if ( el ) initAccordion( el );
		} );
	}

} )();
