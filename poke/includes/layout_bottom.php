</main>

<?php if (!empty($pokeUser)): ?>
<nav class="tabbar" aria-label="Navigation">
    <a href="index.php" class="<?= ($activeTab ?? '') === 'collection' ? 'on' : '' ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="9" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
        Collection
    </a>
    <a href="market.php" class="<?= ($activeTab ?? '') === 'market' ? 'on' : '' ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><circle cx="17" cy="9" r="2.5"/><path d="M2.5 20c.8-3.6 3.4-5.5 6.5-5.5s5.7 1.9 6.5 5.5M15 14.6c2.8-.3 5.2 1.2 6 4.4"/></svg>
        Bourse
    </a>
    <a href="trades.php" class="<?= ($activeTab ?? '') === 'trades' ? 'on' : '' ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h14l-4-4M20 16H6l4 4"/></svg>
        Échanges
        <?php if (!empty($pokePending)): ?><i class="dot"><?= (int)$pokePending ?></i><?php endif; ?>
    </a>
</nav>
<?php endif; ?>

<script src="js/poke.js?v=1"></script>
</body>
</html>
