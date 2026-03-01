<main class="py-5">
    <div class="container">
        <h1 class="mb-3">保護者専用ページ</h1>

        <?php if (isset($username) && $username !== ''): ?>
            <p class="text-muted"><?php echo e($username); ?>さん、こんにちは！</p>
        <?php endif; ?>

        <?php
        $children  = isset($children) ? $children : array();
        $schedules = isset($schedules) ? $schedules : array();
        ?>

        <?php if ( ! empty($children)): ?>
            <section class="mb-4">
                <h2 class="h5 mb-2">お子様</h2>
                <ul class="list-group">
                    <?php foreach ($children as $rel): ?>
                        <?php if (isset($rel->student) && $rel->student): ?>
                            <li class="list-group-item"><?php echo e($rel->student->last_name . ' ' . $rel->student->first_name); ?>（<?php echo e($rel->student->username); ?>）</li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ( ! empty($schedules)): ?>
            <section>
                <h2 class="h5 mb-2">お子様の授業・報告</h2>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>日付</th>
                                <th>時間帯</th>
                                <th>科目</th>
                                <th>生徒</th>
                                <th>報告</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $s): ?>
                                <tr>
                                    <td><?php echo e($s->lesson_date); ?></td>
                                    <td><?php echo (isset($s->time_slot_id) ? e((string) $s->time_slot_id) : '-'); ?></td>
                                    <td><?php echo isset($s->subject) && $s->subject ? e($s->subject->subject_name) : '-'; ?></td>
                                    <td><?php echo isset($s->student) && $s->student ? e($s->student->last_name . ' ' . $s->student->first_name) : '-'; ?></td>
                                    <td><?php echo isset($s->report) && $s->report ? e(mb_strimwidth($s->report->lesson_report, 0, 40, '…')) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php else: ?>
            <p class="text-muted">お子様の授業・報告はありません。</p>
        <?php endif; ?>
    </div>
</main>
