#!/usr/bin/env node
/**
 * Génère poke/data/cards.json : le catalogue des cartes 30th Celebration
 * et 30th Celebration : Classic Collection.
 *
 * Sources (gratuites, sans clé) :
 *  - TCGdex (https://tcgdex.dev) : noms + images en français
 *  - pokemontcg.io (données GitHub PokemonTCG/pokemon-tcg-data) : raretés,
 *    et images de la Classic Collection (absentes de TCGdex à ce jour)
 *
 * Usage : node poke/tools/build_cards.js
 * À relancer si une source se met à jour (ex. images FR de la Classic Collection).
 */
const fs = require('fs');
const path = require('path');

const TCGDEX = 'https://api.tcgdex.net/v2';
const PTCG = 'https://raw.githubusercontent.com/PokemonTCG/pokemon-tcg-data/master/cards/en';

const SETS = [
    { id: 'main', name: '30th Celebration', tcgdex: '30th', ptcg: 'me55' },
    { id: 'classic', name: 'Classic Collection', tcgdex: '30th-c', ptcg: 'me55c' },
];

const RARITY_FR = {
    'Common': 'Commune',
    'Uncommon': 'Peu commune',
    'Rare': 'Rare',
    'Double Rare': 'Double rare',
    'Pikachu Rare': 'Rare Pikachu',
    'Illustration Rare': 'Illustration rare',
    'Special Illustration Rare': 'Illustration spéciale rare',
    'Futuristic Rare': 'Rare futuriste',
    'Rare Holo': 'Rare holo',
    'Rare Secret': 'Rare secrète',
    'Rare Ultra': 'Ultra rare',
};

async function getJson(url) {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`${res.status} sur ${url}`);
    return res.json();
}

// "Genesect-EX" / "Genesect EX" / "Pikachu & Zekrom-GX" -> clé comparable
const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]/g, '');

async function buildSet(set) {
    const [fr, en, ptcg] = await Promise.all([
        getJson(`${TCGDEX}/fr/sets/${set.tcgdex}`),
        getJson(`${TCGDEX}/en/sets/${set.tcgdex}`),
        getJson(`${PTCG}/${set.ptcg}.json`),
    ]);
    const frById = Object.fromEntries(fr.cards.map(c => [c.id, c]));

    if (set.id === 'main') {
        // Même numérotation des deux côtés : "001" (TCGdex) <-> "1" (pokemontcg.io)
        const ptcgByNum = Object.fromEntries(ptcg.map(c => [c.number.replace(/^0+/, ''), c]));
        return en.cards.map((c, i) => {
            const p = ptcgByNum[c.localId.replace(/^0+/, '')] || {};
            const f = frById[c.id] || {};
            return {
                id: c.id,
                set: set.id,
                num: c.localId,
                order: i,
                name: f.name || c.name,
                name_en: c.name,
                rarity: RARITY_FR[p.rarity] || p.rarity || '',
                img: f.image ? `${f.image}/low.webp` : (p.images?.small || ''),
                img_hd: f.image ? `${f.image}/high.webp` : (p.images?.large || ''),
            };
        });
    }

    // Classic Collection : les cartes gardent leur numéro d'origine (Dracaufeu 4/102),
    // on rapproche TCGdex et pokemontcg.io par nom anglais (+ numéro pour les doublons).
    const remaining = [...ptcg];
    return en.cards.map((c, i) => {
        const f = frById[c.id] || {};
        let candidates = remaining.filter(p => norm(p.name) === norm(c.name));
        // ex. "Palkia" (TCGdex) <-> "Palkia LV.X" (pokemontcg.io)
        if (!candidates.length) candidates = remaining.filter(p => norm(p.name).startsWith(norm(c.name)));
        const p = candidates[0] || {};
        if (candidates.length) remaining.splice(remaining.indexOf(p), 1);
        else console.warn(`  ! pas d'équivalent pokemontcg.io pour ${c.id} ${c.name}`);
        const img = f.image ? `${f.image}/low.webp` : (p.images?.small || '');
        return {
            id: c.id,
            set: set.id,
            num: c.localId,
            order: i,
            name: f.name || c.name,
            name_en: c.name,
            original_num: p.number || '',
            rarity: RARITY_FR[p.rarity] || p.rarity || '',
            img,
            img_hd: f.image ? `${f.image}/high.webp` : (p.images?.large || img),
        };
    });
}

(async () => {
    const out = { generated: new Date().toISOString().slice(0, 10), sets: [], cards: [] };
    for (const set of SETS) {
        console.log(`Set ${set.name}…`);
        const cards = await buildSet(set);
        out.sets.push({ id: set.id, name: set.name, count: cards.length });
        out.cards.push(...cards);
        console.log(`  ${cards.length} cartes, ${cards.filter(c => c.img).length} avec image`);
    }
    const file = path.join(__dirname, '..', 'data', 'cards.json');
    fs.mkdirSync(path.dirname(file), { recursive: true });
    fs.writeFileSync(file, JSON.stringify(out, null, 1));
    console.log(`Écrit : ${file}`);
})().catch(e => { console.error(e); process.exit(1); });
