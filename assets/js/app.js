(function () {
    document.querySelectorAll('[data-sidebar-toggle]').forEach(function (el) {
        el.addEventListener('click', function () {
            document.querySelector('.app-sidebar')?.classList.toggle('open');
            document.querySelector('.sidebar-backdrop')?.classList.toggle('show');
        });
    });

    document.querySelectorAll('[data-copy]').forEach(function (el) {
        el.addEventListener('click', function () {
            var value = el.getAttribute('data-copy') || '';
            if (!value) return;
            navigator.clipboard.writeText(value).then(function () {
                el.classList.add('copied');
                el.textContent = 'Copied';
                setTimeout(function () {
                    el.classList.remove('copied');
                    el.textContent = el.getAttribute('data-label') || 'Copy';
                }, 1600);
            });
        });
    });
})();
