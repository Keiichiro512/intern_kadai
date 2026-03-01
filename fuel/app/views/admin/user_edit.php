<!-- ヘッダー -->
<header class="admin-header">
    <span class="admin-header__greeting">塾長　お疲れ様です！</span>
    <?php echo \View::forge('partials/header_links')->render(); ?>
</header>

<!-- メインコンテンツ -->
<main class="admin-main admin-main--form">
    <div class="container">
        <div class="user-form-card shadow-sm">
            <h1 class="user-form-title">ユーザー編集</h1>

            <?php $success = Session::get_flash('success'); ?>
            <?php $error_flash = Session::get_flash('error'); ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo e($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_flash)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo e($error_flash); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $errors; ?>
                </div>
            <?php endif; ?>

            <div class="mb-3 text-muted small">
                ID: <?php echo (int) $user->id; ?> /
                役割ID: <?php echo (int) $user->role_id; ?> /
                Username: <?php echo e($user->username); ?>
            </div>

            <form method="post" class="user-form" action="<?php echo Uri::create('admin/edit_user/' . (int) $user->id); ?>">
                <!-- 氏名 -->
                <div class="row g-3 align-items-center mb-4">
                    <div class="col-auto">
                        <label for="last_name" class="col-form-label user-form-label">氏</label>
                    </div>
                    <div class="col">
                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            class="form-control"
                            value="<?php echo e($input['last_name']); ?>"
                        >
                    </div>

                    <div class="col-auto">
                        <label for="first_name" class="col-form-label user-form-label">名</label>
                    </div>
                    <div class="col">
                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            class="form-control"
                            value="<?php echo e($input['first_name']); ?>"
                        >
                    </div>
                </div>

                <?php if (!empty($is_student)): ?>
                    <!-- 学年 -->
                    <div class="row g-3 align-items-center mb-4">
                        <div class="col-auto">
                            <label for="grade_id" class="col-form-label user-form-label">学年：</label>
                        </div>
                        <div class="col-6 col-sm-4">
                            <select id="grade_id" name="grade_id" class="form-select">
                                <option value="">選択してください</option>
                                <?php if (!empty($grades)): ?>
                                    <?php foreach ($grades as $grade): ?>
                                        <option
                                            value="<?php echo (int) $grade->id; ?>"
                                            <?php echo ((string) $input['grade_id'] === (string) $grade->id) ? 'selected' : ''; ?>
                                        >
                                            <?php echo e($grade->grade_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- 受講科目 -->
                    <div class="mb-4">
                        <label class="form-label user-form-label d-block">受講科目：</label>
                        <div class="user-form-subjects">
                            <?php if (!empty($subjects)): ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <div class="form-check form-check-inline mb-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="subject_ids[]"
                                            id="subject_<?php echo (int) $subject->id; ?>"
                                            value="<?php echo (int) $subject->id; ?>"
                                            <?php echo in_array((int) $subject->id, (array) $input['subject_ids'], true) ? 'checked' : ''; ?>
                                        >
                                        <label class="form-check-label" for="subject_<?php echo (int) $subject->id; ?>">
                                            <?php echo e($subject->subject_name); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted small">科目マスタが未登録です。</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- パスワード（空欄なら維持） -->
                <div class="row g-3 align-items-center mb-4">
                    <div class="col-auto">
                        <label for="password" class="col-form-label user-form-label">パスワード</label>
                    </div>
                    <div class="col">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            autocomplete="new-password"
                            placeholder="変更する場合のみ入力"
                        >
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="<?php echo Uri::create('admin/users/edit'); ?>" class="btn btn-outline-secondary">
                        一覧に戻る
                    </a>
                    <button type="submit" class="btn btn-primary user-form-submit">更新する</button>
                </div>
            </form>

            <hr class="my-4">

            <p class="small text-muted mb-2">【テスト用】パスワードを強制的に &quot;testpass123&quot; に変更して検証する場合:</p>
            <a href="<?php echo Uri::create('admin/force_test_password/' . (int) $user->id); ?>" class="btn btn-outline-warning btn-sm mb-4">パスワードを強制的に &quot;testpass123&quot; に変更する</a>

            <form method="post" action="<?php echo Uri::create('admin/delete_user/' . (int) $user->id); ?>" onsubmit="return confirmDelete();">
                <button type="submit" class="btn btn-danger">このユーザーを削除する</button>
            </form>
        </div>
    </div>
</main>

<script>
    function confirmDelete() {
        return window.confirm('本当に削除しますか？');
    }
</script>
