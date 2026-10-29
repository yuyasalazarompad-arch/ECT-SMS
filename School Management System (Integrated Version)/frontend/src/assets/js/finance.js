'use strict';

/**
 * Finance & Reporting interactions: Manila clock, menus, student search.
 */
(() => {
    const timeZone = 'Asia/Manila';
    const timeFormat = new Intl.DateTimeFormat('en-PH', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
        timeZone
    });
    const dateFormat = new Intl.DateTimeFormat('en-PH', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        timeZone
    });

    const clockTime = document.getElementById('fin-clock-time');
    const clockDate = document.getElementById('fin-clock-date');

    const tick = () => {
        const now = new Date();
        if (clockTime) {
            clockTime.textContent = timeFormat.format(now) + ' PHT';
        }
        if (clockDate) {
            clockDate.textContent = dateFormat.format(now);
        }
    };

    tick();
    window.setInterval(tick, 30000);

    const menus = [...document.querySelectorAll('details[data-fin-menu]')];

    document.addEventListener('click', (event) => {
        menus.forEach((menu) => {
            if (menu.open && !menu.contains(event.target)) {
                menu.open = false;
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            menus.forEach((menu) => {
                menu.open = false;
            });
        }
    });

    const search = document.querySelector('[data-fin-search]');

    if (search) {
        const table = document.querySelector(search.getAttribute('data-fin-search'));
        const body = table?.querySelector('tbody');
        const rows = body
            ? [...body.querySelectorAll('tr')].filter((row) => !row.hasAttribute('data-empty'))
            : [];
        let noMatchRow = null;

        const updateSearch = () => {
            const query = search.value.trim().toLowerCase();
            let visible = 0;

            rows.forEach((row) => {
                const match = query === '' || row.textContent.toLowerCase().includes(query);
                row.hidden = !match;
                if (match) {
                    visible++;
                }
            });

            if (rows.length > 0) {
                if (visible === 0) {
                    if (!noMatchRow) {
                        noMatchRow = document.createElement('tr');
                        noMatchRow.className = 'fin-search-empty';
                        const cell = document.createElement('td');
                        cell.colSpan = table.querySelectorAll('thead th').length;
                        cell.className = 'fin-empty';
                        cell.textContent = 'No students match your search.';
                        noMatchRow.appendChild(cell);
                    }
                    body.appendChild(noMatchRow);
                } else if (noMatchRow?.parentNode) {
                    noMatchRow.remove();
                }
            }
        };

        search.addEventListener('input', updateSearch);
    }
})();
