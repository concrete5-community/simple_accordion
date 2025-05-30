;(function() {

function ready() {
    document.querySelectorAll('.simple-accordion-title-shell').forEach((el) => {
        el.addEventListener('click', () => {
            const cls = el.parentElement.classList;
            if (cls.contains('open')) {
                cls.remove('open');
                cls.add('closed');
            } else if (cls.contains('closed')) {
                cls.remove('closed');
                cls.add('open');
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ready);
} else {
    ready();
}

})();
