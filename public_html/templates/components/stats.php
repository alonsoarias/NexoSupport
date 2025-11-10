<!-- Stats Grid -->
<div class="stats-container">
    <div class="stats-grid">
        <?php if (isset($stats) && is_array($stats)): ?>
            <?php foreach ($stats as $stat): ?>
                <div class="stat-card">
                    <div class="label"><?= htmlspecialchars($stat['label']) ?></div>
                    <div class="value"><?= htmlspecialchars($stat['value']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
