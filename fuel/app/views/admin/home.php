<!-- ヘッダー -->
<header class="admin-header">
    <span class="admin-header__greeting">塾長　お疲れ様です！</span>
    <a href="<?php echo Uri::create('auth/logout'); ?>" class="admin-header__logout">ログアウト</a>
</header>

<!-- メインコンテンツ -->
<main class="admin-main">
    <div class="container">
        <div class="admin-menu">

            <a href="<?php echo Uri::create('schedule'); ?>" class="admin-menu__btn">
                授業スケジュール作成
            </a>

            <a href="<?php echo Uri::create('users/create'); ?>" class="admin-menu__btn">
                講師・生徒を追加する
            </a>

            <a href="<?php echo Uri::create('users/edit'); ?>" class="admin-menu__btn">
                講師・生徒を編集する
            </a>

        </div>
    </div>
</main>
