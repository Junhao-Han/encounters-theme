/** Progressive enhancement for shared OJS forms. */
(() => {
    // OJS 3.5 generates English month names in the search date filters.
    ['dateFromMonth', 'dateToMonth'].forEach((id) => {
        const select = document.getElementById(id);
        if (!select) return;
        const formatter = new Intl.DateTimeFormat(document.documentElement.lang, {month: 'short', timeZone: 'UTC'});
        [...select.options].forEach((option) => {
            const month = Number(option.value);
            if (Number.isInteger(month) && month >= 1 && month <= 12) {
                option.textContent = formatter.format(new Date(Date.UTC(2020, month - 1, 1)));
            }
        });
    });

    const reviewer = document.getElementById('reviewerOptinGroup');
    const interests = document.getElementById('reviewerInterests');
    if (reviewer && interests) {
        const update = () => {
            const selected = Boolean(reviewer.querySelector('input:checked'));
            interests.hidden = !selected;
            interests.classList.toggle('is_visible', selected);
        };
        reviewer.addEventListener('change', update);
        update();
    }

    document.querySelectorAll('#contextOptinGroup .roles').forEach((roles) => {
        const consent = roles.parentElement.querySelector('.context_privacy');
        if (!consent) return;
        const update = () => {
            const selected = Boolean(roles.querySelector('input:checked'));
            consent.hidden = !selected;
            consent.classList.toggle('context_privacy_visible', selected);
        };
        roles.addEventListener('change', update);
        update();
    });
})();
