/* UnitWise Theme — theme.js */
(function () {
	'use strict';

	/* -----------------------------------------------------------------------
	   Frosted-glass scroll header
	----------------------------------------------------------------------- */
	const header = document.querySelector('.site-header');
	if (header) {
		const toggle = () => header.classList.toggle('is-scrolled', window.scrollY > 10);
		toggle();
		window.addEventListener('scroll', toggle, { passive: true });
	}

	/* -----------------------------------------------------------------------
	   Mobile hamburger menu
	----------------------------------------------------------------------- */
	const hamburger = document.querySelector('.hamburger-btn');
	const mobileNav = document.querySelector('.mobile-nav');
	if (hamburger && mobileNav) {
		const iconMenu  = hamburger.querySelector('.icon-menu');
		const iconClose = hamburger.querySelector('.icon-close');
		hamburger.addEventListener('click', () => {
			const open = mobileNav.hasAttribute('hidden');
			if (open) {
				mobileNav.removeAttribute('hidden');
			} else {
				mobileNav.setAttribute('hidden', '');
			}
			mobileNav.classList.toggle('is-open', open);
			hamburger.setAttribute('aria-expanded', String(open));
			if (iconMenu)  iconMenu.hidden  = open;
			if (iconClose) iconClose.hidden = !open;
		});
		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape' && !mobileNav.hasAttribute('hidden')) {
				mobileNav.setAttribute('hidden', '');
				mobileNav.classList.remove('is-open');
				hamburger.setAttribute('aria-expanded', 'false');
				if (iconMenu)  iconMenu.hidden  = false;
				if (iconClose) iconClose.hidden = true;
				hamburger.focus();
			}
		});
	}

	/* -----------------------------------------------------------------------
	   Dark mode toggle
	----------------------------------------------------------------------- */
	const themeBtn = document.querySelector('.theme-toggle-btn');
	const root = document.documentElement;

	function getStoredTheme () {
		try { return localStorage.getItem('uw-theme'); } catch { return null; }
	}
	function storeTheme (t) {
		try { localStorage.setItem('uw-theme', t); } catch {}
	}
	function applyTheme (t) {
		root.setAttribute('data-theme', t);
		if (themeBtn) {
			const moonEl = themeBtn.querySelector('.icon-moon');
			const sunEl  = themeBtn.querySelector('.icon-sun');
			if (moonEl) moonEl.hidden = (t === 'dark');
			if (sunEl)  sunEl.hidden  = (t !== 'dark');
		}
	}

	const stored = getStoredTheme();
	const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
	applyTheme(stored || (prefersDark ? 'dark' : 'light'));

	if (themeBtn) {
		themeBtn.addEventListener('click', () => {
			const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
			applyTheme(next);
			storeTheme(next);
		});
	}

	/* -----------------------------------------------------------------------
	   Entrance animations
	----------------------------------------------------------------------- */
	// Blog post cards: animate on load with stagger so they're never invisible
	document.querySelectorAll('.post-card').forEach((el, i) => {
		el.style.animationDelay = (i * 60) + 'ms';
		el.classList.add('animate-rise-sm');
	});

	// Category cards: animate when they scroll into view
	if ('IntersectionObserver' in window) {
		const io = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					entry.target.style.opacity = '';
					entry.target.classList.add('animate-rise-sm');
					io.unobserve(entry.target);
				}
			});
		}, { threshold: 0, rootMargin: '0px 0px 100px 0px' });

		document.querySelectorAll('.category-card').forEach((el) => {
			el.style.opacity = '0';
			io.observe(el);
		});
	}

	/* Load More — blog index (URL-aware)
	----------------------------------------------------------------------- */

	/* -----------------------------------------------------------------------
	   Load More — blog index (pushes ?page=N into the URL for shareability)
	----------------------------------------------------------------------- */
	const loadMoreBtn = document.querySelector('.btn--load-more');
	if (loadMoreBtn) {
		window.addEventListener('popstate', (e) => {
			const p = (e.state && e.state.page)
				? e.state.page
				: parseInt(new URLSearchParams(window.location.search).get('paged') || '1', 10);
			loadMoreBtn.dataset.page = String(p);
		});
	}
	if (loadMoreBtn && typeof uwBlog !== 'undefined') {
		const grid = document.querySelector('.post-grid');

		loadMoreBtn.addEventListener('click', async () => {
			const nextPage = parseInt(loadMoreBtn.dataset.page, 10) + 1;
			const maxPages = parseInt(grid ? grid.dataset.maxPages : '1', 10);

			loadMoreBtn.disabled = true;
			loadMoreBtn.textContent = 'Loading…';

			try {
				const body = new FormData();
				body.append('action', 'uw_load_more_posts');
				body.append('nonce',  uwBlog.nonce);
				body.append('page',   String(nextPage));

				const res  = await fetch(uwBlog.ajaxUrl, { method: 'POST', body });
				const data = await res.json();

				if (data.success && data.data.html && grid) {
					grid.insertAdjacentHTML('beforeend', data.data.html);
					loadMoreBtn.dataset.page = String(nextPage);

					// Update URL so page 2+ is bookmarkable/shareable
					const url = new URL(window.location.href);
					url.searchParams.set('paged', nextPage);
					history.pushState({ page: nextPage }, '', url.toString());

					// Animate newly added cards on insertion with stagger
					grid.querySelectorAll('.post-card:not(.animate-rise-sm)').forEach((el, i) => {
						el.style.animationDelay = (i * 60) + 'ms';
						el.classList.add('animate-rise-sm');
					});
				}

				if (data.success && (!data.data.hasMore || nextPage >= maxPages)) {
					const wrap = loadMoreBtn.closest('.load-more-wrap');
					if (wrap) wrap.remove();
				} else if (data.success) {
					loadMoreBtn.disabled = false;
					loadMoreBtn.textContent = 'Load More';
				} else {
					loadMoreBtn.disabled = false;
					loadMoreBtn.textContent = 'Load More';
				}
			} catch {
				loadMoreBtn.disabled = false;
				loadMoreBtn.textContent = 'Load More';
			}
		});
	}
})();
