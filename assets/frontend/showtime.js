document.addEventListener('DOMContentLoaded', () => {

    // ── All Dates expand/collapse ──────────────────────────
    // querySelectorAll damit mehrere Shortcodes auf einer Seite funktionieren
    document.querySelectorAll('.showtime-shows').forEach(wrapper => {
        const btn         = wrapper.querySelector('.showtime-btn-expand');
        const hiddenBlock = wrapper.querySelector('.showtime-hidden');

        if (!btn || !hiddenBlock) return;

        let expanded = false;

        btn.addEventListener('click', () => {
            expanded = !expanded;

            if (expanded) {
                hiddenBlock.style.display    = 'block';
                hiddenBlock.style.overflow   = 'hidden';
                hiddenBlock.style.transition = 'max-height 0.4s ease';
                hiddenBlock.style.maxHeight  = '0px';
                // Force reflow: Browser muss den 0-Zustand registrieren
                // bevor er zur Zielhöhe animiert
                void hiddenBlock.offsetHeight;
                hiddenBlock.style.maxHeight  = hiddenBlock.scrollHeight + 'px';
                btn.textContent = btn.dataset.labelLess || 'Show Less';
            } else {
                // Aktuelle Höhe explizit setzen, dann auf 0 animieren
                hiddenBlock.style.maxHeight  = hiddenBlock.scrollHeight + 'px';
                void hiddenBlock.offsetHeight;
                hiddenBlock.style.maxHeight  = '0px';
                hiddenBlock.addEventListener('transitionend', () => {
                    if (!expanded) hiddenBlock.style.display = 'none';
                }, { once: true });
                btn.textContent = btn.dataset.labelMore || 'All Dates';
            }
        });
    });

    // ── Presale Countdown ──────────────────────────────────
    const presaleEls = document.querySelectorAll('.showtime-presale');

    presaleEls.forEach(el => {
        const presaleDate = el.dataset.presale; // Ymd
        if (!presaleDate) return;

        // Parse Ymd → Date
        const y = presaleDate.substring(0, 4);
        const m = presaleDate.substring(4, 6) - 1;
        const d = presaleDate.substring(6, 8);
        const target = new Date(y, m, d, 10, 0, 0); // 10:00 Uhr Presale-Tag

        const countdownEl = el.querySelector('.showtime-countdown');
        if (!countdownEl) return;

        function update() {
            const now  = new Date();
            const diff = target - now;

            if (diff <= 0) {
                el.textContent = 'Presale live now!';
                return;
            }

            const days    = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours   = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            countdownEl.textContent = days > 0
                ? `${days}d ${hours}h ${minutes}m`
                : `${hours}h ${minutes}m ${seconds}s`;
        }

        update();
        setInterval(update, 1000);
    });

});