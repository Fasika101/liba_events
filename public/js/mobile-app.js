(function () {
    'use strict';

    var mobileMq = window.matchMedia('(max-width: 991.98px)');

    function initMobileShell() {
        if (!document.body.classList.contains('layout-app')) {
            return;
        }

        if (mobileMq.matches) {
            document.body.classList.add('sidebar-collapse');
        }

        var pushMenu = document.querySelector('[data-widget="pushmenu"]');
        if (pushMenu) {
            pushMenu.addEventListener('click', function () {
                setTimeout(function () {
                    if (mobileMq.matches) {
                        document.body.classList.toggle(
                            'sidebar-open',
                            !document.body.classList.contains('sidebar-collapse')
                        );
                    }
                }, 50);
            });
        }

        document.querySelectorAll('.main-sidebar .nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (mobileMq.matches) {
                    document.body.classList.add('sidebar-collapse');
                    document.body.classList.remove('sidebar-open');
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileShell);
    } else {
        initMobileShell();
    }

    mobileMq.addEventListener('change', function (e) {
        if (e.matches) {
            document.body.classList.add('sidebar-collapse');
        }
    });
})();
