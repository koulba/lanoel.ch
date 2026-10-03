# poke.lanoel.ch — échange de cartes Pokémon

Mini-site mobile pour gérer sa collection **30th Celebration** + **Classic Collection**
et proposer des échanges de doubles entre amis. Utilise les comptes et la base de lanoel.ch.

## Mise en ligne

1. Créer le sous-domaine `poke.lanoel.ch` chez l'hébergeur et faire pointer sa racine
   sur le dossier `poke/` de ce dépôt (il inclut `../config/database.php` et le `.env` du site).
2. Activer le certificat SSL pour le sous-domaine.
3. C'est tout : les tables `poke_cards`, `poke_trades` et `poke_trade_items` sont créées
   automatiquement à la première visite.

La session est partagée sur `.lanoel.ch` : connecté sur lanoel.ch = connecté sur poke.lanoel.ch.

## Catalogue des cartes

`data/cards.json` est généré par `node tools/build_cards.js` (Node 18+) à partir de :
- [TCGdex](https://tcgdex.dev) : noms et images en français ;
- [pokemontcg.io](https://github.com/PokemonTCG/pokemon-tcg-data) : raretés et images
  de la Classic Collection (pas encore dans TCGdex).

Relancer le script quand les sources se mettent à jour (ex. images FR de la Classic Collection).

## Fonctionnement d'un échange

Proposition (en attente) → acceptée → « Cartes échangées ✓ » : les collections des deux
joueurs sont alors mises à jour automatiquement. Refus/annulation possibles avant.
