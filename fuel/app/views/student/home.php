<main class="py-5">
    <div class="container">
        <h1 class="mb-3">生徒専用ページ</h1>

        <?php if (isset($username) && $username !== ''): ?>
            <p class="text-muted"><?php echo e($username); ?>さん、こんにちは！</p>
        <?php endif; ?>
    </div>
</main>

