        </main>
    </div>
</div>
<div class="sidebar-backdrop" data-sidebar-toggle></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
<?php if (!empty($pageScripts)): ?>
    <?php foreach ((array) $pageScripts as $script): ?>
        <script src="<?= e($script) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
