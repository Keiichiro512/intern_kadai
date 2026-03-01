<main class="py-5">
    <div class="container">
        <h1 class="mb-3">講師専用ページ</h1>

        <?php if (isset($username) && $username !== ''): ?>
            <p class="text-muted"><?php echo e($username); ?>さん、こんにちは！</p>
        <?php endif; ?>

        <?php
        $my_students = isset($my_students) ? $my_students : array();
        $schedules   = isset($schedules) ? $schedules : array();
        ?>

        <?php if ( ! empty($my_students)): ?>
            <section class="mb-4">
                <h2 class="h5 mb-2">担当生徒</h2>
                <ul class="list-group">
                    <?php foreach ($my_students as $u): ?>
                        <li class="list-group-item"><?php echo e($u->last_name . ' ' . $u->first_name); ?>（<?php echo e($u->username); ?>）</li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ( ! empty($schedules)): ?>
            <section>
                <h2 class="h5 mb-2">担当スケジュール</h2>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>日付</th>
                                <th>時間帯</th>
                                <th>科目</th>
                                <th>生徒</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $s): ?>
                                <tr>
                                    <td><?php echo e($s->lesson_date); ?></td>
                                    <td><?php echo (isset($s->time_slot_id) ? e((string) $s->time_slot_id) : '-'); ?></td>
                                    <td><?php echo isset($s->subject) && $s->subject ? e($s->subject->subject_name) : '-'; ?></td>
                                    <td><?php echo isset($s->student) && $s->student ? e($s->student->last_name . ' ' . $s->student->first_name) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php else: ?>
            <p class="text-muted">担当スケジュールはありません。</p>
        <?php endif; ?>
    </div>
</main>
