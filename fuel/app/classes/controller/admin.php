<?php

class Controller_Admin extends Controller_Base
{
    public $template = 'template';

    public function action_home(){
        // template.php で$title = '塾長ホーム' を設定する；タブのタイトルを設定する
        $this->template->title       = '塾長ホーム';
        // template.php で$style_sheet = 'admin.css' を設定する；cssを読み込む
        $this->template->style_sheet = 'admin.css';
        //View::forge('admin/home')：fuel/app/views/admin/home.php を読み込み、そのページの HTML（本文）を組み立てる。
        // template.php で$content = View::forge('admin/home') を設定する；ホーム画面を表示する
        $this->template->content = View::forge('admin/home');
    }

    public function action_schedule_home(){
        // --- 1. 画面の共通設定（template.php のタイトル・CSS） ---
        $this->template->title       = '授業スケジュール';
        $this->template->style_sheet = 'admin.css';

        // --- 2. 「どの日のスケジュールを見るか」: GET の year/month と date、なければ今日 or 月初などで $display_dt を決める ---

        // URL に year がなければ date('Y')（サーバー実行時の今年）を使う。
        $year  = (int) \Input::get('year', date('Y'));
        // URL に month がなければ date('n')（サーバー実行時の今月）を使う。
        $month = (int) \Input::get('month', date('n'));
        //$month を「1 以上 12 以下」にそろえる（はみ出した値を切り詰める）
        $month = max(1, min(12, $month));
        //ブラウザの URL の ?date=2025-03-21 のような GET パラメータ date を文字列で取ります。
        $date_str = \Input::get('date');
        
        // 主に「月の移動」や「最初の日付の表示」「授業保存後の日付表示」で使われる。
        if ($date_str) {
            // date_str があれば、date_str を Y-m-d 形式の日付に変換し、display_dt に格納する。（date あり・形式OK）
            $display_dt = \DateTime::createFromFormat('Y-m-d', $date_str);
            if ( ! $display_dt) {
                // 変換に失敗した場合、「その年・その月の1日」 にフォールバックします（先に決めた $year / $month を使用）（date あり・形式NG）→省くと良くない
                $display_dt = new \DateTime($year . '-' . $month . '-01');
            }
        } else {
            //月移動の場合、移動先が今月であれば、今日の日付を使う。（date なし・今月）
            $today = new \DateTime();
            if ($year === (int) $today->format('Y') && $month === (int) $today->format('n')) {
                $display_dt = clone $today;
            } else {
                // そうでなければ、「その年・その月の1日」 にフォールバックします（先に決めた $year / $month を使用）。（date なし・今月以外）
                $display_dt = new \DateTime($year . '-' . $month . '-01');
            }
        }

        // --- 3. 左サイドバー用：講師一覧（role_id=2）・学年別生徒（students×users）・ビュー向け $students_by_grade ---
        $teacher_result = \DB::select('id', 'last_name', 'first_name')
            ->from('users')
            ->where('role_id', 2)
            ->order_by('last_name', 'asc')
            ->order_by('first_name', 'asc')
            ->execute();
        $teachers = array();
        foreach ($teacher_result as $tr) {
            $teachers[] = (object) $tr;
        }
        // $teachersの中身の例
        // $teachers = [
        //     (object)[
        //       'id' => 12,
        //       'last_name' => '田中',
        //       'first_name' => '太郎',
        //     ],
        //     (object)[
        //       'id' => 25,
        //       'last_name' => '山田',
        //       'first_name' => '花子',
        //     ],
        //     (object)[
        //       'id' => 31,
        //       'last_name' => '鈴木',
        //       'first_name' => '次郎',
        //     ],
        //   ];

        $student_rows = \DB::select('students.user_id', 'students.grade_id','users.last_name','users.first_name')
            ->from('students')
            ->join('users', 'INNER')
            ->on('students.user_id', '=', 'users.id')
            ->order_by('students.grade_id', 'asc') 
            ->order_by('users.last_name', 'asc') 
            ->order_by('users.first_name', 'asc') 
            ->execute();
            
        $students_by_grade_id = array();
        foreach ($student_rows as $sr) {
            // その生徒が属する学年ID（例: 1年=1, 2年=2 など）を取り出します。
            $gid = (int) $sr['grade_id'];
            // その学年ID用の箱がまだ無ければ、新しく作ります。
            if ( ! isset($students_by_grade_id[$gid])) {
                // 「この学年の生徒リスト」を入れる空配列を用意します。
                $students_by_grade_id[$gid] = array();
            }
            $stu = new \stdClass();
            $stu->user_id = (int) $sr['user_id'];
            $stu->user    = (object) array(
                'last_name'  => (string) $sr['last_name'],
                'first_name' => (string) $sr['first_name'],
            );
            $students_by_grade_id[$gid][] = $stu;
        }

        $grade_result = \DB::select('id', 'grade_name')
            ->from('grades')
            ->order_by('id', 'asc')
            ->execute();
        $grades = array();
        foreach ($grade_result as $grow) {
            $g = (object) $grow;
            $gid = (int) $g->id;
            $g->students = isset($students_by_grade_id[$gid]) ? $students_by_grade_id[$gid] : array();
            $grades[] = $g;
        }
        $students_by_grade = array();
        foreach ($grades as $grade) {
            $students_by_grade[] = array(
                'grade'   => $grade,
                'students' => $grade->students ?: array(),
            );
        }

        // --- 4. 時間枠（コマ）マスタ：中央スケジュール表の行と対応 ---
        $time_slot_result = \DB::select('id', 'slot_name', 'start_time', 'end_time')
            ->from('time_slots')
            ->order_by('id', 'asc')
            ->execute();
        $time_slots = array();
        foreach ($time_slot_result as $tsrow) {
            $time_slots[] = (object) $tsrow;
        }

        // --- 5. 当日の授業データ：lesson_schedules を起点に関連をまとめて取得し $schedules（講師・生徒・複数生徒）へ組み立て ---
        //     5a. 当日分の lesson_schedules を取得し、続く一括取得用に授業 ID 一覧を作る
        $lesson_date = $display_dt->format('Y-m-d');
        $sched_raw = \DB::select(
            'id',
            'lesson_date',
            'time_slot_id',
            'teacher_user_id',
            'student_user_id',
            'subject_id'
        )
            ->from('lesson_schedules')
            ->where('lesson_date', $lesson_date)
            ->order_by('time_slot_id', 'asc')
            ->execute();
        $schedule_ids        = array();
        $schedule_rows_list  = array();
        foreach ($sched_raw as $srow) {
            $schedule_rows_list[] = $srow;
            $schedule_ids[]       = (int) $srow['id'];
        }
        $schedule_ids = array_values(array_unique($schedule_ids));

        //     5b. 複数生徒紐づけを授業IDごとにグループ化
        $ss_by_ls = array();
        if ( ! empty($schedule_ids)) {
            $ss_rows = \DB::select('lesson_schedule_id', 'student_user_id')
                ->from('lesson_schedule_students')
                ->where('lesson_schedule_id', 'in', $schedule_ids)
                ->order_by('id', 'asc')
                ->execute();
            foreach ($ss_rows as $ssrow) {
                $lsid = (int) $ssrow['lesson_schedule_id'];
                if ( ! isset($ss_by_ls[$lsid])) {
                    $ss_by_ls[$lsid] = array();
                }
                $ss_by_ls[$lsid][] = $ssrow;
            }
        }

        //     5c. 表示に必要な users をID一覧で一括取得 → $users_map、students で学年 → $grade_by_user
        $user_ids_needed = array();
        foreach ($schedule_rows_list as $srow) {
            if ( ! empty($srow['teacher_user_id'])) {
                $user_ids_needed[] = (int) $srow['teacher_user_id'];
            }
            if ( ! empty($srow['student_user_id'])) {
                $user_ids_needed[] = (int) $srow['student_user_id'];
            }
        }
        foreach ($ss_by_ls as $list) {
            foreach ($list as $ssrow) {
                $user_ids_needed[] = (int) $ssrow['student_user_id'];
            }
        }
        $user_ids_needed = array_values(array_unique(array_filter($user_ids_needed)));

        $users_map = array();
        if ( ! empty($user_ids_needed)) {
            $urows = \DB::select('id', 'last_name', 'first_name')
                ->from('users')
                ->where('id', 'in', $user_ids_needed)
                ->execute();
            foreach ($urows as $ur) {
                $users_map[(int) $ur['id']] = (object) $ur;
            }
        }

        $grade_by_user = array();
        if ( ! empty($user_ids_needed)) {
            $strows = \DB::select('user_id', 'grade_id')
                ->from('students')
                ->where('user_id', 'in', $user_ids_needed)
                ->execute();
            foreach ($strows as $str) {
                $grade_by_user[(int) $str['user_id']] = (int) $str['grade_id'];
            }
        }

        //     5d. 授業×生徒ごとの科目（テーブルがある場合のみ一括取得 → $lsss_map）
        $has_lsss = \DBUtil::table_exists('lesson_schedule_student_subjects');
        $lsss_map = array();
        if ($has_lsss && ! empty($schedule_ids)) {
            $lrows = \DB::select('lesson_schedule_id', 'student_user_id', 'subject_id')
                ->from('lesson_schedule_student_subjects')
                ->where('lesson_schedule_id', 'in', $schedule_ids)
                ->execute();
            foreach ($lrows as $lr) {
                $k            = (int) $lr['lesson_schedule_id'] . '_' . (int) $lr['student_user_id'];
                $lsss_map[$k] = (int) $lr['subject_id'];
            }
        }

        //     5e. ビュー／下処理と同じ形になるよう $schedules オブジェクトを生成（teacher, student, schedule_students）
        $schedules = array();
        foreach ($schedule_rows_list as $srow) {
            $ls                  = (object) $srow;
            $ls->id              = (int) $ls->id;
            $ls->time_slot_id    = (int) $ls->time_slot_id;
            $ls->teacher_user_id = (int) $ls->teacher_user_id;
            $ls->student_user_id = (isset($srow['student_user_id']) && $srow['student_user_id'] !== null && $srow['student_user_id'] !== '')
                ? (int) $srow['student_user_id']
                : 0;
            $ls->subject_id = (isset($srow['subject_id']) && $srow['subject_id'] !== null)
                ? (int) $srow['subject_id']
                : 0;
            $tuid           = $ls->teacher_user_id;
            $ls->teacher    = ($tuid && isset($users_map[$tuid])) ? $users_map[$tuid] : null;
            $suid           = $ls->student_user_id;
            $ls->student    = ($suid && isset($users_map[$suid])) ? $users_map[$suid] : null;
            $ls->schedule_students = array();
            if (isset($ss_by_ls[$ls->id])) {
                foreach ($ss_by_ls[$ls->id] as $ssrow) {
                    $ss                  = new \stdClass();
                    $ss->student_user_id = (int) $ssrow['student_user_id'];
                    $vsid                = $ss->student_user_id;
                    $ss->student         = isset($users_map[$vsid]) ? $users_map[$vsid] : null;
                    $ls->schedule_students[] = $ss;
                }
            }
            $schedules[] = $ls;
        }

        // --- 6. 科目マスタ：一覧 $subjects と ID→名前 $subject_names_by_id ---
        $subj_res = \DB::select('id', 'subject_name')
            ->from('subjects')
            ->order_by('id', 'asc')
            ->execute();
        $subject_names_by_id = array();
        $subjects            = array();
        foreach ($subj_res as $sj) {
            $subject_names_by_id[(int) $sj['id']] = $sj['subject_name'];
            $subjects[]                         = (object) $sj;
        }

        // --- 7. 中央メイン：時間帯IDごとに $lesson_slots（講師名・生徒表示・編集用ID・units）を構築 ---
        $lesson_slots = array();
        foreach ($time_slots as $ts) {
            $lesson_slots[(int) $ts->id] = array();
        }
        foreach ($schedules as $ls) {
            $tid = (int) $ls->time_slot_id;
            if ( ! isset($lesson_slots[$tid])) {
                $lesson_slots[$tid] = array();
            }
            $teacher_name = ($ls->teacher) ? ($ls->teacher->last_name . ' ' . $ls->teacher->first_name) : '';
            $student_user_ids = array();
            $student_names = array();
            $units = array();
            $display_parts = array();
            if ( ! empty($ls->schedule_students)) {
                foreach ($ls->schedule_students as $ss) {
                    $sid = (int) $ss->student_user_id;
                    $student_user_ids[] = $sid;
                    $student_display_name = $ss->student ? ($ss->student->last_name . ' ' . $ss->student->first_name) : '';
                    $student_names[] = $student_display_name;
                    $grade_id = isset($grade_by_user[$sid]) ? $grade_by_user[$sid] : 0;
                    $subject_id = 0;
                    if ($has_lsss) {
                        $k = (int) $ls->id . '_' . $sid;
                        $subject_id = isset($lsss_map[$k]) ? $lsss_map[$k] : 0;
                    }
                    $subject_display_name = isset($subject_names_by_id[$subject_id]) ? $subject_names_by_id[$subject_id] : '';
                    $display_parts[] = $student_display_name . '(' . $subject_display_name . ')';
                    $units[] = array('grade_id' => $grade_id, 'student_user_id' => $sid, 'subject_id' => $subject_id);
                }
            } elseif ($ls->student_user_id) {
                $sid = (int) $ls->student_user_id;
                $student_user_ids[] = $sid;
                $student_display_name = $ls->student ? ($ls->student->last_name . ' ' . $ls->student->first_name) : '';
                $student_names[] = $student_display_name;
                $stu = \Model_Student::find('first', array('where' => array('user_id' => $sid)));
                $grade_id = $stu ? (int) $stu->grade_id : 0;
                $subject_id = $ls->subject_id ? (int) $ls->subject_id : 0;
                $subject_display_name = isset($subject_names_by_id[$subject_id]) ? $subject_names_by_id[$subject_id] : '';
                $display_parts[] = $student_display_name . '(' . $subject_display_name . ')';
                $units[] = array('grade_id' => $grade_id, 'student_user_id' => $sid, 'subject_id' => $subject_id);
            }
            $student_name = implode('、', $student_names);
            $subject_display = implode('、', $display_parts);
            $lesson_slots[$tid][] = array(
                'id'      => (int) $ls->id,
                'teacher' => $teacher_name,
                'student' => $student_name,
                'subject' => $subject_display,
                'teacher_user_id' => (int) $ls->teacher_user_id,
                'student_user_id' => (int) $ls->student_user_id,
                'student_user_ids' => $student_user_ids,
                'units'   => $units,
                'subject_id'      => (int) $ls->subject_id,
            );
        }

        // --- 8. 右カレンダー周り：ヘッダ表示日・その月の日付グリッド $calendar_weeks ---
        $weekday_ja = array('日', '月', '火', '水', '木', '金', '土');
        $display_date = $display_dt->format('n/j') . '(' . $weekday_ja[(int) $display_dt->format('w')] . ')';

        $cal_year  = (int) $display_dt->format('Y');
        $cal_month = (int) $display_dt->format('n');
        $calendar_month = $cal_month . '月';
        $calendar_year  = $cal_year;

        $first = new \DateTime($cal_year . '-' . $cal_month . '-01');
        $last  = clone $first;
        $last->modify('last day of this month');
        $last_day = (int) $last->format('j');
        $start_w = (int) $first->format('w');

        $calendar_weeks = array();
        $week = array();
        for ($i = 0; $i < $start_w; $i++) {
            $week[] = '';
        }
        for ($d = 1; $d <= $last_day; $d++) {
            $week[] = (string) $d;
            if (count($week) === 7) {
                $calendar_weeks[] = $week;
                $week = array();
            }
        }
        if (count($week) > 0) {
            while (count($week) < 7) {
                $week[] = '';
            }
            $calendar_weeks[] = $week;
        }

        //     8b. その月の全日について lesson_schedules を範囲取得し、日×コマの有無ラベル $monthly_lessons（セルに A/B/C 等）
        $first_str = $first->format('Y-m-d');
        $last_str  = $last->format('Y-m-d');
        $month_result = \DB::select('lesson_date', 'time_slot_id')
            ->from('lesson_schedules')
            ->where('lesson_date', '>=', $first_str)
            ->where('lesson_date', '<=', $last_str)
            ->order_by('lesson_date', 'asc')
            ->order_by('time_slot_id', 'asc')
            ->execute();
        $month_schedules = array();
        foreach ($month_result as $mr) {
            $month_schedules[] = (object) $mr;
        }

        $slot_labels = array();
        foreach ($time_slots as $ts) {
            $slot_labels[(int) $ts->id] = mb_substr($ts->slot_name, 0, 1) ?: (string) $ts->id;
        }
        $monthly_lessons = array();
        foreach ($month_schedules as $ls) {
            $date = $ls->lesson_date;
            $tid = (int) $ls->time_slot_id;
            if ( ! isset($monthly_lessons[$date])) {
                $monthly_lessons[$date] = array();
            }
            $monthly_lessons[$date][$tid] = isset($slot_labels[$tid]) ? $slot_labels[$tid] : (string) $tid;
        }

        $selected_day = (int) $display_dt->format('j');

        // --- 9. カレンダー ‹ › 用：前月・次月へ飛ぶ GET URL（year/month） ---
        $cal_dt = new \DateTime($cal_year . '-' . $cal_month . '-01');
        $prev_dt = clone $cal_dt;
        $prev_dt->modify('first day of previous month');
        $next_dt = clone $cal_dt;
        $next_dt->modify('first day of next month');
        $prev_url = \Uri::create('admin/schedule', array(), array(
            'year'  => $prev_dt->format('Y'),
            'month' => $prev_dt->format('n'),
        ));
        $next_url = \Uri::create('admin/schedule', array(), array(
            'year'  => $next_dt->format('Y'),
            'month' => $next_dt->format('n'),
        ));

        // --- 10. モーダル・フロント用：科目JSON、学年別生徒JSON、履修科目マップ $student_enrollments ---
        $subjects_for_js = array();
        foreach ($subjects as $s) {
            $subjects_for_js[] = array(
                'id'   => (int) $s->id,
                'name' => (string) $s->subject_name,
            );
        }

        $students_flat = array();
        $students_by_grade_json = array();
        foreach ($grades as $grade) {
            $students_in_grade = array();
            if ( ! empty($grade->students)) {
                foreach ($grade->students as $st) {
                    if ($st->user) {
                        $students_flat[] = array(
                            'id'   => (int) $st->user_id,
                            'name' => $st->user->last_name . ' ' . $st->user->first_name,
                        );
                        $students_in_grade[] = array(
                            'id'   => (int) $st->user_id,
                            'name' => $st->user->last_name . ' ' . $st->user->first_name,
                        );
                    }
                }
            }
            $students_by_grade_json[] = array(
                'grade_id'   => (int) $grade->id,
                'grade_name' => $grade->grade_name,
                'students'   => $students_in_grade,
            );
        }

        //     10b. student_subjects から「生徒user_id → 履修科目ID配列」
        $student_enrollments = array();
        $enroll_result = \DB::select('student_user_id', 'subject_id')
            ->from('student_subjects')
            ->execute();
        foreach ($enroll_result as $es) {
            $uid = (int) $es['student_user_id'];
            if ( ! isset($student_enrollments[$uid])) {
                $student_enrollments[$uid] = array();
            }
            $student_enrollments[$uid][] = (int) $es['subject_id'];
        }

        // --- 11. スケジュール画面ビューへ一括渡し ---
        $this->template->content = View::forge('admin/schedule/home', array(
            'teachers'               => $teachers, // 左サイド講師リスト・モーダル講師プルダウン
            'grades'                 => $grades, // 学年マスタ（生徒紐づけ付きオブジェクト）
            'students_by_grade'      => $students_by_grade, // 左サイド：学年見出し＋生徒一覧用

            'students_by_grade_json' => $students_by_grade_json, // JS：学年→生徒選択のデータ
            'student_enrollments'    => $student_enrollments, // JS：生徒ごと履修科目ID（科目絞り込み等）

            'display_date'           => $display_date, // 中央ヘッダ「n/j(曜)」表示
            'time_slots'             => $time_slots, // 中央：時間枠（コマ）の行
            'lesson_slots'           => $lesson_slots, // 中央：コマごとの授業セル（講師・生徒・編集用data等）

            'calendar_month'         => $calendar_month, // 右カレンダー：「n月」見出し
            'calendar_year'          => $calendar_year, // 右カレンダー：年見出し
            'calendar_weeks'         => $calendar_weeks, // 右カレンダー：週ごとの日付マス目

            'selected_day'           => $selected_day, // 選択中の「日」（1〜31）
            'calendar_month_num'     => $cal_month, // カレンダーが表示している月（1〜12）
            'calendar_year_num'      => $cal_year, // カレンダーが表示している年

            'prev_url'               => $prev_url, // 前月へ（GET year/month）
            'next_url'               => $next_url, // 次月へ（GET year/month）

            'schedule_lesson_date'   => $lesson_date, // 表示中の日付 Y-m-d（リンク・フォームhidden・data属性）

            'subjects'               => $subjects, // 科目一覧オブジェクト（テンプレート用）

            'subjects_for_js'        => $subjects_for_js, // 科目を JSON 化してモーダルJSへ
            'students_flat'          => $students_flat, // JS：生徒一覧フラット配列

            'monthly_lessons'        => $monthly_lessons, // その月の日×コマに授業あり（A/B/C表示等）
        ));
    }

    /**
     * スケジュール保存（新規 or 更新）。
     * 生徒・科目のペアをラジオボタン形式で受け取り、トランザクション内で Delete & Insert により
     * lesson_schedules / lesson_schedule_students / lesson_schedule_student_subjects を同期保存する。
     * POST: lesson_schedule_id, lesson_date, time_slot_id, teacher_user_id, student_units[idx][student_user_id], student_units[idx][subject_id]
     */
    public function action_schedule_save()
    {
        if (\Input::method() !== 'POST') {
            \Response::redirect('admin/schedule');
            return;
        }

        $id = (int) \Input::post('lesson_schedule_id', 0);
        $lesson_date = trim((string) \Input::post('lesson_date', ''));
        $time_slot_id = (int) \Input::post('time_slot_id', 0);
        $teacher_user_id = (int) \Input::post('teacher_user_id', 0);
        $student_units_raw = \Input::post('student_units');
        if ( ! is_array($student_units_raw)) {
            $student_units_raw = array();
        }

        $errors = array();
        if ($lesson_date === '') {
            $errors[] = '日付を指定してください。';
        }
        if ($time_slot_id <= 0) {
            $errors[] = '時間枠を指定してください。';
        }
        if ($teacher_user_id <= 0) {
            $errors[] = '講師を選択してください。';
        }

        // 有効なユニットのみ抽出（生徒IDあり かつ 科目を1つ選択しているもの）
        $units_valid = array();
        foreach ($student_units_raw as $u) {
            if ( ! is_array($u)) {
                continue;
            }
            $student_user_id = isset($u['student_user_id']) ? (int) $u['student_user_id'] : 0;
            if ($student_user_id <= 0) {
                continue;
            }
            $subject_id = isset($u['subject_id']) ? (int) $u['subject_id'] : 0;
            if ($subject_id <= 0) {
                continue;
            }
            $units_valid[] = array(
                'student_user_id' => $student_user_id,
                'subject_id'      => $subject_id,
            );
        }
        
        if (empty($units_valid)) {
            $errors[] = '生徒と科目をそれぞれ1つ以上選択してください。';
        }

        if ( ! empty($errors)) {
            \Session::set_flash('error', implode(' ', $errors));
            \Response::redirect(\Uri::create('admin/schedule', array(), \Input::get()));
            return;
        }

        // lesson_schedules.subject_id（NOT NULL）: 有効ユニットの先頭科目。取得できない場合は subjects の最小 ID でフォールバック
        $first_subject_id = (int) $units_valid[0]['subject_id'];
        if ($first_subject_id <= 0) {
            $row = \DB::select(\DB::expr('MIN(id) as min_id'))->from('subjects')->execute()->current();
            $first_subject_id = $row && isset($row['min_id']) && $row['min_id'] !== null
                ? (int) $row['min_id']
                : $first_subject_id;
        }
        if ($first_subject_id <= 0) {
            $errors[] = '科目が登録されていません。先に科目を登録してください。';
            \Session::set_flash('error', implode(' ', $errors));
            \Response::redirect(\Uri::create('admin/schedule', array(), \Input::get()));
            return;
        }

        try {
            \DB::start_transaction();

            if ($id > 0) {
                // 更新: 1. 既存紐づけのクリーンアップ → 2. 基本情報更新
                $schedule = \Model_Lesson_Schedule::find($id);
                if ( ! $schedule) {
                    \DB::rollback_transaction();
                    \Session::set_flash('error', '指定された授業が見つかりません。');
                    \Response::redirect('admin/schedule');
                    return;
                }
                \DB::delete('lesson_schedule_students')->where('lesson_schedule_id', '=', $id)->execute();
                if (\DBUtil::table_exists('lesson_schedule_student_subjects')) {
                    \DB::delete('lesson_schedule_student_subjects')->where('lesson_schedule_id', '=', $id)->execute();
                }
                $schedule->lesson_date = $lesson_date;
                $schedule->time_slot_id = $time_slot_id;
                $schedule->teacher_user_id = $teacher_user_id;
                $schedule->student_user_id = $units_valid[0]['student_user_id'];
                $schedule->subject_id = $first_subject_id;
                $schedule->save();
            } else {
                // 新規: 先に lesson_schedules を 1 件作成して ID を確定
                $schedule = \Model_Lesson_Schedule::forge(array(
                    'lesson_date'      => $lesson_date,
                    'time_slot_id'     => $time_slot_id,
                    'teacher_user_id'  => $teacher_user_id,
                    'student_user_id'  => $units_valid[0]['student_user_id'],
                    'subject_id'       => $first_subject_id,
                ));
                $schedule->save();
            }

            $schedule_id = $schedule->id;

            // 3. 複数生徒・科目の保存（1生徒につき1科目を lesson_schedule_student_subjects に1行）
            foreach ($units_valid as $unit) {
                $student_user_id = $unit['student_user_id'];
                $subject_id = (int) $unit['subject_id'];
                $schedule_student = \Model\Lesson_Schedule_Student::forge(array(
                    'lesson_schedule_id' => $schedule_id,
                    'student_user_id'    => $student_user_id,
                ));
                $schedule_student->save();

                if ($subject_id > 0 && \DBUtil::table_exists('lesson_schedule_student_subjects')) {
                    \DB::insert('lesson_schedule_student_subjects')->set(array(
                        'lesson_schedule_id' => $schedule_id,
                        'student_user_id'    => $student_user_id,
                        'subject_id'         => $subject_id,
                    ))->execute();
                }
            }

            \DB::commit_transaction();
            \Session::set_flash('success', '保存しました。');
        } catch (\Database_Exception $e) {
            \DB::rollback_transaction();
            \Log::error('schedule_save Database_Exception: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            \Session::set_flash('error', '保存に失敗しました。');
        } catch (\Exception $e) {
            \DB::rollback_transaction();
            \Log::error('schedule_save: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            \Session::set_flash('error', '保存に失敗しました。');
        }

        $redirect_params = array();
        $ry = \Input::post('redirect_year', \Input::get('year'));
        $rm = \Input::post('redirect_month', \Input::get('month'));
        $rd = trim((string) \Input::post('redirect_date', ''));
        if ($ry) $redirect_params['year'] = $ry;
        if ($rm) $redirect_params['month'] = $rm;
        if ($rd !== '') $redirect_params['date'] = $rd;
        \Response::redirect(\Uri::create('admin/schedule', array(), $redirect_params));
    }

    /**
     * スケジュール削除。POST: lesson_schedule_id
     */
    public function action_schedule_delete()
    {
        if (\Input::method() !== 'POST') {
            \Response::redirect('admin/schedule');
            return;
        }

        $id = (int) \Input::post('lesson_schedule_id', 0);
        if ($id <= 0) {
            \Session::set_flash('error', '不正なリクエストです。');
            \Response::redirect('admin/schedule');
            return;
        }

        $schedule = \Model_Lesson_Schedule::find($id);
        if ($schedule) {
            try {
                $schedule->delete();
                \Session::set_flash('success', '削除しました。');
            } catch (\Exception $e) {
                \Session::set_flash('error', '削除に失敗しました。');
            }
        }

        $redirect_params = array();
        $ry = \Input::post('redirect_year', \Input::get('year'));
        $rm = \Input::post('redirect_month', \Input::get('month'));
        $rd = trim((string) \Input::post('redirect_date', ''));
        if ($ry) $redirect_params['year'] = $ry;
        if ($rm) $redirect_params['month'] = $rm;
        if ($rd !== '') $redirect_params['date'] = $rd;
        \Response::redirect(\Uri::create('admin/schedule', array(), $redirect_params));
    }

    /**
     * 講師・生徒 追加画面
     */
    public function action_create()
    {
        $this->template->title       = 'ユーザー登録';
        $this->template->style_sheet = 'admin.css';

        // マスタデータ取得（全件）※ null の場合でも必ず配列を渡す
        $grades_result   = Model_Grade::find('all');
        $subjects_result = Model_Subject::find('all');

        $grades   = $grades_result   ?: array();
        $subjects = $subjects_result ?: array();

        // 初期表示用の入力値
        $input = array(
            'target_type'        => 'teacher',
            'last_name'          => '',
            'first_name'         => '',
            'grade_id'           => '',
            'subject_ids'        => array(),
            'parent_last_name'   => '',
            'parent_first_name'  => '',
            'parent_password'    => '',
        );

        $errors = '';

        if (\Input::method() === 'POST') {
            $input['target_type']       = \Input::post('target_type', 'teacher');
            $input['last_name']         = trim((string) \Input::post('last_name', ''));
            $input['first_name']        = trim((string) \Input::post('first_name', ''));
            $input['grade_id']          = (string) \Input::post('grade_id', '');
            $input['subject_ids']       = (array) \Input::post('subject_ids', array());
            $input['parent_last_name']  = trim((string) \Input::post('parent_last_name', ''));
            $input['parent_first_name'] = trim((string) \Input::post('parent_first_name', ''));
            $input['parent_password']   = trim((string) \Input::post('parent_password', ''));

            $raw_password = (string) \Input::post('password', '');

            $has_parent_input = false;
            if ($input['target_type'] === 'student') {
                if ($input['parent_last_name'] !== '' || $input['parent_first_name'] !== '' || $input['parent_password'] !== '') {
                    $has_parent_input = true;
                }
            }

            $val = \Validation::forge();
            $val->add('target_type', '対象')
                ->add_rule('required')
                ->add_rule('in_array', array('teacher', 'student'));
            $val->add('last_name', '氏')
                ->add_rule('required')
                ->add_rule('max_length', 50);
            $val->add('first_name', '名')
                ->add_rule('required')
                ->add_rule('max_length', 50);

            $val->add('password', 'パスワード')
                ->add_rule('required')
                ->add_rule('min_length', 6);

            if ($input['target_type'] === 'student') {
                $val->add('grade_id', '学年')
                    ->add_rule('required');
                $val->add('subject_ids', '受講科目')
                    ->add_rule('required');

                if ($has_parent_input) {
                    $val->add('parent_last_name', '保護者氏')
                        ->add_rule('required')
                        ->add_rule('max_length', 50);
                    $val->add('parent_first_name', '保護者名')
                        ->add_rule('required')
                        ->add_rule('max_length', 50);
                    $val->add('parent_password', '保護者パスワード')
                        ->add_rule('required')
                        ->add_rule('min_length', 6);
                }
            }

            if ( ! $val->run($input)) {
                $errors = $val->show_errors();
            } else {
                try {
                    \DB::start_transaction();

                    // ログイン照合（password_verify）と整合させるため、必ず password_hash(PASSWORD_DEFAULT) を使用
                    $hashed_password        = password_hash($raw_password, PASSWORD_DEFAULT);
                    $hashed_parent_password = $has_parent_input ? password_hash($input['parent_password'], PASSWORD_DEFAULT) : null;

                    $role_id = ($input['target_type'] === 'teacher') ? 2 : 3;
                    $username = $input['last_name'] . $input['first_name'];

                    $user = Model_User::forge(array(
                        'role_id'    => $role_id,
                        'username'   => $username,
                        'password'   => $hashed_password,
                        'first_name' => $input['first_name'],
                        'last_name'  => $input['last_name'],
                    ));
                    $user->save();

                    if ($input['target_type'] === 'student') {
                        $student = Model_Student::forge(array(
                            'user_id'  => (int) $user->id,
                            'grade_id' => (int) $input['grade_id'],
                        ));
                        $student->save();

                        foreach ($input['subject_ids'] as $subject_id) {
                            $subject_id = (int) $subject_id;
                            if ($subject_id <= 0) {
                                continue;
                            }

                            $student_subject = Model_Student_Subject::forge(array(
                                'student_user_id' => (int) $user->id,
                                'subject_id'      => $subject_id,
                            ));
                            $student_subject->save();
                        }

                        if ($has_parent_input && $hashed_parent_password !== null) {
                            $parent_username = $input['parent_last_name'] . $input['parent_first_name'];

                            $parent_user = Model_User::forge(array(
                                'role_id'    => 4,
                                'username'   => $parent_username,
                                'password'   => $hashed_parent_password,
                                'first_name' => $input['parent_first_name'],
                                'last_name'  => $input['parent_last_name'],
                            ));
                            $parent_user->save();

                            $relation = Model_Parent_Student_Relation::forge(array(
                                'parent_user_id'  => (int) $parent_user->id,
                                'student_user_id' => (int) $user->id,
                            ));
                            $relation->save();
                        }
                    }

                    \DB::commit_transaction();

                    \Response::redirect('admin/user_create_complete');
                } catch (\Exception $e) {
                    \DB::rollback_transaction();
                    $errors = $e->getMessage();
                }
            }
        }

        $data = array(
            'grades'   => $grades,
            'subjects' => $subjects,
            // null を渡すと Security::htmlentities 内で get_class(null) が実行され警告になるため、空文字にしておく
            'errors'   => (string) $errors,
            'input'    => $input,
        );

        $this->template->content = View::forge('admin/user_create', $data);
    }

    /**
     * ユーザー登録完了画面
     */
    public function action_create_complete()
    {
        $this->template->title       = '登録完了';
        $this->template->style_sheet = 'admin.css';

        $this->template->content = View::forge('admin/user_create_complete');
    }

    /**
     * 講師・生徒を編集する一覧（学年別生徒・講師リスト）
     */
    public function action_user_list()
    {
        $this->template->title       = '講師・生徒を編集する';
        $this->template->style_sheet = 'admin.css';

        $grades = Model_Grade::find('all', array('order_by' => array('id' => 'asc')));
        $students_by_grade = array();
        foreach ($grades as $grade) {
            $students = Model_Student::find('all', array(
                'where'   => array('grade_id' => $grade->id),
                'related' => array('user'),
            ));
            $students = $students ?: array();
            usort($students, function ($a, $b) {
                $na = $a->user ? $a->user->last_name . $a->user->first_name : '';
                $nb = $b->user ? $b->user->last_name . $b->user->first_name : '';
                return strcmp($na, $nb);
            });
            $students_by_grade[] = array('grade' => $grade, 'students' => $students);
        }

        $teachers = Model_User::find('all', array(
            'where'    => array('role_id' => 2),
            'order_by' => array('last_name' => 'asc', 'first_name' => 'asc'),
        ));

        $this->template->content = View::forge('admin/user_list', array(
            'students_by_grade' => $students_by_grade,
            'teachers'          => $teachers,
        ));
    }

    /**
     * ユーザー編集画面（一覧からの遷移先）
     */
    public function action_edit_user($id)
    {
        $this->template->title       = 'ユーザー編集';
        $this->template->style_sheet = 'admin.css';

        $user = Model_User::find((int) $id);
        if (empty($user)) {
            \Session::set_flash('error', '指定されたユーザーが見つかりません。');
            \Response::redirect('admin/users/edit');
            return;
        }

        $is_student = ((int) $user->role_id === 3);
        $grades     = $is_student ? (Model_Grade::find('all', array('order_by' => array('id' => 'asc'))) ?: array()) : array();
        $subjects   = $is_student ? (Model_Subject::find('all', array('order_by' => array('id' => 'asc'))) ?: array()) : array();

        $student = null;
        $subject_ids = array();
        $grade_id = '';

        if ($is_student) {
            $student = Model_Student::find('first', array(
                'where' => array(
                    array('user_id', (int) $user->id),
                ),
            ));
            $grade_id = $student ? (string) $student->grade_id : '';

            $rels = Model_Student_Subject::find('all', array(
                'where' => array(
                    array('student_user_id', (int) $user->id),
                ),
            )) ?: array();
            foreach ($rels as $rel) {
                $subject_ids[] = (int) $rel->subject_id;
            }
        }

        $input = array(
            'last_name'   => (string) $user->last_name,
            'first_name'  => (string) $user->first_name,
            'grade_id'    => (string) $grade_id,
            'subject_ids' => $subject_ids,
            'password'    => '',
        );

        $errors = '';

        if (\Input::method() === 'POST') {
            $input['last_name']   = trim((string) \Input::post('last_name', ''));
            $input['first_name']  = trim((string) \Input::post('first_name', ''));
            $input['grade_id']    = (string) \Input::post('grade_id', '');
            $input['subject_ids'] = (array) \Input::post('subject_ids', array());
            $raw_password         = (string) \Input::post('password', '');
            $input['password']    = $raw_password;

            $val = \Validation::forge();
            $val->add('last_name', '氏')->add_rule('required')->add_rule('max_length', 50);
            $val->add('first_name', '名')->add_rule('required')->add_rule('max_length', 50);

            $val->add('password', 'パスワード')->add_rule('min_length', 6);

            if ($is_student) {
                $val->add('grade_id', '学年')->add_rule('required');
                $val->add('subject_ids', '受講科目')->add_rule('required');
            }

            if ( ! $val->run($input)) {
                $errors = $val->show_errors();
            } else {
                try {
                    \DB::start_transaction();

                    $user->last_name  = $input['last_name'];
                    $user->first_name = $input['first_name'];

                    if ($raw_password !== '') {
                        // ログイン照合（password_verify）と整合させるため、必ず password_hash(PASSWORD_DEFAULT)
                        $user->password = password_hash($raw_password, PASSWORD_DEFAULT);
                    }

                    $user->save();

                    if ($is_student) {
                        if (empty($student)) {
                            $student = Model_Student::forge(array(
                                'user_id'  => (int) $user->id,
                                'grade_id' => (int) $input['grade_id'],
                            ));
                        } else {
                            $student->grade_id = (int) $input['grade_id'];
                        }
                        $student->save();

                        $existing = Model_Student_Subject::find('all', array(
                            'where' => array(
                                array('student_user_id', (int) $user->id),
                            ),
                        )) ?: array();

                        foreach ($existing as $ex) {
                            $ex->delete();
                        }

                        foreach ($input['subject_ids'] as $sid) {
                            $sid = (int) $sid;
                            if ($sid <= 0) {
                                continue;
                            }
                            $ss = Model_Student_Subject::forge(array(
                                'student_user_id' => (int) $user->id,
                                'subject_id'      => $sid,
                            ));
                            $ss->save();
                        }
                    }

                    \DB::commit_transaction();

                    \Session::set_flash('success', '更新しました');
                    \Response::redirect('admin/edit_user/' . (int) $user->id);
                    return;
                } catch (\Exception $e) {
                    \DB::rollback_transaction();
                    $errors = $e->getMessage();
                }
            }
        }

        $this->template->content = View::forge('admin/user_edit', array(
            'user'     => $user,
            'is_student' => $is_student,
            'grades'   => $grades,
            'subjects' => $subjects,
            'errors'   => (string) $errors,
            'input'    => $input,
        ));
    }

    /**
     * 【テスト用】指定ユーザーのパスワードを強制的に "testpass123" に変更する。
     * ログイン照合の検証用。本番では削除すること。
     */
    public function action_force_test_password($id)
    {
        $user = Model_User::find((int) $id);
        if (empty($user)) {
            \Session::set_flash('error', '指定されたユーザーが見つかりません。');
            \Response::redirect('admin/users/edit');
            return;
        }
        $user->password = password_hash('testpass123', PASSWORD_DEFAULT);
        $user->save();
        \Session::set_flash('success', 'このユーザーのパスワードを "testpass123" に強制変更しました。ログイン検証後に元に戻してください。');
        \Response::redirect('admin/edit_user/' . (int) $user->id);
    }

    /**
     * ユーザー削除
     */
    public function action_delete_user($id)
    {
        if (\Input::method() !== 'POST') {
            \Response::redirect('admin/users/edit');
            return;
        }

        $user = Model_User::find((int) $id);
        if (empty($user)) {
            \Session::set_flash('error', '指定されたユーザーが見つかりません。');
            \Response::redirect('admin/users/edit');
            return;
        }

        $deleted_parent_count = 0;

        try {
            \DB::start_transaction();

            if ((int) $user->role_id === 3) {
                // 1. 生徒関連：受講科目 → 生徒詳細の順で削除
                $existing = Model_Student_Subject::find('all', array(
                    'where' => array(
                        array('student_user_id', (int) $user->id),
                    ),
                )) ?: array();
                foreach ($existing as $ex) {
                    $ex->delete();
                }

                $student = Model_Student::find('first', array(
                    'where' => array(
                        array('user_id', (int) $user->id),
                    ),
                ));
                if ($student) {
                    $student->delete();
                }

                // 2. この生徒に紐づく親子関係から parent_user_id を取得
                $child_relations = Model_Parent_Student_Relation::find('all', array(
                    'where' => array(
                        array('student_user_id', (int) $user->id),
                    ),
                )) ?: array();

                $parent_ids = array();
                foreach ($child_relations as $rel) {
                    $parent_ids[(int) $rel->parent_user_id] = true;
                }
                $parent_ids = array_keys($parent_ids);

                // 3. 他に紐付いている生徒がいない親のみ users を削除
                foreach ($parent_ids as $parent_id) {
                    $other_relations = Model_Parent_Student_Relation::find('all', array(
                        'where' => array(
                            array('parent_user_id', $parent_id),
                            array('student_user_id', '!=', (int) $user->id),
                        ),
                    )) ?: array();

                    if (count($other_relations) === 0) {
                        $parent_rels = Model_Parent_Student_Relation::find('all', array(
                            'where' => array(
                                array('parent_user_id', $parent_id),
                            ),
                        )) ?: array();
                        foreach ($parent_rels as $pr) {
                            $pr->delete();
                        }
                        $parent_user = Model_User::find($parent_id);
                        if ($parent_user) {
                            $parent_user->delete();
                            $deleted_parent_count++;
                        }
                    }
                }

                // 4. 削除対象ユーザーに紐づく親子関係を削除（生徒側として残っている分）
                $relations_child = Model_Parent_Student_Relation::find('all', array(
                    'where' => array(
                        array('student_user_id', (int) $user->id),
                    ),
                )) ?: array();
                foreach ($relations_child as $rel) {
                    $rel->delete();
                }
            }

            // 5. 削除対象が親または講師等の場合：自身を親とする紐付けを削除
            $relations_parent = Model_Parent_Student_Relation::find('all', array(
                'where' => array(
                    array('parent_user_id', (int) $user->id),
                ),
            )) ?: array();
            foreach ($relations_parent as $rel) {
                $rel->delete();
            }

            $user->delete();

            \DB::commit_transaction();

            if ((int) $user->role_id === 3 && $deleted_parent_count > 0) {
                \Session::set_flash('success', '生徒および（該当する場合は）親御さんのデータを削除しました');
            } elseif ((int) $user->role_id === 3) {
                \Session::set_flash('success', '生徒のデータを削除しました');
            } else {
                \Session::set_flash('success', '削除しました');
            }
        } catch (\Exception $e) {
            \DB::rollback_transaction();
            \Session::set_flash('error', $e->getMessage());
        }

        \Response::redirect('admin/users/edit');
        return;
    }
}
