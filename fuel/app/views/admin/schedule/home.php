<?php
/**
 * スケジュール管理画面
 * 左: 講師・生徒一覧 / 中央: スケジュール表示 / 右: カレンダー
 * コントローラーから渡される $teachers, $students_by_grade, $time_slots, $lesson_slots, $calendar_weeks 等を表示。
 */
$teachers          = isset($teachers) ? $teachers : array();
$students_by_grade = isset($students_by_grade) ? $students_by_grade : array();
$display_date     = isset($display_date) ? $display_date : '';
$time_slots       = isset($time_slots) ? $time_slots : array();
$lesson_slots     = isset($lesson_slots) ? $lesson_slots : array();
$calendar_month   = isset($calendar_month) ? $calendar_month : '';
$calendar_year    = isset($calendar_year) ? $calendar_year : '';
$calendar_weeks   = isset($calendar_weeks) ? $calendar_weeks : array();
$selected_day     = isset($selected_day) ? (int) $selected_day : 0;
$calendar_month_num = isset($calendar_month_num) ? (int) $calendar_month_num : (int) date('n');
$calendar_year_num  = isset($calendar_year_num) ? (int) $calendar_year_num : (int) date('Y');
$today_day   = (int) date('j');
$today_month = (int) date('n');
$today_year  = (int) date('Y');
$prev_url    = isset($prev_url) ? $prev_url : Uri::create('admin/schedule');
$next_url    = isset($next_url) ? $next_url : Uri::create('admin/schedule');
$schedule_lesson_date = isset($schedule_lesson_date) ? $schedule_lesson_date : '';
$subjects    = isset($subjects) ? $subjects : array();
$students_flat = isset($students_flat) ? $students_flat : array();
$monthly_lessons = isset($monthly_lessons) ? $monthly_lessons : array();
$save_url    = Uri::create('admin/schedule/save');
$delete_url  = Uri::create('admin/schedule/delete');
?>
<!-- メインコンテンツ（ヘッダー・ログアウトは template.php で共通表示） -->
<main class="admin-main admin-main--schedule">
    <div class="schedule-layout">

        <!-- 1. 左サイドバー：講師・生徒一覧 -->
        <aside class="schedule-sidebar schedule-sidebar--left">
            <section class="schedule-sidebar__block">
                <h2 class="schedule-sidebar__title">講師リスト</h2>
                <ul class="schedule-sidebar__list">
                    <?php foreach ($teachers as $t): ?>
                        <li class="schedule-sidebar__item"><?php echo e($t->last_name . ' ' . $t->first_name); ?></li>
                    <?php endforeach; ?>
                    <?php if (empty($teachers)): ?>
                        <li class="schedule-sidebar__item schedule-sidebar__item--empty">登録なし</li>
                    <?php endif; ?>
                </ul>
            </section>
            <section class="schedule-sidebar__block">
                <h2 class="schedule-sidebar__title">生徒リスト</h2>
                <?php foreach ($students_by_grade as $grade_block): ?>
                    <div class="schedule-sidebar__grade">
                        <div class="schedule-sidebar__grade-heading"><?php echo e($grade_block['grade']->grade_name); ?></div>
                        <ul class="schedule-sidebar__list">
                            <?php foreach ($grade_block['students'] as $s): ?>
                                <li class="schedule-sidebar__item"><?php echo e($s->user ? ($s->user->last_name . ' ' . $s->user->first_name) : '-'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($students_by_grade)): ?>
                    <p class="schedule-sidebar__item--empty">登録なし</p>
                <?php endif; ?>
            </section>
        </aside>

        <!-- 2. 中央メイン：スケジュール表示 -->
        <div class="schedule-main">
            <header class="schedule-main__header">
                <h1 class="schedule-main__date"><?php echo e($display_date); ?></h1>
            </header>
            <div class="schedule-main__slots">
                <?php foreach ($time_slots as $slot): ?>
                    <?php $lessons = isset($lesson_slots[$slot->id]) ? $lesson_slots[$slot->id] : array(); ?>
                    <div class="schedule-slot">
                        <div class="schedule-slot__label"><?php echo e($slot->slot_name); ?></div>
                        <div class="schedule-slot__cells">
                            <?php if ( ! empty($lessons)): ?>
                                <?php foreach ($lessons as $lesson): ?>
                                    <button type="button" class="schedule-slot__cell schedule-slot__cell--clickable"
                                        data-date="<?php echo e($schedule_lesson_date); ?>"
                                        data-time-slot-id="<?php echo e($slot->id); ?>"
                                        data-lesson-schedule-id="<?php echo e(isset($lesson['id']) ? $lesson['id'] : ''); ?>"
                                        data-teacher-user-id="<?php echo e(isset($lesson['teacher_user_id']) ? $lesson['teacher_user_id'] : ''); ?>"
                                        data-student-user-id="<?php echo e(isset($lesson['student_user_id']) ? $lesson['student_user_id'] : ''); ?>"
                                        data-subject-id="<?php echo e(isset($lesson['subject_id']) ? $lesson['subject_id'] : ''); ?>">
                                        <span class="schedule-slot__teacher"><?php echo e($lesson['teacher']); ?></span>
                                        <span class="schedule-slot__student"><?php echo e($lesson['student']); ?></span>
                                        <span class="schedule-slot__subject"><?php echo e($lesson['subject']); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <button type="button" class="schedule-slot__cell schedule-slot__cell--empty schedule-slot__cell--clickable"
                                    data-date="<?php echo e($schedule_lesson_date); ?>"
                                    data-time-slot-id="<?php echo e($slot->id); ?>"
                                    data-lesson-schedule-id="">（空き）</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($time_slots)): ?>
                    <p class="schedule-slot__cell--empty">時間枠が登録されていません。</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. 右サイドバー：カレンダー -->
        <aside class="schedule-sidebar schedule-sidebar--right">
            <section class="schedule-calendar">
                <div class="schedule-calendar__nav">
                    <a href="<?php echo e($prev_url); ?>" class="schedule-calendar__btn" aria-label="前の月">&lt;</a>
                    <span class="schedule-calendar__month"><?php echo e($calendar_month); ?></span>
                    <a href="<?php echo e($next_url); ?>" class="schedule-calendar__btn" aria-label="次の月">&gt;</a>
                </div>
                <div class="schedule-calendar__title"><?php echo e($calendar_year); ?>年</div>
                <table class="schedule-calendar__table" role="presentation">
                    <thead>
                        <tr>
                            <th>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calendar_weeks as $week): ?>
                            <tr>
                                <?php foreach ($week as $day): ?>
                                    <?php
                                    $day_int = $day !== '' ? (int) $day : 0;
                                    $is_selected = ($day_int > 0 && $day_int === $selected_day);
                                    $is_today = ($day_int > 0 && $day_int === $today_day && $calendar_year_num === $today_year && $calendar_month_num === $today_month);
                                    $cell_class = 'schedule-calendar__day';
                                    if ($is_selected) $cell_class .= ' is-active';
                                    if ($is_today)   $cell_class .= ' is-today';
                                    $day_link = '';
                                    $date_ymd = '';
                                    if ($day_int > 0) {
                                        $date_ymd = sprintf('%04d-%02d-%02d', $calendar_year_num, $calendar_month_num, $day_int);
                                        $day_url = Uri::create('admin/schedule', array(), array('date' => $date_ymd, 'year' => $calendar_year_num, 'month' => $calendar_month_num));
                                        $day_link = '<a href="' . e($day_url) . '" class="schedule-calendar__day-link">' . e($day) . '</a>';
                                    }
                                    $day_lessons = ($date_ymd !== '' && isset($monthly_lessons[$date_ymd])) ? $monthly_lessons[$date_ymd] : array();
                                    ?>
                                    <td class="<?php echo e($cell_class); ?>">
                                        <?php if ($day_int > 0): ?>
                                            <div class="schedule-calendar__day-inner">
                                                <a href="<?php echo e($day_url); ?>" class="stretched-link" aria-label="日付を選択"></a>
                                                <div class="cell-row cell-date"><?php echo $day_link; ?></div>
                                                <?php
                                                $slots = array('A' => 1, 'B' => 2, 'C' => 3);
                                                foreach ($slots as $label => $id):
                                                    $has_lesson = isset($monthly_lessons[$date_ymd][$id]);
                                                    $slot_class = 'cell-row cell-slot slot-' . strtolower($label);
                                                    if ( ! $has_lesson) $slot_class .= ' cell-slot--empty';
                                                ?>
                                                <div class="<?php echo e($slot_class); ?>"><?php echo $has_lesson ? e($label) : ''; ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </aside>

    </div>
</main>

<!-- 授業 新規登録・編集 モーダル -->
<div id="schedule-modal" class="schedule-modal" role="dialog" aria-labelledby="schedule-modal-title" aria-hidden="true">
    <div class="schedule-modal__overlay"></div>
    <div class="schedule-modal__box">
        <h2 id="schedule-modal-title" class="schedule-modal__title">授業の登録・編集</h2>

        <?php echo Form::open(array('action' => $save_url, 'method' => 'post', 'id' => 'schedule-form-save', 'class' => 'schedule-modal__form')); ?>
            <?php echo Form::csrf(); ?>
            <input type="hidden" name="lesson_schedule_id" id="form-lesson-schedule-id" value="">
            <input type="hidden" name="lesson_date" id="form-lesson-date" value="">
            <input type="hidden" name="time_slot_id" id="form-time-slot-id" value="">
            <input type="hidden" name="redirect_year" value="<?php echo e($calendar_year_num); ?>">
            <input type="hidden" name="redirect_month" value="<?php echo e($calendar_month_num); ?>">
            <input type="hidden" name="redirect_date" value="<?php echo e($schedule_lesson_date); ?>">

            <div class="schedule-modal__field">
                <label for="form-teacher-user-id" class="schedule-modal__label">講師</label>
                <select name="teacher_user_id" id="form-teacher-user-id" class="schedule-modal__select" required>
                    <option value="">選択してください</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?php echo (int) $t->id; ?>"><?php echo e($t->last_name . ' ' . $t->first_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="schedule-modal__field">
                <label for="form-student-user-id" class="schedule-modal__label">生徒</label>
                <select name="student_user_id" id="form-student-user-id" class="schedule-modal__select" required>
                    <option value="">選択してください</option>
                    <?php foreach ($students_flat as $st): ?>
                        <option value="<?php echo (int) $st['id']; ?>"><?php echo e($st['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="schedule-modal__field">
                <label for="form-subject-id" class="schedule-modal__label">科目</label>
                <select name="subject_id" id="form-subject-id" class="schedule-modal__select" required>
                    <option value="">選択してください</option>
                    <?php foreach ($subjects as $subj): ?>
                        <option value="<?php echo (int) $subj->id; ?>"><?php echo e($subj->subject_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

        <?php echo Form::close(); ?>

        <div class="schedule-modal__actions">
            <div class="schedule-modal__actions-left">
                <button type="submit" form="schedule-form-save" class="schedule-modal__btn schedule-modal__btn--primary">保存</button>
                <button type="button" class="schedule-modal__btn schedule-modal__btn--secondary" id="schedule-modal-cancel">キャンセル</button>
            </div>
            <div id="schedule-delete-wrap" class="schedule-modal__actions-right" style="display: none;">
                <?php echo Form::open(array('action' => $delete_url, 'method' => 'post', 'id' => 'schedule-form-delete')); ?>
                    <?php echo Form::csrf(); ?>
                    <input type="hidden" name="lesson_schedule_id" id="form-delete-lesson-schedule-id" value="">
                    <input type="hidden" name="redirect_year" value="<?php echo e($calendar_year_num); ?>">
                    <input type="hidden" name="redirect_month" value="<?php echo e($calendar_month_num); ?>">
                    <input type="hidden" name="redirect_date" value="<?php echo e($schedule_lesson_date); ?>">
                    <button type="submit" class="schedule-modal__btn schedule-modal__btn--danger" onclick="return confirm('この授業を削除してもよろしいですか？');">削除</button>
                <?php echo Form::close(); ?>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var modal = document.getElementById('schedule-modal');
    var overlay = modal && modal.querySelector('.schedule-modal__overlay');
    var formId = document.getElementById('form-lesson-schedule-id');
    var formDate = document.getElementById('form-lesson-date');
    var formTimeSlotId = document.getElementById('form-time-slot-id');
    var formTeacher = document.getElementById('form-teacher-user-id');
    var formStudent = document.getElementById('form-student-user-id');
    var formSubject = document.getElementById('form-subject-id');
    var deleteWrap = document.getElementById('schedule-delete-wrap');
    var formDeleteId = document.getElementById('form-delete-lesson-schedule-id');

    function openModal() {
        if (modal) modal.classList.add('is-open');
        if (modal) modal.setAttribute('aria-hidden', 'false');
    }
    function closeModal() {
        if (modal) modal.classList.remove('is-open');
        if (modal) modal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('.schedule-slot__cell--clickable').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var date = this.getAttribute('data-date');
            var timeSlotId = this.getAttribute('data-time-slot-id');
            var lessonId = this.getAttribute('data-lesson-schedule-id') || '';
            var teacherId = this.getAttribute('data-teacher-user-id') || '';
            var studentId = this.getAttribute('data-student-user-id') || '';
            var subjectId = this.getAttribute('data-subject-id') || '';

            if (formId) formId.value = lessonId;
            if (formDate) formDate.value = date;
            if (formTimeSlotId) formTimeSlotId.value = timeSlotId || '';
            if (formTeacher) formTeacher.value = teacherId;
            if (formStudent) formStudent.value = studentId;
            if (formSubject) formSubject.value = subjectId;
            if (formDeleteId) formDeleteId.value = lessonId;

            if (deleteWrap) deleteWrap.style.display = lessonId ? 'inline' : 'none';

            openModal();
        });
    });

    if (overlay) overlay.addEventListener('click', closeModal);
    var cancelBtn = document.getElementById('schedule-modal-cancel');
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
})();
</script>
