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
$grades      = isset($grades) ? $grades : array();
$students_by_grade_json = isset($students_by_grade_json) ? $students_by_grade_json : array();
$student_enrollments = isset($student_enrollments) ? $student_enrollments : array();
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
                                        data-units="<?php echo e(json_encode(isset($lesson['units']) ? $lesson['units'] : array())); ?>">
                                        <span class="schedule-slot__teacher"><?php echo e($lesson['teacher']); ?></span>
                                        <span class="schedule-slot__student"><?php echo e($lesson['student']); ?></span>
                                        <span class="schedule-slot__subject"><?php echo e($lesson['subject']); ?></span>
                                    </button>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <button type="button" class="schedule-slot__cell schedule-slot__cell--empty schedule-slot__cell--clickable"
                                    data-date="<?php echo e($schedule_lesson_date); ?>"
                                    data-time-slot-id="<?php echo e($slot->id); ?>"
                                    data-lesson-schedule-id=""
                                    data-teacher-user-id=""
                                    data-units="[]">（空き）</button>
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
                <span class="schedule-modal__label">生徒・科目（学年→生徒→科目を選択）</span>
                <div id="schedule-units-container" class="schedule-units"></div>
                <button type="button" id="schedule-unit-add" class="schedule-unit-add-btn">生徒を追加</button>
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

<script type="text/template" id="schedule-unit-tpl">
    <div class="schedule-unit" data-unit-index="">
        <div class="schedule-unit__row">
            <label class="schedule-unit__label">学年</label>
            <select class="schedule-unit__grade">
                <option value="">選択</option>
            </select>
            <label class="schedule-unit__label">生徒</label>
            <select class="schedule-unit__student">
                <option value="">選択</option>
            </select>
            <button type="button" class="schedule-unit__remove">削除</button>
        </div>
        <div class="schedule-unit__subjects">
            <span class="schedule-unit__label">科目</span>
        </div>
    </div>
</script>

<script>
(function() {
    var GRADES = <?php echo json_encode($students_by_grade_json); ?>;
    var SUBJECTS = <?php echo json_encode(array_map(function($s) { return array('id' => $s->id, 'name' => $s->subject_name); }, $subjects)); ?>;
    var ENROLLMENTS = <?php echo json_encode($student_enrollments); ?>;

    // 安全な初期化（JSエラー防止）
    if (!Array.isArray(GRADES)) GRADES = [];
    if (!Array.isArray(SUBJECTS)) SUBJECTS = [];
    if (!ENROLLMENTS || typeof ENROLLMENTS !== 'object') ENROLLMENTS = {};

    var modal = document.getElementById('schedule-modal');
    var overlay = modal && modal.querySelector('.schedule-modal__overlay');
    var formId = document.getElementById('form-lesson-schedule-id');
    var formDate = document.getElementById('form-lesson-date');
    var formTimeSlotId = document.getElementById('form-time-slot-id');
    var formTeacher = document.getElementById('form-teacher-user-id');
    var deleteWrap = document.getElementById('schedule-delete-wrap');
    var formDeleteId = document.getElementById('form-delete-lesson-schedule-id');
    var unitsContainer = document.getElementById('schedule-units-container');
    var addBtn = document.getElementById('schedule-unit-add');
    var unitTpl = document.getElementById('schedule-unit-tpl');
    var unitIndex = 0;

    if (!modal || !unitsContainer || !addBtn || !unitTpl) {
        return;
    }

    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }
    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function getStudentsByGradeId(gradeId) {
        var g = GRADES.filter(function(x) { return x.grade_id == gradeId; });
        return g.length ? g[0].students : [];
    }

    function getEnrollment(studentUserId) {
        return ENROLLMENTS[String(studentUserId)] || [];
    }

    function renderSubjectCheckboxes(unitEl, studentUserId) {
        var wrap = unitEl.querySelector('.schedule-unit__subjects');
        if (!wrap) return;
        var idx = unitEl.getAttribute('data-unit-index');
        var enrolled = getEnrollment(studentUserId);
        wrap.innerHTML = '<span class="schedule-unit__label">科目</span>';
        SUBJECTS.forEach(function(subj) {
            var enabled = enrolled.indexOf(subj.id) !== -1;
            var label = document.createElement('label');
            label.className = 'schedule-unit__subject-item' + (enabled ? '' : ' subject-disabled');
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.name = 'student_units[' + idx + '][subject_ids][]';
            cb.value = subj.id;
            cb.disabled = !enabled;
            if (!enabled) cb.setAttribute('aria-disabled', 'true');
            label.appendChild(cb);
            label.appendChild(document.createTextNode(' ' + subj.name));
            wrap.appendChild(label);
        });
    }

    function updateStudentSelect(unitEl) {
        var gradeSelect = unitEl.querySelector('.schedule-unit__grade');
        var studentSelect = unitEl.querySelector('.schedule-unit__student');
        var gradeId = gradeSelect ? gradeSelect.value : '';
        var students = getStudentsByGradeId(gradeId);
        if (!studentSelect) return;
        var cur = studentSelect.value;
        studentSelect.innerHTML = '<option value="">選択</option>';
        students.forEach(function(s) {
            var opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            if (String(s.id) === String(cur)) opt.selected = true;
            studentSelect.appendChild(opt);
        });
        if (!cur) renderSubjectCheckboxes(unitEl, '');
        else renderSubjectCheckboxes(unitEl, cur);
    }

    function addUnit(data) {
        data = data || { grade_id: '', student_user_id: '', subject_ids: [] };
        var idx = unitIndex++;
        var html = unitTpl.innerHTML.replace('data-unit-index=""', 'data-unit-index="' + idx + '"');
        var wrap = document.createElement('div');
        wrap.innerHTML = html;
        var unitEl = wrap.firstElementChild;

        var gradeSelect = unitEl.querySelector('.schedule-unit__grade');
        gradeSelect.name = 'student_units[' + idx + '][grade_id]';
        var studentSelect = unitEl.querySelector('.schedule-unit__student');
        studentSelect.name = 'student_units[' + idx + '][student_user_id]';
        GRADES.forEach(function(g) {
            var opt = document.createElement('option');
            opt.value = g.grade_id;
            opt.textContent = g.grade_name;
            if (String(g.grade_id) === String(data.grade_id)) opt.selected = true;
            gradeSelect.appendChild(opt);
        });

        gradeSelect.addEventListener('change', function() {
            unitEl.querySelector('.schedule-unit__student').value = '';
            updateStudentSelect(unitEl);
        });

        unitsContainer.appendChild(unitEl);
        updateStudentSelect(unitEl);

        studentSelect.addEventListener('change', function() {
            renderSubjectCheckboxes(unitEl, this.value);
        });

        if (data.student_user_id) {
            studentSelect.value = data.student_user_id;
            updateStudentSelect(unitEl);
            renderSubjectCheckboxes(unitEl, data.student_user_id);
            var subsWrap = unitEl.querySelector('.schedule-unit__subjects');
            if (subsWrap && data.subject_ids && data.subject_ids.length) {
                setTimeout(function() {
                    subsWrap.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
                        if (data.subject_ids.indexOf(parseInt(cb.value, 10)) !== -1) cb.checked = true;
                    });
                }, 0);
            }
        }

        unitEl.querySelector('.schedule-unit__remove').addEventListener('click', function() {
            unitEl.remove();
        });
    }

    addBtn.addEventListener('click', function() {
        addUnit({});
    });

    // イベントデリゲートで全てのセルを対象にする
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.schedule-slot__cell--clickable');
        if (!btn) return;

        var date = btn.getAttribute('data-date');
        var timeSlotId = btn.getAttribute('data-time-slot-id');
        var lessonId = btn.getAttribute('data-lesson-schedule-id') || '';
        var teacherId = btn.getAttribute('data-teacher-user-id') || '';
        var unitsJson = btn.getAttribute('data-units') || '[]';
        var units = [];
        try {
            units = JSON.parse(unitsJson);
            if (!Array.isArray(units)) units = [];
        } catch (e) {
            units = [];
        }

        if (formId) formId.value = lessonId;
        if (formDate) formDate.value = date || '';
        if (formTimeSlotId) formTimeSlotId.value = timeSlotId || '';
        if (formTeacher) formTeacher.value = teacherId || '';
        if (formDeleteId) formDeleteId.value = lessonId;

        unitsContainer.innerHTML = '';
        unitIndex = 0;
        if (units.length > 0) {
            units.forEach(function(u) { addUnit(u); });
        } else {
            addUnit({});
        }

        if (deleteWrap) deleteWrap.style.display = lessonId ? 'inline' : 'none';

        openModal();
    });

    if (overlay) overlay.addEventListener('click', closeModal);
    var cancelBtn = document.getElementById('schedule-modal-cancel');
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
})();
</script>
