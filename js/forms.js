/** Progressive enhancement for the shared OJS registration form. */
(() => {
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
