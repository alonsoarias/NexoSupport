<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'ISER - Instituto Superior de Educación Rural' ?></title>

    <!-- ISER Theme CSS -->
    <link rel="stylesheet" href="/assets/css/iser-theme.css">

    <!-- Chart.js -->
    <?php if (isset($includeCharts) && $includeCharts): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <?php endif; ?>

    <!-- Additional CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <style>
        <?= $customCSS ?? '' ?>
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <?php include __DIR__ . '/../components/header.php'; ?>

        <!-- Stats (opcional) -->
        <?php if (isset($showStats) && $showStats && isset($stats)): ?>
            <?php include __DIR__ . '/../components/stats.php'; ?>
        <?php endif; ?>

        <!-- Content -->
        <div class="content">
            <?= $content ?? '' ?>
        </div>

        <!-- Footer -->
        <?php include __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- ISER Theme JS -->
    <script src="/assets/js/iser-theme.js"></script>

    <!-- Additional JS -->
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?= htmlspecialchars($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        <?= $customJS ?? '' ?>
    </script>
</body>
</html>
