(function () {
    'use strict';

    function bootTelegram() {
        var tg = window.Telegram && window.Telegram.WebApp;
        if (! tg) {
            return;
        }

        try {
            tg.ready();
            tg.expand();
            if (tg.disableVerticalSwipes) {
                tg.disableVerticalSwipes();
            }
        } catch (e) {}

        var initData = tg.initData;
        if (! initData) {
            return;
        }

        var token = document.querySelector('meta[name="csrf-token"]');
        fetch('/discover/telegram-auth', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                initData: initData,
                locale: document.documentElement.lang === 'am' ? 'am' : 'en'
            })
        }).then(function (response) {
            return response.json().catch(function () { return null; });
        }).then(function (data) {
            if (! data || ! data.ok) {
                return;
            }

            var path = window.location.pathname || '';
            var onGate = path.indexOf('/discover/gate') !== -1;
            var onOnboarding = path.indexOf('/discover/onboarding') !== -1;

            if (data.onboarding && data.onboarding.required) {
                if (! onOnboarding && data.onboarding.redirect) {
                    window.location.href = data.onboarding.redirect;
                }
                return;
            }

            if (onGate || onOnboarding) {
                window.location.href = '/discover';
            }
        }).catch(function () {});
    }

    function bootAdCarousel() {
        var root = document.querySelector('[data-ad-carousel]');
        if (! root) {
            return;
        }

        var slides = Array.prototype.slice.call(root.querySelectorAll('[data-ad-slide]'));
        if (slides.length < 2) {
            return;
        }

        var dots = Array.prototype.slice.call(root.querySelectorAll('[data-ad-dot]'));
        var index = 0;
        var timer = null;

        function show(next) {
            index = (next + slides.length) % slides.length;
            slides.forEach(function (slide, i) {
                slide.classList.toggle('is-active', i === index);
            });
            dots.forEach(function (dot, i) {
                dot.classList.toggle('is-active', i === index);
            });
        }

        function start() {
            stop();
            timer = window.setInterval(function () {
                show(index + 1);
            }, 4500);
        }

        function stop() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var i = parseInt(dot.getAttribute('data-ad-dot'), 10) || 0;
                show(i);
                start();
            });
        });

        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);
        root.addEventListener('touchstart', stop, { passive: true });
        root.addEventListener('touchend', start, { passive: true });

        show(0);
        start();
    }

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-copy]');
        if (! btn) {
            return;
        }
        var text = btn.getAttribute('data-copy') || '';
        if (! text) {
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                var original = btn.textContent;
                btn.textContent = btn.getAttribute('data-copied-label') || 'Copied';
                setTimeout(function () { btn.textContent = original; }, 1200);
            });
        }
    });

    function boot() {
        bootTelegram();
        bootAdCarousel();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
