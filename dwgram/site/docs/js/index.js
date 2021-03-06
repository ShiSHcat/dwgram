let drawer;
(function() {
    const appbar = new mdc.topAppBar.MDCTopAppBar(document.querySelector('.mdc-top-app-bar'))
    drawer = new mdc.drawer.MDCDrawer(document.querySelector('.mdc-drawer'))
    appbar.listen('MDCTopAppBar:nav', _ => drawer.open = !drawer.open)
})();

// change selected button on scroll
(function() {
    console.log('a');
    const e = window;
    const e1 = document.querySelector('a.mdc-list-item[href="#s"]');
    const e2 = document.querySelector('a.mdc-list-item[href="#getchat"]');
    const e3 = document.querySelector('a.mdc-list-item[href="#messages"]');
    const sce2 = document.querySelector('#getchat');
    const sce3 = document.querySelector('#messages');
    e.addEventListener('scroll', _ => {
        let sc2 = sce2.offsetTop - 56 - 16;
        let tot = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        let sc3 = sce3.offsetTop > tot ? tot : sce3.offsetTop;
        let scTop = document.documentElement.scrollTop;
        //console.log(scTop, 0, sc2, sc3, sc4, tot);
        [e1, e2, e3].forEach(_ => _.classList.remove('mdc-list-item--activated'));
        if (0 <= scTop && scTop < sc2) {
            e1.classList.add('mdc-list-item--activated');
        } else if (sc2 <= scTop && scTop < sc3) {
            e2.classList.add('mdc-list-item--activated');
        } else if (sc3 <= scTop || scTop >= tot) {
            e3.classList.add('mdc-list-item--activated');
        }
    });
})();