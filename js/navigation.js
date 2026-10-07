/** Navigation controls. */
(() => {
    const header = document.getElementById('headerNavigationContainer');
    const menu = document.getElementById('encounters-navigation');
    const toggle = header?.querySelector('.pkp_site_nav_toggle');
    if (!header || !menu || !toggle) return;

    const desktop = window.matchMedia('(min-width: 901px)');
    const language = header.querySelector('.encounters-language');
    language?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && language.open) {
            language.open = false;
            language.querySelector('summary').focus();
        }
    });
    const setOpen = (open) => {
        menu.classList.toggle('pkp_site_nav_menu--isOpen', open);
        toggle.classList.toggle('pkp_site_nav_toggle--transform', open);
        toggle.setAttribute('aria-expanded', String(open));
    };
    const submenus = [...menu.querySelectorAll('[data-encounters-submenu]')].map((button) => ({
        button,
        list: document.getElementById(button.getAttribute('aria-controls')),
    })).filter(({list}) => list);
    const closeSubmenus = (except = null) => {
        submenus.forEach(({button, list}) => {
            if (button === except) return;
            button.setAttribute('aria-expanded', 'false');
            list.hidden = true;
        });
    };

    submenus.forEach(({button, list}) => {
        list.hidden = true;
        button.hidden = false;
        button.addEventListener('click', () => {
            const open = button.getAttribute('aria-expanded') !== 'true';
            closeSubmenus(button);
            button.setAttribute('aria-expanded', String(open));
            list.hidden = !open;
        });
    });
    toggle.hidden = false;
    header.classList.add('encounters-navigation-ready');
    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        setOpen(open);
        if (!open) closeSubmenus();
    });
    toggle.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || desktop.matches) return;
        setOpen(false);
        closeSubmenus();
    });
    menu.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        const active = submenus.find(({button}) => button.getAttribute('aria-expanded') === 'true');
        if (active) {
            closeSubmenus();
            active.button.focus();
        } else if (!desktop.matches) {
            setOpen(false);
            toggle.focus();
        }
    });
    document.addEventListener('click', (event) => {
        if (language && !language.contains(event.target)) language.open = false;
        if (!header.contains(event.target)) {
            closeSubmenus();
            setOpen(false);
        }
    });
    desktop.addEventListener('change', () => {
        setOpen(false);
        closeSubmenus();
    });
    setOpen(false);
})();
