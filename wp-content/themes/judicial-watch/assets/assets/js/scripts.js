/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ([
/* 0 */,
/* 1 */
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

	__webpack_require__.r(__webpack_exports__);
	/* harmony export */ __webpack_require__.d(__webpack_exports__, {
	/* harmony export */   isInViewport: () => (/* binding */ isInViewport),
	/* harmony export */   slideDown: () => (/* binding */ slideDown), 
	/* harmony export */   slideToggle: () => (/* binding */ slideToggle),
	/* harmony export */   slideUp: () => (/* binding */ slideUp)
	/* harmony export */ });

	/**
	 * isInViewport
	 */
	function isInViewport(element) {
		const rect = element.getBoundingClientRect();
		const windowHeight = (window.innerHeight || document.documentElement.clientHeight);
		const windowWidth = (window.innerWidth || document.documentElement.clientWidth);
	
		return (rect.top <= windowHeight && rect.top + rect.height >= 0) && (rect.left <= windowWidth && rect.left + rect.width >= 0);
	}

	/**
	 * slideDown \ slideUp \ slideToggle
	 */
	
	function slideDown(element, duration = 350) {
		element.style.display = 'block';
		element.style.overflow = 'hidden';
		let height = element.scrollHeight;
		element.style.height = 0;
		setTimeout(() => {
			element.style.transition = `height ${duration}ms`;
			element.style.height = height + 'px';
			element.addEventListener('transitionend', function te() {
				element.removeEventListener('transitionend', te);
				element.style.removeProperty('height');
				element.style.removeProperty('transition');
				element.style.removeProperty('overflow');
			});
		}, 0);
	}
	
	function slideUp(element, duration = 350) {
		element.style.height = element.offsetHeight + 'px';
		element.style.overflow = 'hidden';
		setTimeout(() => {
			element.style.transition = `height ${duration}ms`;
			element.style.height = '0';
			element.addEventListener('transitionend', function te() {
				element.removeEventListener('transitionend', te);
				if (element.style.height === '0px') {
					element.style.display = 'none';
				}
				element.style.removeProperty('height');
				element.style.removeProperty('transition');
				element.style.removeProperty('overflow');
			});
		}, 0);
	}
	
	function slideToggle(element, duration = 350) {
		if (window.getComputedStyle(element).display === 'none') {
			return slideDown(element, duration);
		} else {
			return slideUp(element, duration);
		}
	}
	
	/**
	 * Cookies
	 */
	function csGetCookie( name ) {
		let matches = document.cookie.match( new RegExp(
			"(?:^|; )" + name.replace( /([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1' ) + "=([^;]*)"
		) );
		return matches ? decodeURIComponent( matches[ 1 ] ) : undefined;
	}
	
	function csSetCookie( name, value, props = {} ) {
	
		props = {
			path: '/'
		};
	
		if ( props.expires instanceof Date ) {
			props.expires = props.expires.toUTCString();
		}
	
		let updatedCookie = encodeURIComponent( name ) + "=" + encodeURIComponent( value );
	
		for ( let optionKey in props ) {
			updatedCookie += "; " + optionKey;
			let optionValue = props[ optionKey ];
			if ( optionValue !== true ) {
				updatedCookie += "=" + optionValue;
			}
		}
	
		document.cookie = updatedCookie;
	}
	
	
	
	
	/***/ })
	/******/ 	]);
	/************************************************************************/
	/******/ 	// The module cache
	/******/ 	var __webpack_module_cache__ = {};
	/******/ 	
	/******/ 	// The require function
	/******/ 	function __webpack_require__(moduleId) {
	/******/ 		// Check if module is in cache
	/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
	/******/ 		if (cachedModule !== undefined) {
	/******/ 			return cachedModule.exports;
	/******/ 		}
	/******/ 		// Create a new module (and put it into the cache)
	/******/ 		var module = __webpack_module_cache__[moduleId] = {
	/******/ 			exports: {}
	/******/ 		};
	/******/ 	
	/******/ 		// Execute the module function
	/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
	/******/ 	
	/******/ 		// Return the exports of the module
	/******/ 		return module.exports;
	/******/ 	}
	/******/ 	
	/************************************************************************/
	/******/ 	/* webpack/runtime/define property getters */
	/******/ 	(() => {
	/******/ 		__webpack_require__.d = (exports, definition) => {
	/******/ 			for(var key in definition) {
	/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
	/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
	/******/ 				}
	/******/ 			}
	/******/ 		};
	/******/ 	})();
	/******/ 	
	/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
	/******/ 	(() => {
	/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
	/******/ 	})();
	/******/ 	
	/******/ 	/* webpack/runtime/make namespace object */
	/******/ 	(() => {
	/******/ 		__webpack_require__.r = (exports) => {
	/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
	/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
	/******/ 			}
	/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
	/******/ 		};
	/******/ 	})();
	/******/ 	
	/************************************************************************/
	var __webpack_exports__ = {};
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	var __webpack_exports__ = {};
	__webpack_require__.r(__webpack_exports__);
	/* harmony import */ var _utility_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(1);
	/** ----------------------------------------------------------------------------
	 * Footer Nav Menu */
	
	(function () {
	
		HTMLElement.prototype.responsiveNav = function () {
			this.classList.remove('menu-item-expanded');
			let previousElement = this.previousElementSibling;
	
			if (previousElement && previousElement.classList.contains('submenu-visible')) {
				previousElement.classList.remove('submenu-visible');
				(0,_utility_js__WEBPACK_IMPORTED_MODULE_0__.slideUp)(previousElement);
				this.parentElement.classList.remove('menu-item-expanded');
			} else {
				let parentOfParent = this.parentElement.parentElement;
	
				parentOfParent.querySelectorAll('.menu-item .sub-menu').forEach(subMenu => {
					subMenu.classList.remove('submenu-visible');
					(0,_utility_js__WEBPACK_IMPORTED_MODULE_0__.slideUp)(subMenu);
				});
	
				parentOfParent.querySelectorAll('.menu-item-expanded').forEach(item => {
					item.classList.remove('menu-item-expanded');
				});
	
				if (previousElement) {
					previousElement.classList.toggle('submenu-visible');
					(0,_utility_js__WEBPACK_IMPORTED_MODULE_0__.slideToggle)(previousElement);
				}
	
				this.parentElement.classList.toggle('menu-item-expanded');
			}
		};
	
		document.addEventListener('DOMContentLoaded', function () {
			let menuItems = document.querySelectorAll('.cs-footer-columns__nav .menu-item-has-children');
	
			menuItems.forEach(menuItem => {
				let span = document.createElement('span');
				menuItem.appendChild(span);
	
				span.addEventListener('click', function (e) {
					e.preventDefault();
					this.responsiveNav();
				});
			});
		});
	
	})();
	
	})();
	
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	var __webpack_exports__ = {};
	__webpack_require__.r(__webpack_exports__);
	/** ----------------------------------------------------------------------------
	 * Section toggles header panels and overlay */
	
	(function () {
		const body = document.body;
		const headerElement = document.querySelector('.cs-header');
		const headerOverlay = document.querySelector('.cs-header-overlay');
		const searchToggles = document.querySelectorAll('.cs-header__search-toggle, .cs-search__close');
		const burgerToggles = document.querySelectorAll('.cs-header__burger-toggle');
		const followToggles = document.querySelectorAll('.cs-follow__toggle');
	
		searchToggles.forEach(searchToggle => {
			searchToggle.addEventListener('click', function (e) {
				e.preventDefault();
	
				if (body.classList.contains('cs-offcanvas-active')) {
					body.classList.remove('cs-offcanvas-active');
				}
	
				if (headerElement.classList.contains('cs-burger-visible')) {
					headerElement.classList.remove('cs-burger-visible');
					body.classList.remove('cs-burger-active');
				}
	
				if (headerElement.classList.contains('cs-follow-visible')) {
					headerElement.classList.remove('cs-follow-visible');
					body.classList.remove('cs-follow-active');
				}
	
				headerElement.classList.toggle('cs-search-visible');
				body.classList.toggle('cs-search-active');
			});
		});
	
		burgerToggles.forEach(burgerToggle => {
			burgerToggle.addEventListener('click', function (e) {
				e.preventDefault();
	
				if (body.classList.contains('cs-offcanvas-active')) {
					body.classList.remove('cs-offcanvas-active');
				}
	
				if (headerElement.classList.contains('cs-search-visible')) {
					headerElement.classList.remove('cs-search-visible');
					body.classList.remove('cs-search-active');
				}
	
				if (headerElement.classList.contains('cs-follow-visible')) {
					headerElement.classList.remove('cs-follow-visible');
					body.classList.remove('cs-follow-active');
				}
	
				headerElement.classList.toggle('cs-burger-visible');
				body.classList.toggle('cs-burger-active');
			});
		});
	
		followToggles.forEach(followToggle => {
			followToggle.addEventListener('click', function (e) {
				e.preventDefault();
	
				if (body.classList.contains('cs-offcanvas-active')) {
					body.classList.remove('cs-offcanvas-active');
				}
	
				if (headerElement.classList.contains('cs-search-visible')) {
					headerElement.classList.remove('cs-search-visible');
					body.classList.remove('cs-search-active');
				}
	
				if (headerElement.classList.contains('cs-burger-visible')) {
					headerElement.classList.remove('cs-burger-visible');
					body.classList.remove('cs-burger-active');
				}
	
				headerElement.classList.toggle('cs-follow-visible');
				body.classList.toggle('cs-follow-active');
			});
		});
	
		if ( headerOverlay ) {
			headerOverlay.addEventListener('click', function (e) {
				e.preventDefault();
	
				if (headerElement.classList.contains('cs-search-visible')) {
					headerElement.classList.remove('cs-search-visible');
					body.classList.remove('cs-search-active');
				}
	
				if (headerElement.classList.contains('cs-burger-visible')) {
					headerElement.classList.remove('cs-burger-visible');
					body.classList.remove('cs-burger-active');
				}
	
				if (headerElement.classList.contains('cs-follow-visible')) {
					headerElement.classList.remove('cs-follow-visible');
					body.classList.remove('cs-follow-active');
				}
			});
		}
	
	})();
	
	})();
	
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	var __webpack_exports__ = {};
	__webpack_require__.r(__webpack_exports__);
	/** ----------------------------------------------------------------------------
	 * Header Scroll Scheme */
	
	document.addEventListener('DOMContentLoaded', function () {
	
		let transitionTimer;
	
		document.addEventListener('nav-stick', function (event) {
			if (document.body.classList.contains('cs-display-header-overlay')) {
	
				let headerSmart = document.querySelector('.cs-navbar-smart-enabled .cs-header, .cs-navbar-sticky-enabled .cs-header');
				let headerAlt = headerSmart.querySelector('.cs-container');
	
				headerAlt.classList.add("cs-header-elements-no-transition");
	
				clearTimeout(transitionTimer);
	
				transitionTimer = setTimeout(function () {
					headerAlt.classList.remove("cs-header-elements-no-transition");
				}, 300);
			}
		});
	
		document.addEventListener('nav-unstick', function (event) {
			if (document.body.classList.contains('cs-display-header-overlay')) {
	
				let headerSmart = document.querySelector('.cs-navbar-smart-enabled .cs-header, .cs-navbar-sticky-enabled .cs-header');
				let headerAlt = headerSmart.querySelector('.cs-container');
	
				headerAlt.classList.add("cs-header-elements-no-transition");
	
				clearTimeout(transitionTimer);
	
				transitionTimer = setTimeout(function () {
					headerAlt.classList.remove("cs-header-elements-no-transition");
				}, 300);
			}
		});
	
	});
	
	})();
	
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	var __webpack_exports__ = {};
	__webpack_require__.r(__webpack_exports__);
	/** ----------------------------------------------------------------------------
	 * Header Smart Streatch */
	
	document.addEventListener('DOMContentLoaded', function () {
	
		document.addEventListener('header-smart-stretch-scroll-sticky-scroll-init', function (event) {
			let headerParams = event.detail;
	
			window.addEventListener('scroll', function () {
				let scrolled = window.scrollY;
	
				let headerSmart = document.querySelector('.cs-navbar-smart-enabled .cs-header, .cs-navbar-sticky-enabled .cs-header');
				headerParams.headerSmartPosition = headerSmart ? headerSmart.offsetTop : 0;
	
				if (scrolled > headerParams.smartStart + headerParams.scrollPoint + 10 && scrolled > headerParams.scrollPrev) {
					if (scrolled > headerParams.smartStart + headerParams.headerLargeHeight + 200) {
						document.dispatchEvent(new CustomEvent('sticky-nav-hide', { detail: headerParams }));
					}
				} else {
					if (headerParams.scrollUpAmount >= headerParams.scrollPoint || scrolled === 0) {
						document.dispatchEvent(new CustomEvent('sticky-nav-visible', { detail: headerParams }));
					}
				}
	
				if (scrolled > headerParams.smartStart + headerParams.headerLargeHeight) {
					document.dispatchEvent(new CustomEvent('nav-stick', { detail: headerParams }));
				} else if (headerParams.headerSmartPosition <= headerParams.smartStart) {
					document.dispatchEvent(new CustomEvent('nav-unstick', { detail: headerParams }));
				}
	
				if (scrolled < headerParams.scrollPrev) {
					headerParams.scrollUpAmount += headerParams.scrollPrev - scrolled;
				} else {
					headerParams.scrollUpAmount = 0;
				}
	
				let wpAdminBar = document.querySelector('#wpadminbar');
				if (wpAdminBar && window.innerWidth <= 600 && scrolled >= headerParams.wpAdminBarHeight) {
					document.dispatchEvent(new CustomEvent('adminbar-mobile-scrolled', { detail: headerParams }));
				} else {
					document.dispatchEvent(new CustomEvent('adminbar-mobile-no-scrolled', { detail: headerParams }));
				}
	
				headerParams.scrollPrev = scrolled;
			});
	
		});
	
	});
	
	})();
	
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	var __webpack_exports__ = {};
	__webpack_require__.r(__webpack_exports__);
	})();
	
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	var __webpack_exports__ = {};
	__webpack_require__.r(__webpack_exports__);
	/** ----------------------------------------------------------------------------
	 * Mega Menu */
	
	document.addEventListener('DOMContentLoaded', function () {
		function loadMenuPosts(menuItem) {
			var dataTerm = menuItem.children[0].dataset.term,
				dataPosts = menuItem.children[0].dataset.posts,
				dataNumberposts = menuItem.children[0].dataset.numberposts,
				menuContainer,
				postsContainer;
	
			// Containers.
			if (menuItem.classList.contains('cs-mega-menu-term') || menuItem.classList.contains('cs-mega-menu-posts')) {
				menuContainer = menuItem;
				postsContainer = menuContainer.querySelector('.cs-mm__posts');
			}
	
			if (menuItem.classList.contains('cs-mega-menu-child-term')) {
				menuContainer = menuItem.closest('.sub-menu');
				postsContainer = menuContainer.querySelector('.cs-mm__posts[data-term="' + dataTerm + '"]');
			}
	
			// Check Menu Container.
			if (!menuContainer || typeof menuContainer === 'undefined') {
				return false;
			}
	
			// Check Container.
			if (!postsContainer || typeof postsContainer === 'undefined') {
				return false;
			}
	
			// Set Active.
			menuContainer.querySelectorAll('.menu-item, .cs-mm__posts').forEach(function (el) {
				el.classList.remove('cs-active-item');
			});
	
			menuItem.classList.add('cs-active-item');
			if (postsContainer) {
				postsContainer.classList.add('cs-active-item');
			}
	
			// Check Loading.
			if (menuItem.classList.contains('cs-mm-loading') || menuItem.classList.contains('loaded')) {
				return false;
			}
	
			// Create Data.
			var data = {
				'term': dataTerm,
				'posts': dataPosts,
				'per_page': dataNumberposts
			};
	
			if (typeof csco_mega_menu === 'undefined') {
				return;
			}
	
			// Get Results using fetch.
			menuItem.classList.add('cs-mm-loading');
			postsContainer.classList.add('cs-mm-loading');
	
			function encodeFormData(data) {
				return Object.keys(data).map(key => encodeURIComponent(key) + '=' + encodeURIComponent(data[key])).join('&');
			}
	
			// fetch(csco_mega_menu.rest_url)
			fetch(csco_mega_menu.rest_url, {
				method: 'POST',
				body: encodeFormData(data),
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				}
			})
				.then(response => response.json())
				.then(res => {
					if (res.status && 'success' === res.status) {
						// Set the loaded state.
						menuItem.classList.add('loaded');
						postsContainer.classList.add('loaded');
	
						// Check if there're any posts.
						if (res.content && res.content.length) {
							postsContainer.innerHTML = res.content;
							imagesLoaded(postsContainer, function () {
								// Append Data.
							});
						}
					}
				})
				.catch(error => {
					// Handle errors.
				})
				.finally(() => {
					// Set the loading state.
					menuItem.classList.remove('cs-mm-loading');
					postsContainer.classList.remove('cs-mm-loading');
				});
		}
	
		function getFirstTab(container) {
			var firstTab = false;
	
			container.querySelectorAll('.cs-mega-menu-child').forEach(function (el) {
				if ( ! firstTab && el.classList.contains('cs-mega-menu-child')) {
					firstTab = el;
				}
			});
	
			return firstTab;
		}
	
		function handleMenuHover(event) {
			var menuItem = event.currentTarget;
	
			if (menuItem.classList.contains('cs-mega-menu-posts') ||
				menuItem.classList.contains('cs-mega-menu-term') ||
				menuItem.classList.contains('cs-mega-menu-child')) {
				loadMenuPosts(menuItem);
			}
		}
	
		function handleTermsHover(event) {
			var menuItem = event.currentTarget;
			var tab = getFirstTab(menuItem);
	
			if (tab) {
				loadMenuPosts(tab);
			}
		}
	
		document.querySelectorAll('.cs-header__nav .menu-item.cs-mega-menu-posts').forEach(function (menuItem) {
			menuItem.addEventListener('mouseenter', handleMenuHover);
		});
	
		document.querySelectorAll('.cs-header__nav .menu-item.cs-mega-menu-term').forEach(function (menuItem) {
			menuItem.addEventListener('mouseenter', handleMenuHover);
		});
	
		document.querySelectorAll('.cs-header__nav .menu-item.cs-mega-menu-child').forEach(function (menuItem) {
			menuItem.addEventListener('mouseenter', handleMenuHover);
		});
	
		document.querySelectorAll('.cs-header__nav .menu-item.cs-mega-menu-terms').forEach(function (menuItem) {
			menuItem.addEventListener('mouseenter', handleTermsHover);
		});
	
		// Load First Tab on Navbar Ready.
		document.querySelectorAll('.cs-header__nav .menu-item.cs-mega-menu-terms').forEach(function (menuItem) {
			var tab = getFirstTab(menuItem);
	
			if (tab) {
				loadMenuPosts(tab);
			}
		});
	
		// Autoload Posts.
		document.querySelectorAll('.cs-header__nav .menu-item.cs-mega-menu-posts').forEach(function (menuItem) {
			loadMenuPosts(menuItem);
		});
	
		// Autoload Term.
		document.querySelectorAll('.cs-header__nav .menu-item.cs-mega-menu-term').forEach(function (menuItem) {
			loadMenuPosts(menuItem);
		});
	});
	
	})();
	
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	var __webpack_exports__ = {};
	__webpack_require__.r(__webpack_exports__);
	/** ----------------------------------------------------------------------------
	 * Navigation */
	
	class CscoNavigation {
		constructor() {
			this.headerParams = {
				headerLargeHeight: parseInt(getComputedStyle(document.documentElement).getPropertyValue('--cs-header-initial-height')),
				headerCompactHeight: parseInt(getComputedStyle(document.documentElement).getPropertyValue('--cs-header-height')),
				headerSmart: document.querySelector('.cs-navbar-smart-enabled .cs-header, .cs-navbar-sticky-enabled .cs-header'),
				wpAdminBar: document.querySelector('#wpadminbar'),
				headerBefore: document.querySelector('.cs-header-before'),
				headerStretch: document.querySelector('.cs-navbar-smart-enabled .cs-header-stretch'),
				wpAdminBarHeight: null,
				smartStart: null,
				scrollPoint: 200,
				scrollPrev: 200,
				scrollUpAmount: 0,
				headerSmartPosition: 0
			};
	
			this.initialize();
		}
	
		initialize() {
			if (document.body.classList.contains('wp-admin')) {
				return;
			}
			this.bindEvents();
		}
	
		bindEvents() {
			document.addEventListener('DOMContentLoaded', () => {
				this.smartLevels();
				this.adaptTablet();
				this.stickyScroll();
				this.headerClassesChange();
			});
	
			window.addEventListener('resize', () => {
				this.smartLevels();
				this.adaptTablet();
				this.stickyScroll();
			});
		}
	
		smartLevels() {
			let windowWidth = window.innerWidth;
	
			// Reset Calc.
			document.querySelectorAll('.cs-header__nav-inner li').forEach(el => {
				el.classList.remove('cs-sm__level', 'cs-sm-position-left', 'cs-sm-position-right');
			});
			document.querySelectorAll('.cs-header__nav-inner li .sub-menu').forEach(el => {
				el.classList.remove('cs-mm__position-init');
			});
	
			// Set Settings.
			document.querySelectorAll('.cs-header__nav-inner > li.menu-item:not(.cs-mm)').forEach(parent => {
				let position = 'cs-sm-position-right'; //default
				let objPrevWidth = 0;
	
				parent.querySelectorAll('.sub-menu').forEach(el => {
					// Reset child levels.
					if (el.parentElement.nextElementSibling) {
						el.parentElement.nextElementSibling.classList.add('cs-sm__level');
					}
	
					if (el.parentElement.classList.contains('cs-sm__level')) {
						el.parentElement.classList.remove('cs-mm-level');
						position = 'cs-sm-position-right'; //reset
						objPrevWidth = 0;
					}
	
					// Find out position items.
					let offset = el.getBoundingClientRect().left;
	
					if (position === 'cs-sm-position-right' && el.offsetWidth + offset > windowWidth) {
						position = 'cs-sm-position-left';
					}
	
					if (position === 'cs-sm-position-left' && offset - (el.offsetWidth + objPrevWidth) < 0) {
						position = 'cs-sm-position-right';
					}
	
					objPrevWidth = el.offsetWidth;
	
					el.classList.add('cs-sm-position-init');
					el.parentElement.classList.add(position);
				});
			});
		}
	
		adaptTablet() {
			// Click outside.
			document.addEventListener('touchstart', (e) => {
				if (!e.target.closest('.cs-header__nav-inner')) {
					document.querySelectorAll('.cs-header__nav-inner .menu-item-has-children').forEach(el => {
						el.classList.remove('submenu-visible');
					});
				} else {
					let parentMenuItem = e.target.closest('.menu-item');
					if (parentMenuItem) {
						if (parentMenuItem.previousElementSibling) {
							parentMenuItem.previousElementSibling.querySelectorAll('.menu-item').forEach(el => {
								el.classList.remove('submenu-visible');
							});
						}
						if (parentMenuItem.nextElementSibling) {
							parentMenuItem.nextElementSibling.classList.remove('submenu-visible');
						}
					}
				}
			});
	
			document.querySelectorAll('.cs-header__nav-inner .menu-item-has-children').forEach(el => {
				// Reset class.
				el.classList.remove('submenu-visible');
	
				// Remove expanded if exists.
				let expandedElem = el.querySelector('a > .expanded');
				if (expandedElem) {
					expandedElem.remove();
				}
	
				// Add a caret.
				if ('ontouchstart' in document.documentElement) {
					let aTag = el.querySelector('a');
					if (aTag) {
						let span = document.createElement('span');
						span.className = 'expanded';
						aTag.appendChild(span);
					}
				}
	
				// Check touch device.
				if ('ontouchstart' in document.documentElement) {
					el.classList.add('touch-device');
				}
	
				let expandedElement = el.querySelector('a .expanded');
				if (expandedElement) {
					expandedElement.addEventListener('touchstart', (e) => {
						e.preventDefault();
						el.classList.toggle('submenu-visible');
					}, { passive: false });
				}
	
				let anchor = el.querySelector('a');
				if (anchor && anchor.getAttribute('href') === '#') {
					anchor.addEventListener('touchstart', (e) => {
						e.preventDefault();
						if (!e.target.classList.contains('expanded')) {
							el.classList.toggle('submenu-visible');
						}
					}, { passive: false });
				}
			});
		}
	
		stickyScroll() {
			this.headerParams = {
				headerLargeHeight: parseInt(getComputedStyle(document.documentElement).getPropertyValue('--cs-header-initial-height')),
				headerCompactHeight: parseInt(getComputedStyle(document.documentElement).getPropertyValue('--cs-header-height')),
				headerSmart: document.querySelector('.cs-navbar-smart-enabled .cs-header, .cs-navbar-sticky-enabled .cs-header'),
				wpAdminBar: document.querySelector('#wpadminbar'),
				headerBefore: document.querySelector('.cs-header-before'),
				headerStretch: document.querySelector('.cs-navbar-smart-enabled .cs-header-stretch'),
				wpAdminBarHeight: null,
				smartStart: null,
				scrollPoint: 200,
				scrollPrev: 200,
				scrollUpAmount: 0,
				headerSmartPosition: 0
			};
	
			this.headerParams.wpAdminBarHeight = this.headerParams.wpAdminBar ? this.headerParams.wpAdminBar.offsetHeight : 0;
	
			if (this.headerParams.headerBefore) {
				this.headerParams.smartStart = this.headerParams.headerBefore.offsetTop;
			} else {
				this.headerParams.smartStart = this.headerParams.wpAdminBarHeight + (this.headerParams.headerSmart ? this.headerParams.headerSmart.offsetTop : 0);
			}
	
			window.addEventListener('scroll', () => {
				let scrolled = window.scrollY;
				this.headerParams.headerSmartPosition = this.headerParams.headerSmart ? this.headerParams.headerSmart.offsetTop : 0;
	
				if (scrolled > this.headerParams.smartStart + this.headerParams.scrollPoint + 10 && scrolled > this.headerParams.scrollPrev) {
					if (scrolled > this.headerParams.smartStart + this.headerParams.headerCompactHeight + 200) {
						document.dispatchEvent(new Event('sticky-nav-hide'));
					}
				} else {
					if (this.headerParams.scrollUpAmount >= this.headerParams.scrollPoint || scrolled === 0) {
						document.dispatchEvent(new Event('sticky-nav-visible'));
					}
				}
	
				if ( this.headerParams.headerSmart ) {
					if ( scrolled > this.headerParams.smartStart + this.headerParams.headerCompactHeight ) {
	
						document.dispatchEvent(new Event('nav-stick', { detail: this.headerParams }));
	
					} else if ( this.headerParams.headerSmartPosition <= this.headerParams.smartStart ) {
						document.dispatchEvent(new Event('nav-unstick', { detail: this.headerParams }));
					}
				}
	
				if (scrolled < this.headerParams.scrollPrev) {
					this.headerParams.scrollUpAmount += this.headerParams.scrollPrev - scrolled;
				} else {
					this.headerParams.scrollUpAmount = 0;
				}
	
				if (this.headerParams.wpAdminBar && window.innerWidth <= 600 && scrolled >= this.headerParams.wpAdminBarHeight) {
					document.dispatchEvent(new Event('adminbar-mobile-scrolled'));
				} else {
					document.dispatchEvent(new Event('adminbar-mobile-no-scrolled'));
				}
	
				this.headerParams.scrollPrev = scrolled;
			});
		}
	
		headerClassesChange() {
			document.addEventListener("sticky-nav-visible", event => {
				this.headerParams.headerSmart.classList.add('cs-header-smart-visible');
			});
	
			document.addEventListener("sticky-nav-hide", event => {
				this.headerParams.headerSmart.classList.remove('cs-header-smart-visible');
			});
	
			document.addEventListener("nav-stick", event => {
				this.headerParams.headerSmart.classList.add('cs-scroll-sticky');
			});
	
			document.addEventListener("nav-unstick", event => {
				this.headerParams.headerSmart.classList.remove('cs-scroll-sticky', 'cs-header-smart-visible');
			});
	
			document.addEventListener("adminbar-mobile-scrolled", event => {
				document.body.classList.add('cs-adminbar-mobile-scrolled');
			});
	
			document.addEventListener("adminbar-mobile-no-scrolled", event => {
				document.body.classList.remove('cs-adminbar-mobile-scrolled');
			});
		}
	}
	
	new CscoNavigation();
	
	})();	
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	var __webpack_exports__ = {};
	__webpack_require__.r(__webpack_exports__);
	/** ----------------------------------------------------------------------------
	 * Offcanvas */
	
	(function () {
		const body = document.body;
		const headerElement = document.querySelector('.cs-header');
		const offcanvasToggles = document.querySelectorAll('.cs-header__offcanvas-toggle, .cs-site-overlay, .cs-offcanvas__toggle');
	
		offcanvasToggles.forEach(offcanvasToggle => {
			offcanvasToggle.addEventListener('click', function (e) {
				e.preventDefault();
	
				if (headerElement.classList.contains('cs-search-visible')) {
					headerElement.classList.remove('cs-search-visible');
					body.classList.remove('cs-search-active');
				}
	
				if (headerElement.classList.contains('cs-burger-visible')) {
					headerElement.classList.remove('cs-burger-visible');
					body.classList.remove('cs-burger-active');
				}
	
				if (!body.classList.contains('cs-offcanvas-active')) {
					body.classList.add('cs-offcanvas-transition');
				} else {
					setTimeout(() => {
						body.classList.remove('cs-offcanvas-transition');
					}, 400);
				}
	
				body.classList.toggle('cs-offcanvas-active');
			});
		});
	
	})();
	
	})();
	
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	var __webpack_exports__ = {};
	__webpack_require__.r(__webpack_exports__);
	/** ----------------------------------------------------------------------------
	 * Responsive Embeds */
	
	(function () {
		/**
		 * Add max-width & max-height to <iframe> elements, depending on their width & height props.
		 */
		function initResponsiveEmbeds() {
			let proportion, parentWidth;
			let iframes = document.querySelectorAll('.entry-content iframe');
	
			// Loop through iframe elements.
			iframes.forEach(iframe => {
				// Don't handle if the parent automatically resizes itself.
				if (iframe.closest('div[data-video-start], div[data-video-end]')) {
					return;
				}
				// Only continue if the iframe has a width & height defined.
				if (iframe.width && iframe.height) {
					// Calculate the proportion/ratio based on the width & height.
					proportion = parseFloat(iframe.width) / parseFloat(iframe.height);
					// Get the parent element's width.
					parentWidth = parseFloat(window.getComputedStyle(iframe.parentElement).width);
					// Set the max-width & height.
					iframe.style.maxWidth = '100%';
					iframe.style.maxHeight = Math.round(parentWidth / proportion) + 'px';
				}
			});
		}
	
		// Document ready.
		document.addEventListener('DOMContentLoaded', function () {
			initResponsiveEmbeds();
		});
	
		// Post load. This assumes you have an event "post-load" being dispatched on the body element.
		document.body.addEventListener('post-load', function () {
			initResponsiveEmbeds();
		});
	
		// Window resize.
		window.addEventListener('resize', function () {
			initResponsiveEmbeds();
		});
	
		// Run on initial load.
		initResponsiveEmbeds();
	
	})();
	
	})();
	
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	var __webpack_exports__ = {};
	__webpack_require__.r(__webpack_exports__);
	/* harmony import */ var _utility_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(1);
	/** ----------------------------------------------------------------------------
	 * Color Scheme Toogle */
	
	
	
	const cscoDarkMode = {
		init: function () {
			this.initMode();
	
			window.matchMedia('(prefers-color-scheme: dark)').addListener((e) => {
				this.initMode();
			});
	
			document.querySelectorAll('.cs-site-scheme-toggle').forEach(toggle => {
				toggle.onclick = () => {
					if ('dark' === document.body.getAttribute('data-scheme')) {
						this.changeScheme('light', true);
					} else {
						this.changeScheme('dark', true);
					}
				};
			});
		},
	
		detectColorScheme: function (color) {
			var level = 190;
			var alpha = 1;
			var rgba = [255, 255, 255];
			var color_rgba = false;
	
			color = color.trim();
	
			// Excludes.
			if ( ['#0e131a'].includes( color ) ) {
				return 'dark';
			}
	
			if ('#' === color[0]) {
				color = color.replace('#', '').trim();
	
				if (3 === color.length) {
					color = color[0] + color[0] + color[1] + color[1] + color[2] + color[2];
				}
	
				rgba[0] = parseInt(color.substr(0, 2), 16);
				rgba[1] = parseInt(color.substr(2, 2), 16);
				rgba[2] = parseInt(color.substr(4, 2), 16);
			} else if ((color_rgba = color.replace(/\s/g, '').match(/^rgba?\((\d+),(\d+),(\d+),?([^,\s)]+)?/i))) {
				rgba[0] = parseInt(color_rgba[1]);
				rgba[1] = parseInt(color_rgba[2]);
				rgba[2] = parseInt(color_rgba[3]);
	
				if (color_rgba[4] !== undefined) {
					alpha = parseFloat(color_rgba[4]);
				}
			}
	
			rgba.forEach(function myFunction(channel, key, stack) {
				stack[key] = String(channel + Math.ceil((255 - channel) * (1 - alpha))).padStart(2, '0');
			});
	
			var scheme = 'light';
	
			var brightness = ((rgba[0] * 299) + (rgba[1] * 587) + (rgba[2] * 114)) / 1000;
	
			if (rgba[0] === rgba[1] && rgba[1] === rgba[2]) {
				if (brightness < level) {
					scheme = 'dark';
				}
			} else {
				if (brightness < level) {
					scheme = 'inverse';
				}
			}
	
			return scheme;
		},
	
		setIndividualScheme: function () {
			var list = {
				'.cs-header': '--cs-header-background',
				'.cs-header__nav-inner .sub-menu': '--cs-header-submenu-background',
				'.cs-header-topbar': '--cs-header-topbar-background',
				'.cs-offcanvas': '--cs-offcanvas-background',
				'.cs-footer': '--cs-footer-background',
			};
	
			function createClosure(key) {
				return function (element) {
					var color = getComputedStyle(element).getPropertyValue(list[key]);
					var scheme = cscoDarkMode.detectColorScheme(color);
					element.setAttribute('data-scheme', scheme);
				};
			}
	
			for (var key in list) {
				if (list.hasOwnProperty(key)) {
					var elements = document.querySelectorAll(key);
	
					if (elements.length <= 0) {
						continue;
					}
	
					elements.forEach(createClosure(key));
				}
			}
		},
	
		initMode: function () {
			let siteScheme = false;
	
			switch (csLocalize.siteSchemeMode) {
				case 'dark':
					siteScheme = 'dark';
					break;
				case 'light':
					siteScheme = 'light';
					break;
				case 'system':
					siteScheme = 'auto';
					break;
			}
	
			if (csLocalize.siteSchemeToogle) {
				if ('light' === (0,_utility_js__WEBPACK_IMPORTED_MODULE_0__.csGetCookie)('_color_schema')) {
					siteScheme = 'light';
				}
				if ('dark' === (0,_utility_js__WEBPACK_IMPORTED_MODULE_0__.csGetCookie)('_color_schema')) {
					siteScheme = 'dark';
				}
			}
	
			this.setIndividualScheme();
	
			if (siteScheme && siteScheme !== document.body.getAttribute('data-scheme')) {
				this.changeScheme(siteScheme, false);
			}
		},
	
		changeScheme: function (siteScheme, cookie) {
			document.body.classList.add('cs-scheme-toggled');
			document.body.setAttribute('data-scheme', siteScheme);
	
			this.setIndividualScheme();
	
			if (cookie) {
				(0,_utility_js__WEBPACK_IMPORTED_MODULE_0__.csSetCookie)('_color_schema', siteScheme, { expires: 2592000 });
			}
	
			setTimeout(() => {
				document.body.classList.remove('cs-scheme-toggled');
			}, 100);
		}
	};
	
	cscoDarkMode.init();
	
	})();
	
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	var __webpack_exports__ = {};
	__webpack_require__.r(__webpack_exports__);
	/** ----------------------------------------------------------------------------
	 * Scroll to top */
	
	( function() {
	
		const section = 'cs-scroll-top';
		const activeClass = 'is-active';
		const offset = 200;
	
		const scrollToTop = () => {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		};
	
		const scrollToTopButton = document.querySelector(`.${section}`);
	
		if (scrollToTopButton) {
			const progressPath = scrollToTopButton.querySelector(`.${section}-progress path`);
			const pathLength = progressPath.getTotalLength();
	
			progressPath.style.transition = progressPath.style.WebkitTransition = 'none';
			progressPath.style.strokeDasharray = `${pathLength} ${pathLength}`;
			progressPath.style.strokeDashoffset = pathLength;
			progressPath.getBoundingClientRect();
			progressPath.style.transition = progressPath.style.WebkitTransition = 'stroke-dashoffset 10ms linear';
	
			const updateProgress = function () {
				const scroll = window.scrollY || window.scrollTop || document.documentElement.scrollTop;
				const docHeight = Math.max(
					document.body.scrollHeight,
					document.documentElement.scrollHeight,
					document.body.offsetHeight,
					document.documentElement.offsetHeight,
					document.body.clientHeight,
					document.documentElement.clientHeight
				);
				const windowHeight = Math.max(
					document.documentElement.clientHeight,
					window.innerHeight || 0
				);
				const height = docHeight - windowHeight;
				var progress = pathLength - (scroll * pathLength) / height;
				progressPath.style.strokeDashoffset = progress;
			};
	
			updateProgress();
	
			scrollToTopButton.addEventListener('click', scrollToTop);
	
			window.addEventListener('scroll', () => {
				updateProgress();
	
				const scrollPos = window.scrollY || window.scrollTop || document.getElementsByTagName('html')[0].scrollTop;
				if (scrollPos > offset) {
					scrollToTopButton.classList.add(activeClass);
				} else {
					scrollToTopButton.classList.remove(activeClass);
				}
			});
		}
	
	} )();
	
	})();
	
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	var __webpack_exports__ = {};
	__webpack_require__.r(__webpack_exports__);
	/** ----------------------------------------------------------------------------
	 * Sticky Sidebar */
	
	(function() {
		let stickyElementsSmart = [],
			stickyElements = [];
	
		stickyElementsSmart.push(
			'.cs-navbar-smart-enabled.cs-stick-to-top .cs-single-product .entry-summary',
			'.cs-sticky-sidebar-enabled.cs-navbar-smart-enabled.cs-stick-to-top .cs-sidebar__inner',
			'.cs-sticky-sidebar-enabled.cs-navbar-smart-enabled.cs-stick-last .cs-sidebar__inner .widget:last-child'
		);
	
		stickyElements.push(
			'.cs-navbar-sticky-enabled.cs-stick-to-top .cs-single-product .entry-summary',
			'.cs-sticky-sidebar-enabled.cs-navbar-sticky-enabled.cs-stick-to-top .cs-sidebar__inner',
			'.cs-sticky-sidebar-enabled.cs-navbar-sticky-enabled.cs-stick-last .cs-sidebar__inner .widget:last-child'
		);
	
		document.addEventListener("DOMContentLoaded", function() {
			let headerStick = document.querySelector('.cs-header'),
				wpAdminBar = document.querySelector('#wpadminbar'),
				headerStickHeight = headerStick ? headerStick.offsetHeight : 0,
				wpAdminBarHeight = wpAdminBar ? wpAdminBar.offsetHeight : 0,
				headerStretch = document.querySelector('.cs-header-stretch'),
				headerStretchHeight = headerStretch ? headerStretch.offsetHeight : 0,
				allHeight = headerStickHeight + wpAdminBarHeight + 20,
				windowWidth = window.innerWidth;
	
			if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
				stickyElementsSmart.push('.cs-sticky-sidebar-enabled.cs-stick-to-bottom .cs-sidebar__inner');
				stickyElements.push('.cs-sticky-sidebar-enabled.cs-stick-to-bottom .cs-sidebar__inner');
			}
	
			stickyElementsSmart = stickyElementsSmart.join(',');
			stickyElements = stickyElements.join(',');
	
			document.addEventListener('sticky-nav-visible', function() {
				headerStickHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--cs-header-height'));
				allHeight = (headerStretchHeight || 0) + (wpAdminBarHeight || 0) + 20;
	
				document.querySelectorAll(stickyElementsSmart).forEach(el => {
					el.style.top = allHeight + 'px';
				});
			});
	
			document.addEventListener('sticky-nav-hide', function() {
				headerStickHeight = 0;
				allHeight = (headerStickHeight || 0) + (wpAdminBarHeight || 0) + 20;
	
				document.querySelectorAll(stickyElementsSmart).forEach(el => {
					el.style.top = allHeight + 'px';
				});
			});
	
			document.addEventListener('stretch-nav-to-small', function() {
				headerStretchHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--cs-header-height'));
				allHeight = (headerStretchHeight || 0) + (wpAdminBarHeight || 0) + 20;
	
				if (headerStretch && headerStretch.classList.contains("cs-scroll-sticky") && !headerStretch.classList.contains("cs-scroll-active")) {
					document.querySelectorAll(stickyElementsSmart).forEach(el => {
						el.style.top = allHeight + 'px';
					});
				}
			});
	
			document.addEventListener('stretch-nav-to-big', function() {
				headerStretchHeight = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--cs-header-initial-height'));
			});
	
			if (document.body.classList.contains('cs-navbar-smart-enabled') && windowWidth >= 1020) {
				allHeight = (headerStretchHeight || 0) + (wpAdminBarHeight || 0) + 20;
	
				document.querySelectorAll(stickyElementsSmart).forEach(el => {
					el.style.top = allHeight + 'px';
				});
			} else if (document.body.classList.contains('cs-navbar-sticky-enabled') && windowWidth >= 1020) {
				allHeight = (headerStretchHeight || 0) + (wpAdminBarHeight || 0) + 20;
	
				document.querySelectorAll(stickyElements).forEach(el => {
					el.style.top = allHeight + 'px';
				});
			}
	
			window.addEventListener('resize', function() {
				let windowWidthResize = window.innerWidth;
				if (windowWidthResize < 1020) {
					document.querySelectorAll(stickyElements).forEach(el => {
						el.removeAttribute('style');
					});
					document.querySelectorAll(stickyElementsSmart).forEach(el => {
						el.removeAttribute('style');
					});
				}
			});
		});
	})();
	
	})();
	
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
	(() => {
	__webpack_require__.r(__webpack_exports__);
	/* harmony import */ var _utility_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(1);
	/** ----------------------------------------------------------------------------
	 * Widget Nav Menu */
	
	(function () {
	
		HTMLElement.prototype.responsiveNav = function () {
			this.classList.remove('menu-item-expanded');
			let previousElement = this.previousElementSibling;
	
			if (previousElement && previousElement.classList.contains('submenu-visible')) {
				previousElement.classList.remove('submenu-visible');
				(0,_utility_js__WEBPACK_IMPORTED_MODULE_0__.slideUp)(previousElement);
				this.parentElement.classList.remove('menu-item-expanded');
			} else {
				let parentOfParent = this.parentElement.parentElement;
	
				parentOfParent.querySelectorAll('.menu-item .sub-menu').forEach(subMenu => {
					subMenu.classList.remove('submenu-visible');
					(0,_utility_js__WEBPACK_IMPORTED_MODULE_0__.slideUp)(subMenu);
				});
	
				parentOfParent.querySelectorAll('.menu-item-expanded').forEach(item => {
					item.classList.remove('menu-item-expanded');
				});
	
				if (previousElement) {
					previousElement.classList.toggle('submenu-visible');
					(0,_utility_js__WEBPACK_IMPORTED_MODULE_0__.slideToggle)(previousElement);
				}
	
				this.parentElement.classList.toggle('menu-item-expanded');
			}
		};
	
		document.addEventListener('DOMContentLoaded', function () {
			let menuItems = document.querySelectorAll('.widget_nav_menu .menu-item-has-children');
	
			menuItems.forEach(menuItem => {
				let span = document.createElement('span');
				menuItem.appendChild(span);
	
				span.addEventListener('click', function (e) {
					e.preventDefault();
					this.responsiveNav();
				});
	
				let anchor = menuItem.children[0];
				if (anchor && anchor.tagName === 'A' && anchor.getAttribute('href') === '#') {
					anchor.addEventListener('click', function (e) {
						e.preventDefault();
						this.nextElementSibling.nextElementSibling.responsiveNav();
					});
				}
			});
		});
	
	})();
	
	})();
	
	/******/ })()
	;
