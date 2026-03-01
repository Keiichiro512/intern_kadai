<!-- メインコンテンツ（ヘッダー・ログアウトは template.php で共通表示） -->
<main class="admin-main">
    <div class="container">
        <div class="admin-menu">

            <a href="<?php echo Uri::create('admin/schedule'); ?>" class="admin-menu__btn">
                授業スケジュール作成
            </a>

            <a href="<?php echo Uri::create('admin/user_create'); ?>" class="admin-menu__btn">
                講師・生徒を追加する
            </a>

            <a href="<?php echo Uri::create('admin/users/edit'); ?>" class="admin-menu__btn">
                講師・生徒を編集する
            </a>

        </div>
    </div>
</main>
