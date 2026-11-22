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
        }).catch(function () {});
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootTelegram);
    } else {
        bootTelegram();
    }
})();
