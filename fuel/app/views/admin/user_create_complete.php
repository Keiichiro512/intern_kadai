<!-- ヘッダー -->
<header class="admin-header">
    <span class="admin-header__greeting">塾長　お疲れ様です！</span>
    <a href="<?php echo Uri::create('auth/logout'); ?>" class="admin-header__logout">ログアウト</a>
</header>

<!-- メインコンテンツ -->
<main class="admin-main">
    <div class="container">
        <div class="user-form-card shadow-sm text-center py-5">
            <h1 class="user-form-title mb-3">登録完了</h1>
            <p class="mb-4">登録が正常に完了しました。</p>

            <div class="d-flex justify-content-center gap-3">
                <a href="<?php echo Uri::create('admin/home'); ?>" class="btn btn-outline-secondary">
                    ホームに戻る
                </a>
                <a href="<?php echo Uri::create('admin/user_create'); ?>" class="btn btn-primary">
                    続けて登録する
                </a>
            </div>
        </div>
    </div>
</main>

