/* LANoël — interactions et animations partagées */
(function () {
    'use strict';

    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var canHover = window.matchMedia('(hover: hover)').matches;

    // --- Écran de chargement (accueil, une fois par session) ---
    (function () {
        var loader = document.getElementById('loader');
        if (!loader) return;
        var pct = document.getElementById('pct');
        if (reduce) { loader.remove(); return; }
        document.documentElement.style.setProperty('--t0', '1.05s');
        var t0 = performance.now();
        (function step(now) {
            var p = Math.min(1, (now - t0) / 1000);
            if (pct) pct.textContent = Math.round(p * 100);
            if (p < 1) requestAnimationFrame(step);
            else loader.classList.add('done');
        })(t0);
        setTimeout(function () { loader.remove(); }, 2000);
        try { sessionStorage.setItem('lanoel-loaded', '1'); } catch (e) {}
    })();

    // --- Barre HUD : compacte au scroll, menu mobile ---
    (function () {
        var hud = document.getElementById('hud');
        if (!hud) return;
        function onScroll() { hud.classList.toggle('compact', window.scrollY > 40); }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        var burger = document.getElementById('hudBurger');
        var nav = document.getElementById('hudNav');
        if (burger && nav) {
            burger.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = nav.classList.toggle('open');
                burger.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.addEventListener('click', function (e) {
                if (nav.classList.contains('open') && !nav.contains(e.target)) {
                    nav.classList.remove('open');
                    burger.setAttribute('aria-expanded', 'false');
                }
            });
        }
    })();

    // --- Apparition au scroll (uniquement les éléments sous la ligne de flottaison) ---
    (function () {
        if (reduce || !('IntersectionObserver' in window)) return;
        var vh = window.innerHeight;
        var selector = '.game-card, .team-card, .participant-card, .board, .rank-item, .palmares-podium-card, ' +
            '.download-card, .step-card, .usage-card, .faq-item, .features-list li, .profile-view-stat-card, ' +
            '.profile-view-vote-card, .stat-box, .stat-card, .drawn-participant';
        var targets = document.querySelectorAll(selector);
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (!e.isIntersecting) return;
                e.target.classList.add('in');
                io.unobserve(e.target);
                e.target.querySelectorAll('[data-count]').forEach(countUp);
            });
        }, { threshold: .15, rootMargin: '0px 0px -8% 0px' });
        var i = 0;
        targets.forEach(function (el) {
            if (el.getBoundingClientRect().top > vh) {
                el.style.setProperty('--i', i++ % 8);
                el.classList.add('rv');
                io.observe(el);
            }
        });
        function countUp(el) {
            var end = parseInt(el.getAttribute('data-count'), 10);
            if (isNaN(end)) return;
            var t0 = performance.now(), dur = 1200;
            (function step(now) {
                var p = Math.min(1, (now - t0) / dur);
                p = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(end * p);
                if (p < 1) requestAnimationFrame(step);
            })(t0);
        }
    })();

    // --- Inclinaison 3D des cartes au survol ---
    (function () {
        if (reduce || !canHover) return;
        document.querySelectorAll('.game-card, .team-card, .palmares-podium-card').forEach(function (card) {
            card.classList.add('tilt');
            var shine = document.createElement('i');
            shine.className = 'shine';
            card.appendChild(shine);
            card.addEventListener('mousemove', function (e) {
                var r = card.getBoundingClientRect();
                var x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
                card.style.transform = 'rotateY(' + ((x - .5) * 12) + 'deg) rotateX(' + ((.5 - y) * 12) + 'deg) translateY(-4px)';
                card.style.setProperty('--mx', (x * 100) + '%');
                card.style.setProperty('--my', (y * 100) + '%');
            });
            card.addEventListener('mouseleave', function () { card.style.transform = ''; });
        });
    })();

    // --- Héros : parallaxe du titre et neige ---
    (function () {
        var hero = document.getElementById('hero');
        if (!hero || reduce) return;

        var title = hero.querySelector('.hero-title .grad');
        if (title && canHover) {
            hero.addEventListener('mousemove', function (e) {
                var r = hero.getBoundingClientRect();
                var x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
                title.style.transform = 'rotateY(' + (x * 10) + 'deg) rotateX(' + (-y * 8) + 'deg) translate(' + (x * 10) + 'px,' + (y * 10) + 'px)';
            });
            hero.addEventListener('mouseleave', function () { title.style.transform = ''; });
        }

        var c = document.getElementById('snow');
        if (!c || !c.getContext) return;
        var ctx = c.getContext('2d');
        var flakes = [], W, H, mx = 0, dpr = Math.min(2, window.devicePixelRatio || 1);
        function make(anywhere) {
            var z = Math.random();
            return { x: Math.random() * W, y: anywhere ? Math.random() * H : -10, z: z, r: .6 + z * 2.2, vy: .25 + z * .9, vx: (Math.random() - .5) * .3, ph: Math.random() * Math.PI * 2 };
        }
        function resize() {
            W = hero.clientWidth; H = hero.clientHeight;
            c.width = W * dpr; c.height = H * dpr; ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            var n = Math.round(W * H / 9000);
            flakes = [];
            for (var i = 0; i < n; i++) flakes.push(make(true));
        }
        function accentRGB() {
            var a = getComputedStyle(document.documentElement).getPropertyValue('--accent').trim();
            var m = a.match(/^#([0-9a-f]{6})$/i);
            if (!m) return '255,255,255';
            var n = parseInt(m[1], 16);
            return (n >> 16) + ',' + ((n >> 8) & 255) + ',' + (n & 255);
        }
        var t = 0, rgb = accentRGB();
        function frame() {
            t += .01;
            ctx.clearRect(0, 0, W, H);
            for (var i = 0; i < flakes.length; i++) {
                var f = flakes[i];
                f.y += f.vy; f.x += f.vx + Math.sin(t + f.ph) * .3 + mx * f.z * .6;
                if (f.y > H + 10 || f.x < -10 || f.x > W + 10) { flakes[i] = make(false); continue; }
                var warm = f.z > .82;
                ctx.beginPath(); ctx.arc(f.x, f.y, f.r, 0, Math.PI * 2);
                ctx.fillStyle = warm ? 'rgba(' + rgb + ',' + (.35 + f.z * .4) + ')' : 'rgba(245,242,236,' + (.12 + f.z * .45) + ')';
                if (warm) { ctx.shadowBlur = 10; ctx.shadowColor = 'rgba(' + rgb + ',.8)'; } else ctx.shadowBlur = 0;
                ctx.fill();
            }
            ctx.shadowBlur = 0;
            if (document.visibilityState === 'visible') requestAnimationFrame(frame); else setTimeout(frame, 300);
        }
        hero.addEventListener('mousemove', function (e) { var r = hero.getBoundingClientRect(); mx = (e.clientX - r.left) / r.width - .5; });
        hero.addEventListener('mouseleave', function () { mx = 0; });
        window.addEventListener('resize', resize);
        resize(); frame();
    })();
})();
