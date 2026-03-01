<?php
$role_id = \Session::get('role_id');
$home_uri = '';
if ($role_id !== null && $role_id !== '') {
    switch ((int) $role_id) {
        case 1: $home_uri = 'admin/home'; break;
        case 2: $home_uri = 'teacher/home'; break;
        case 3: $home_uri = 'student/home'; break;
        case 4: $home_uri = 'parent/home'; break;
        default: $home_uri = ''; break;
    }
}
?>
<div class="admin-header__right">
    <?php if ($home_uri !== ''): ?>
        <a href="<?php echo \Uri::create($home_uri); ?>" class="admin-header__link--home">ホームに戻る</a>
    <?php endif; ?>
    <a href="<?php echo \Uri::create('auth/logout'); ?>" class="admin-header__logout">ログアウト</a>
</div>
