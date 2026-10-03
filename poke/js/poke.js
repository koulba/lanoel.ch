/* Poké Lanoël — collection et sélection des échanges */
(function () {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf"]')?.content || '';
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

    /* ------------------------------------------------------------------
       Page Collection
       ------------------------------------------------------------------ */
    const grid = document.getElementById('grid');
    if (grid && window.POKE) {
        const P = window.POKE;
        const qty = Object.assign({}, P.qty);
        const state = { set: P.sets[0].id, filter: 'all', search: '' };
        try {
            const saved = JSON.parse(localStorage.getItem('poke-view') || '{}');
            if (P.sets.some(s => s.id === saved.set)) state.set = saved.set;
            if (saved.filter) state.filter = saved.filter;
        } catch (e) { /* stockage indisponible */ }

        const byId = Object.fromEntries(P.cards.map(c => [c.id, c]));
        const sheet = document.getElementById('cardSheet');
        const sheetBody = document.getElementById('sheetBody');
        const timers = {};

        const matches = card => {
            const q = qty[card.id] || 0;
            switch (state.filter) {
                case 'missing': return q === 0;
                case 'owned': return q > 0;
                case 'doubles': return q > 1;
                case 'offered': return q === 0 && !!P.offers[card.id];
                default: return true;
            }
        };

        function tileHtml(card) {
            const q = qty[card.id] || 0;
            const cls = q === 0 ? 'missing' : (q > 1 ? 'double' : '');
            const offer = q === 0 && P.offers[card.id] ? `<span class="offer" title="Dispo chez un ami">${P.offers[card.id].length}</span>` : '';
            return `<div class="tile ${cls}" data-id="${esc(card.id)}">
                <button type="button" class="art" data-open aria-label="${esc(card.name)}">
                    <img loading="lazy" referrerpolicy="no-referrer" src="${esc(card.img)}" alt="">
                    <span class="num">${esc(card.original_num ? card.original_num : card.num)}</span>
                    ${q > 0 ? `<span class="qty">×${q}</span>` : ''}${offer}
                </button>
                <div class="stepper">
                    <button type="button" data-step="-1" aria-label="Retirer">−</button>
                    <output>${q}</output>
                    <button type="button" data-step="1" aria-label="Ajouter">+</button>
                </div>
                <span class="name">${esc(card.name)}</span>
            </div>`;
        }

        function updateProgress() {
            P.sets.forEach(set => {
                const cards = P.cards.filter(c => c.set === set.id);
                const owned = cards.filter(c => (qty[c.id] || 0) > 0).length;
                const el = document.querySelector(`[data-progress="${set.id}"]`);
                if (el) el.textContent = `${owned} / ${cards.length}`;
                if (set.id === state.set) {
                    document.getElementById('progressBar').style.width = (cards.length ? owned / cards.length * 100 : 0) + '%';
                }
            });
        }

        function render() {
            const q = norm(state.search.trim());
            const list = P.cards.filter(c =>
                (q ? (norm(c.name).includes(q) || norm(c.name_en).includes(q) || c.num.includes(q)) : c.set === state.set)
                && matches(c));
            grid.innerHTML = list.map(tileHtml).join('');
            document.getElementById('empty').hidden = list.length > 0;
            document.querySelectorAll('#setTabs button').forEach(b => b.classList.toggle('on', b.dataset.set === state.set));
            document.querySelectorAll('#filters button').forEach(b => b.classList.toggle('on', b.dataset.filter === state.filter));
            updateProgress();
            try { localStorage.setItem('poke-view', JSON.stringify({ set: state.set, filter: state.filter })); } catch (e) {}
        }

        function refreshTile(id) {
            const old = grid.querySelector(`.tile[data-id="${CSS.escape(id)}"]`);
            if (old) old.outerHTML = tileHtml(byId[id]);
            updateProgress();
            if (sheet.open && sheet.dataset.id === id) openSheet(id);
        }

        function setQty(id, value) {
            qty[id] = Math.max(0, Math.min(99, value));
            refreshTile(id);
            // On attend que l'utilisateur ait fini de taper avant d'enregistrer
            clearTimeout(timers[id]);
            timers[id] = setTimeout(() => save(id, qty[id]), 450);
        }

        function save(id, value) {
            const body = new URLSearchParams({ action: 'set_qty', card_id: id, qty: value, csrf });
            fetch('api.php', { method: 'POST', body, credentials: 'same-origin' })
                .then(r => r.ok ? r.json() : Promise.reject(r.status))
                .catch(() => {
                    alertToast('Enregistrement impossible, vérifie ta connexion.');
                });
        }

        function alertToast(text) {
            let t = document.querySelector('.flash.toast');
            if (!t) {
                t = document.createElement('div');
                t.className = 'flash toast';
                t.style.cssText = 'position:fixed;left:16px;right:16px;bottom:calc(var(--tabbar-h) + 16px);z-index:60;width:auto;background:#3a1010;border-color:rgba(255,46,46,.5)';
                document.body.appendChild(t);
            }
            t.textContent = text;
            clearTimeout(t._h);
            t._h = setTimeout(() => t.remove(), 3500);
        }

        function openSheet(id) {
            const card = byId[id];
            const q = qty[id] || 0;
            const offers = P.offers[id] || [];
            const needs = q > 1 ? (P.needs[id] || []) : [];
            const setName = P.sets.find(s => s.id === card.set)?.name || '';
            sheet.dataset.id = id;
            sheetBody.innerHTML = `
                <img class="sheet-img" referrerpolicy="no-referrer" src="${esc(card.img_hd || card.img)}" alt="">
                <h2>${esc(card.name)}</h2>
                <p class="meta">${esc(setName)} · ${esc(card.num)}${card.original_num ? ` (n° d'origine ${esc(card.original_num)})` : ''}${card.rarity ? ' · ' + esc(card.rarity) : ''}</p>
                <div class="stepper" data-id="${esc(id)}">
                    <button type="button" data-step="-1" aria-label="Retirer">−</button>
                    <output>${q}</output>
                    <button type="button" data-step="1" aria-label="Ajouter">+</button>
                </div>
                ${offers.length ? `<div class="who"><h3>En double chez</h3><ul>${offers.map(o =>
                    `<li><a href="trade.php?with=${o.id}"><span>${esc(o.name)}${o.spare > 1 ? ` (${o.spare} dispo)` : ''}</span><span>Proposer →</span></a></li>`).join('')}</ul></div>` : ''}
                ${needs.length ? `<div class="who"><h3>Ton double ferait plaisir à</h3><ul>${needs.map(o =>
                    `<li><a href="trade.php?with=${o.id}"><span>${esc(o.name)}</span><span>Proposer →</span></a></li>`).join('')}</ul></div>` : ''}
            `;
            if (!sheet.open) sheet.showModal();
        }

        grid.addEventListener('click', e => {
            const tile = e.target.closest('.tile');
            if (!tile) return;
            const step = e.target.closest('[data-step]');
            if (step) setQty(tile.dataset.id, (qty[tile.dataset.id] || 0) + Number(step.dataset.step));
            else if (e.target.closest('[data-open]')) openSheet(tile.dataset.id);
        });
        sheetBody.addEventListener('click', e => {
            const step = e.target.closest('[data-step]');
            if (step) setQty(sheet.dataset.id, (qty[sheet.dataset.id] || 0) + Number(step.dataset.step));
        });
        // Fermer la fiche en touchant le fond
        sheet.addEventListener('click', e => { if (e.target === sheet) sheet.close(); });

        document.getElementById('setTabs').addEventListener('click', e => {
            const b = e.target.closest('button');
            if (!b) return;
            state.set = b.dataset.set;
            state.search = '';
            document.getElementById('search').value = '';
            render();
            window.scrollTo({ top: 0 });
        });
        document.getElementById('filters').addEventListener('click', e => {
            const b = e.target.closest('button');
            if (!b) return;
            state.filter = b.dataset.filter;
            render();
        });
        document.getElementById('search').addEventListener('input', e => {
            state.search = e.target.value;
            render();
        });

        // Envoie les modifications en attente si on quitte la page
        window.addEventListener('pagehide', () => {
            Object.keys(timers).forEach(id => {
                clearTimeout(timers[id]);
                navigator.sendBeacon('api.php', new URLSearchParams({ action: 'set_qty', card_id: id, qty: qty[id], csrf }));
                delete timers[id];
            });
        });

        render();
    }

    /* ------------------------------------------------------------------
       Page Proposer un échange
       ------------------------------------------------------------------ */
    const tradeForm = document.getElementById('tradeForm');
    if (tradeForm) {
        const submit = document.getElementById('tradeSubmit');
        const totals = () => {
            const sum = side => [...tradeForm.querySelectorAll(`.pick-card[data-side="${side}"] input`)]
                .reduce((n, i) => n + Number(i.value), 0);
            const give = sum('give');
            const get = sum('get');
            tradeForm.querySelector('[data-total="give"]').textContent = give;
            tradeForm.querySelector('[data-total="get"]').textContent = get;
            submit.disabled = give + get === 0;
        };
        tradeForm.addEventListener('click', e => {
            const card = e.target.closest('.pick-card');
            if (!card) return;
            const input = card.querySelector('input');
            const max = Number(card.dataset.max);
            // 0 -> 1 -> ... -> max -> 0
            const next = Number(input.value) >= max ? 0 : Number(input.value) + 1;
            input.value = next;
            card.classList.toggle('sel', next > 0);
            const badge = card.querySelector('.pick-count');
            badge.hidden = next === 0;
            badge.textContent = next;
            totals();
        });
        // Ne pas envoyer les cartes non sélectionnées
        tradeForm.addEventListener('submit', () => {
            tradeForm.querySelectorAll('.pick-card input').forEach(i => { if (i.value === '0') i.disabled = true; });
        });
        totals();
    }
})();
