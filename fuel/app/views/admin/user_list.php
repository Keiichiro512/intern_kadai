<!-- ヘッダー -->
<header class="admin-header">
    <span class="admin-header__greeting">塾長　お疲れ様です！</span>
    <?php echo \View::forge('partials/header_links')->render(); ?>
</header>

<!-- メインコンテンツ -->
<main class="admin-main admin-main--list">
    <div class="container">
        <h1 class="admin-list-title">生徒・講師を編集する</h1>

        <!-- 生徒：学年ごと -->
        <div class="admin-list-section">
            <?php foreach ($students_by_grade as $item): ?>
                <?php $grade = $item['grade']; $students = $item['students']; ?>
                <div class="admin-list-grade-block">
                    <div class="admin-list-grade-heading"><?php echo e($grade->grade_name); ?></div>
                    <div class="admin-list-name-wrap">
                        <?php foreach ($students as $student): ?>
                            <?php if ($student->user): ?>
                                <a href="<?php echo Uri::create('admin/edit_user/' . (int) $student->user->id); ?>" class="admin-list-name-btn">
                                    <?php echo e($student->user->last_name . ' ' . $student->user->first_name); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 講師 -->
        <div class="admin-list-section">
            <div class="admin-list-grade-block">
                <div class="admin-list-grade-heading">講師</div>
                <div class="admin-list-name-wrap">
                    <?php foreach ($teachers as $teacher): ?>
                        <a href="<?php echo Uri::create('admin/edit_user/' . (int) $teacher->id); ?>" class="admin-list-name-btn">
                            <?php echo e($teacher->last_name . ' ' . $teacher->first_name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>
