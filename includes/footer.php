<?php if ($user ?? currentUser()): ?>
        </main>
    </div>
</div>
<?php else: ?>
</main>
<?php endif; ?>
<footer class="app-footer text-center py-3">
    <small class="text-muted"><?= __('copyright', ['year' => date('Y')]) ?></small>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= assetUrl('js/app.js') ?>"></script>
</body>
</html>
