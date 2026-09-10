/**
 * Product Category Navigation progressive enhancement.
 *
 * Keeps the server-rendered horizontal list usable without JavaScript, then
 * adds page-aware arrows and dots when the list actually overflows.
 *
 * @package WC_Product_Card_Elementor
 * @since 2.8.0
 */

( function () {
	'use strict';

	/** Initialises one category navigation root exactly once. */
	function initCategoryNavigation( root ) {
		if ( ! root || root.dataset.wcpceCategoryNavigationInit === '1' ) {
			return;
		}

		var list     = root.querySelector( '.wcpce-category-nav__list' );
		var items    = list ? Array.prototype.slice.call( list.children ) : [];
		var previous = root.querySelector( '[data-wcpce-category-direction="-1"]' );
		var next     = root.querySelector( '[data-wcpce-category-direction="1"]' );
		var arrows   = root.querySelector( '.wcpce-category-nav__arrows' );
		var dots     = root.querySelector( '.wcpce-category-nav__dots' );
		var page     = 0;
		var pages    = 1;
		var perPage  = 1;
		var rafId    = 0;

		if ( ! list || ! items.length ) {
			return;
		}

		root.dataset.wcpceCategoryNavigationInit = '1';
		root.classList.add( 'is-enhanced' );

		/** Returns whether animated scrolling is currently appropriate. */
		function smoothScrollEnabled() {
			return root.dataset.smoothScroll === 'yes' &&
				! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		}

		/** Returns a browser-independent horizontal offset for LTR and RTL. */
		function logicalScrollOffset() {
			return 'rtl' === window.getComputedStyle( list ).direction ?
				Math.abs( list.scrollLeft ) : list.scrollLeft;
		}

		/** Updates arrows and pagination state from the current scroll position. */
		function updateNavigationState() {
			var maxScroll = Math.max( 0, list.scrollWidth - list.clientWidth );
			var offset    = Math.min( maxScroll, logicalScrollOffset() );
			var threshold = 2;

			page = maxScroll - offset <= threshold ? pages - 1 :
				Math.max( 0, Math.min( pages - 1, Math.round( offset / Math.max( 1, list.clientWidth ) ) ) );

			if ( previous ) {
				previous.disabled = offset <= threshold;
			}
			if ( next ) {
				next.disabled = maxScroll - offset <= threshold;
			}

			if ( dots ) {
				Array.prototype.forEach.call( dots.children, function ( dot, index ) {
					dot.setAttribute( 'aria-current', index === page ? 'true' : 'false' );
				} );
			}
		}

		/** Scrolls to one calculated navigation page. */
		function scrollToPage( targetPage ) {
			var target = Math.max( 0, Math.min( pages - 1, targetPage ) );
			var item   = items[ Math.min( items.length - 1, target * perPage ) ];

			if ( item ) {
				item.scrollIntoView( {
					behavior: smoothScrollEnabled() ? 'smooth' : 'auto',
					block: 'nearest',
					inline: 'start'
				} );
			}
		}

		/** Rebuilds dots only when the number of pages changes. */
		function rebuildDots( newPageCount ) {
			if ( ! dots || dots.children.length === newPageCount ) {
				return;
			}

			dots.replaceChildren();
			for ( var index = 0; index < newPageCount; index++ ) {
				( function ( dotPage ) {
					var dot = document.createElement( 'button' );
					dot.type      = 'button';
					dot.className = 'wcpce-category-nav__dot';
					var pageLabel = root.dataset.pageLabel || 'Pagina %1$d van %2$d';
					dot.setAttribute(
						'aria-label',
						pageLabel.replace( '%1$d', dotPage + 1 ).replace( '%2$d', newPageCount )
					);
					dot.addEventListener( 'click', function () {
						scrollToPage( dotPage );
					} );
					dots.appendChild( dot );
				} )( index );
			}
		}

		/** Measures overflow and responsive page size after layout changes. */
		function measure() {
			var firstWidth  = items[0].getBoundingClientRect().width;
			var styles      = window.getComputedStyle( list );
			var gap         = parseFloat( styles.columnGap || styles.gap ) || 0;
			var itemAdvance = Math.max( 1, firstWidth + gap );
			var hasOverflow = list.scrollWidth - list.clientWidth > 2;

			perPage = Math.max( 1, Math.floor( ( list.clientWidth + gap ) / itemAdvance ) );
			pages   = hasOverflow ? Math.ceil( items.length / perPage ) : 1;

			root.classList.toggle( 'has-overflow', hasOverflow );
			root.classList.add( 'is-ready' );

			if ( arrows ) {
				arrows.setAttribute( 'aria-hidden', hasOverflow ? 'false' : 'true' );
			}
			if ( dots ) {
				dots.setAttribute( 'aria-hidden', hasOverflow && pages > 1 ? 'false' : 'true' );
				rebuildDots( hasOverflow ? pages : 0 );
			}

			updateNavigationState();
		}

		if ( previous ) {
			previous.addEventListener( 'click', function () {
				scrollToPage( page - 1 );
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function () {
				scrollToPage( page + 1 );
			} );
		}

		list.addEventListener( 'scroll', function () {
			if ( rafId ) {
				return;
			}
			rafId = window.requestAnimationFrame( function () {
				rafId = 0;
				updateNavigationState();
			} );
		}, { passive: true } );

		if ( 'ResizeObserver' in window ) {
			new window.ResizeObserver( measure ).observe( list );
		} else {
			window.addEventListener( 'resize', measure, { passive: true } );
		}

		measure();
	}

	/** Finds all uninitialised widget instances below a document or wrapper. */
	function initAll( scope ) {
		var context = scope && scope.querySelectorAll ? scope : document;
		Array.prototype.forEach.call(
			context.querySelectorAll( '[data-wcpce-category-navigation]' ),
			initCategoryNavigation
		);

		if ( context.matches && context.matches( '[data-wcpce-category-navigation]' ) ) {
			initCategoryNavigation( context );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll( document );
		} );
	} else {
		initAll( document );
	}

	window.addEventListener( 'elementor/frontend/init', function () {
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/wcpce_category_navigation.default',
				function ( scope ) {
					initAll( scope && scope[0] ? scope[0] : scope );
				}
			);
		}
	} );
}() );
