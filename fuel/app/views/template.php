<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? e($title) : 'HRCloud'; ?></title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Noto Sans JP (Google Fonts) -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Page CSS -->
    <?php if (isset($style_sheet)): ?>
        <?php echo Asset::css($style_sheet); ?>
    <?php endif; ?>
    <?php if (\Session::get('user_id') && (empty($style_sheet) || $style_sheet !== 'admin.css')): ?>
        <?php echo Asset::css('admin.css'); ?>
    <?php endif; ?>
</head>
<body>
    <?php if (\Session::get('user_id')): ?>
    <header class="admin-header">
        <span class="admin-header__greeting">
            <?php
            $role_id = (int) \Session::get('role_id');
            if ($role_id === 1) echo '塾長　お疲れ様です！';
            elseif ($role_id === 2) echo '講師　お疲れ様です！';
            elseif ($role_id === 3) echo '生徒　お疲れ様です！';
            elseif ($role_id === 4) echo '保護者　お疲れ様です！';
            else echo e(\Session::get('username', '')) . ' さん';
            ?>
        </span>
        <div class="admin-header__right">
            <?php
            $home_uri = '';
            if ($role_id === 1) $home_uri = 'admin/home';
            elseif ($role_id === 2) $home_uri = 'teacher/home';
            elseif ($role_id === 3) $home_uri = 'student/home';
            elseif ($role_id === 4) $home_uri = 'parent/home';
            ?>
            <?php if ($home_uri !== ''): ?>
                <a href="<?php echo \Uri::create($home_uri); ?>" class="admin-header__link--home">ホームに戻る</a>
            <?php endif; ?>
            <a href="<?php echo \Uri::create('auth/logout'); ?>" class="admin-header__logout">ログアウト</a>
        </div>
    </header>
    <?php endif; ?>
    <?php echo $content; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
