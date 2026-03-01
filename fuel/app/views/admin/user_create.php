<!-- ヘッダー -->
<header class="admin-header">
    <span class="admin-header__greeting">塾長　お疲れ様です！</span>
    <?php echo \View::forge('partials/header_links')->render(); ?>
</header>

<!-- メインコンテンツ -->
<main class="admin-main admin-main--form">
    <div class="container">
        <div class="user-form-card shadow-sm">
            <h1 class="user-form-title">生徒・講師を追加する</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $errors; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="user-form">
                <!-- 対象：生徒 / 講師 -->
                <div class="mb-4">
                    <label class="form-label user-form-label">対象：</label>
                    <div class="d-inline-flex align-items-center gap-4">
                        <div class="form-check form-check-inline">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="target_type"
                                id="target_student"
                                value="student"
                                <?php echo ($input['target_type'] === 'student') ? 'checked' : ''; ?>
                            >
                            <label class="form-check-label" for="target_student">生徒</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input
                                class="form-check-input"
                                type="radio"
                                name="target_type"
                                id="target_teacher"
                                value="teacher"
                                <?php echo ($input['target_type'] !== 'student') ? 'checked' : ''; ?>
                            >
                            <label class="form-check-label" for="target_teacher">講師</label>
                        </div>
                    </div>
                </div>

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

                <!-- パスワード -->
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
                        >
                    </div>
                </div>

                <!-- 生徒専用項目（学年・受講科目） -->
                <div id="student-extra-fields" class="<?php echo ($input['target_type'] === 'student') ? '' : 'd-none'; ?>">
                    <!-- 学年 -->
                    <div class="row g-3 align-items-center mb-4">
                        <div class="col-auto">
                            <label for="grade_id" class="col-form-label user-form-label">学年：</label>
                        </div>
                        <div class="col-6 col-sm-4">
                            <select id="grade_id" name="grade_id" class="form-select">
                                <option value="">選択してください</option>
                                <?php if ( ! empty($grades)): ?>
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
                            <?php if ( ! empty($subjects)): ?>
                                <?php foreach ($subjects as $subject): ?>
                                    <div class="form-check form-check-inline mb-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="subject_ids[]"
                                            id="subject_<?php echo (int) $subject->id; ?>"
                                            value="<?php echo (int) $subject->id; ?>"
                                            <?php echo in_array($subject->id, (array) $input['subject_ids'], true) ? 'checked' : ''; ?>
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
                </div>

                <!-- 保護者情報（生徒選択時のみ表示） -->
                <div id="parent-fields" class="<?php echo ($input['target_type'] === 'student') ? '' : 'd-none'; ?>">
                    <h2 class="h5 mb-3">保護者情報（任意）</h2>

                    <!-- 保護者氏名 -->
                    <div class="row g-3 align-items-center mb-3">
                        <div class="col-auto">
                            <label for="parent_last_name" class="col-form-label user-form-label">保護者 氏</label>
                        </div>
                        <div class="col">
                            <input
                                type="text"
                                id="parent_last_name"
                                name="parent_last_name"
                                class="form-control"
                                value="<?php echo e($input['parent_last_name']); ?>"
                            >
                        </div>

                        <div class="col-auto">
                            <label for="parent_first_name" class="col-form-label user-form-label">保護者 名</label>
                        </div>
                        <div class="col">
                            <input
                                type="text"
                                id="parent_first_name"
                                name="parent_first_name"
                                class="form-control"
                                value="<?php echo e($input['parent_first_name']); ?>"
                            >
                        </div>
                    </div>

                    <!-- 保護者パスワード -->
                    <div class="row g-3 align-items-center mb-4">
                        <div class="col-auto">
                            <label for="parent_password" class="col-form-label user-form-label">保護者パスワード</label>
                        </div>
                        <div class="col">
                            <input
                                type="password"
                                id="parent_password"
                                name="parent_password"
                                class="form-control"
                                autocomplete="new-password"
                            >
                        </div>
                    </div>
                </div>

                <!-- 登録ボタン -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary user-form-submit">登録</button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    // 対象（生徒 / 講師）選択による表示切り替え
    document.addEventListener('DOMContentLoaded', function () {
        const studentRadio = document.getElementById('target_student');
        const teacherRadio = document.getElementById('target_teacher');
        const studentExtra = document.getElementById('student-extra-fields');
        const parentFields = document.getElementById('parent-fields');

        function updateVisibility() {
            if (studentRadio.checked) {
                studentExtra.classList.remove('d-none');
                if (parentFields) {
                    parentFields.classList.remove('d-none');
                }
            } else {
                studentExtra.classList.add('d-none');
                if (parentFields) {
                    parentFields.classList.add('d-none');
                }
            }
        }

        if (studentRadio && teacherRadio && studentExtra) {
            studentRadio.addEventListener('change', updateVisibility);
            teacherRadio.addEventListener('change', updateVisibility);
            updateVisibility();
        }
    });
</script>

