    <footer class="app-footer">
        <div class="container-fluid">
            <span><i class="bi bi-building"></i> Proyecto DL &mdash; <?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?></span>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (!empty($inlineJs)): ?><script><?= $inlineJs ?></script><?php endif; ?>
    <?php if (!empty($extraJs)): foreach ($extraJs as $js): ?>
        <script src="<?= e($js) ?>"></script>
    <?php endforeach; endif; ?>
</body>
</html>
