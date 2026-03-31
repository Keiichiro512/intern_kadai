<?php

class Controller_Admin extends Controller_Base
{
    public $template = 'template';

    // $lesson_date は、呼び出し元で DateTime を format('Y-m-d') した結果として渡す想定の 文字列です。
    protected function build_lesson_slots_for_date($lesson_date){
          // 時間枠（コマ）取得
          $time_slot_result = \DB::select('id', 'slot_name', 'start_time', 'end_time')
          ->from('time_slots')
          ->order_by('id', 'asc')
          ->execute();
      $time_slots = array();
      foreach ($time_slot_result as $tsrow) {
          $time_slots[] = (object) $tsrow;
      }

      // 当日の授業データを組み立て（L147-272）
        // --- 5. 選択した日付の授業データ：lesson_schedules を起点に関連をまとめて取得し $schedules（講師・生徒・複数生徒）へ組み立て ---
        // 選択した日付の授業の取得（L150-172）//     5a. 当日分の lesson_schedules を取得し、続く一括取得用に授業 ID 一覧を作る
        // 日付オブジェクトを文字列に変換している処理です。
        // $lesson_date = $display_dt->format('Y-m-d');
        $sched_raw = \DB::select('id', 'lesson_date', 'time_slot_id', 'teacher_user_id', 'student_user_id', 'subject_id')
            ->from('lesson_schedules')
            ->where('lesson_date', $lesson_date)
            ->order_by('time_slot_id', 'asc')
            ->execute();

        // IDだけを取り出して、$schedule_ids に格納している
            $schedule_ids        = array();
            // 行データ本体 を $schedule_rows_list に格納している    
        $schedule_rows_list  = array();
        foreach ($sched_raw as $srow) {
            $schedule_rows_list[] = $srow;
            $schedule_ids[]       = (int) $srow['id'];
        }
        // array_values は、配列の「値」だけを取り出し、キーを 0 から振り直した新しい配列を返す PHP の関数です。
        // array_unique は、配列の中の重複を削除した新しい配列を返す PHP の関数です。
        $schedule_ids = array_values(array_unique($schedule_ids));
        // array(0 => 10, 1 => 11, 2 => 12)
        // array_values(array_unique($schedule_ids)) は、array(0 => 10, 1 => 11, 2 => 12) を array(10, 11, 12) に変換します。

        // 複数生徒紐づけの取得（L175-189）
        // 5b. 複数生徒紐づけを授業IDごとにグループ化
        $ss_by_ls = array();
        // empty($schedule_ids) は、$schedule_ids が空の配列かどうかをチェックする PHP の関数です。
        if ( ! empty($schedule_ids)) {
            $ss_rows = \DB::select('lesson_schedule_id', 'student_user_id')
                ->from('lesson_schedule_students')
                ->where('lesson_schedule_id', 'in', $schedule_ids)
                ->order_by('id', 'asc')
                ->execute();
            // $ss_rows 仮データ例（5行）※ lesson_schedule_students の1行イメージ
            // +-----+----------------------+-------------------+--------------------------------+
            // | 行  | lesson_schedule_id   | student_user_id   | 備考                           |
            // +-----+----------------------+-------------------+--------------------------------+
            // | A   | 10                   | 201               | 授業10の1人目                  |
            // | B   | 10                   | 202               | 授業10の2人目                  |
            // | C   | 10                   | 203               | 授業10の3人目                  |
            // | D   | 11                   | 201               | 授業11（別コマ）               |
            // | E   | 12                   | 205               | 授業12                         |
            // +-----+----------------------+-------------------+--------------------------------+
            // 上記を下の foreach で授業IDごとにまとめると $ss_by_ls は例えば次の形になる:
            // $ss_by_ls = [
            //     10 => [ $ss_rows の行A, 行B, 行C ],  // 授業ID 10 に生徒3人分の行
            //     11 => [ 行D ],
            //     12 => [ 行E ],
            // ];
            foreach ($ss_rows as $ssrow) {
                $lsid = (int) $ssrow['lesson_schedule_id'];
                if ( ! isset($ss_by_ls[$lsid])) {
                    $ss_by_ls[$lsid] = array();
                }
                $ss_by_ls[$lsid][] = $ssrow;
            }
        }

        // 複数生徒紐づけの取得（L175-189）//     5c. 表示に必要な users をID一覧で一括取得 → $users_map、students で学年 → $grade_by_user
        // 授業に関係しそうなUserだけを取っている
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
        // array_values は、配列の「値」だけを取り出し、キーを 0 から振り直した新しい配列を返す PHP の関数です。
        // array_unique は、配列の中の重複を削除した新しい配列を返す PHP の関数です。
        // array_filter は、array_filter は、「PHP が偽とみなす値」を除きます。除かれる例: 0, 0.0, '', null, false
        $user_ids_needed = array_values(array_unique(array_filter($user_ids_needed)));
        // 授業に関係しそうなUserだけを取っている　→ $user_ids_needed = [10, 20, 12, 22, 30]

        $users_map = array();
        if ( ! empty($user_ids_needed)) {
            // users から id, last_name, first_name を、id が $user_ids_needed に含まれる行だけ取得。
            $urows = \DB::select('id', 'last_name', 'first_name')
                ->from('users')
                ->where('id', 'in', $user_ids_needed)
                ->execute();
            foreach ($urows as $ur) {
                $users_map[(int) $ur['id']] = (object) $ur;
            }
            // 「ユーザーID → その人のオブジェクト」の連想配列
            // $users_map = [
            //     5  => (object)['id' => 5,  'last_name' => '田中', 'first_name' => '太郎'],
            //     12 => (object)['id' => 12, 'last_name' => '山田', 'first_name' => '花子'],
            //     // ...
            // ];
        }
        // students から user_id, grade_id を、user_id が $user_ids_needed に含まれる行だけ取得。
        $grade_by_user = array();
        if ( ! empty($user_ids_needed)) {
            $strows = \DB::select('user_id', 'grade_id')
                ->from('students')
                ->where('user_id', 'in', $user_ids_needed)
                ->execute();
            foreach ($strows as $str) {
                $grade_by_user[(int) $str['user_id']] = (int) $str['grade_id'];
            }
            // 「ユーザーID → 学年ID」の連想配列
            // $grade_by_user = [
            //     5  => 2,   // user 5 は学年ID 2
            //     12 => 1,
            //     講師だけなど students に行がない user はキー自体が無い
            // ];
        }

        // 生徒がどの科目を選択しているかを取得（L231-242）//     5d. 授業×生徒ごとの科目（テーブルがある場合のみ一括取得 → $lsss_map）
        $has_lsss = \DBUtil::table_exists('lesson_schedule_student_subjects');
        $lsss_map = array();
        if ($has_lsss && ! empty($schedule_ids)) {
            // 選択した日付中の授業について、生徒がどの科目を選択しているかを取得
            $lrows = \DB::select('lesson_schedule_id', 'student_user_id', 'subject_id')
                ->from('lesson_schedule_student_subjects')
                ->where('lesson_schedule_id', 'in', $schedule_ids)
                ->execute();
            foreach ($lrows as $lr) {
                $k            = (int) $lr['lesson_schedule_id'] . '_' . (int) $lr['student_user_id'];
                $lsss_map[$k] = (int) $lr['subject_id'];
            }
            // 授業ID_生徒ID → 科目ID　の連想配列
            // $lsss_map = [
            //     "6_22" => 3,
            //     "6_23" => 4,
            //     "7_22" => 4,  // 7_22 の生徒は科目ID 4
            //     "7_21" => 5,  // 7_21 の生徒は科目ID 5
            //   ] 
        }

        // 最終的な授業オブジェクト化（L245-272） //     5e. ビュー／下処理と同じ形になるよう $schedules オブジェクトを生成（teacher, student, schedule_students）
        $schedules = array();
        // $schedule_rows_list = [
        //     'id'               => ...,
        //     'lesson_date'      => '2026-03-28',
        //     'time_slot_id'(授業コマ)     => ...,
        //     'teacher_user_id'  => ...,
        //     'student_user_id'  => ... または null / ''（レガシー用の「代表生徒」）
        //     'subject_id'       => ...,
        //   ]

        foreach ($schedule_rows_list as $srow) {
            $ls                  = (object) $srow;
            $ls->id              = (int) $ls->id;
            $ls->time_slot_id    = (int) $ls->time_slot_id;
            $ls->teacher_user_id = (int) $ls->teacher_user_id;

            // isset でキー未定義・null を除外。!== '' で空文字のみ「生徒なし」。
            $ls->student_user_id = (isset($srow['student_user_id']) && $srow['student_user_id'] !== '') ? (int) $srow['student_user_id'] : 0;
            $ls->subject_id = isset($srow['subject_id']) ? (int) $srow['subject_id'] : 0;

            // ユーザーIDだけだった講師・生徒を、$users_map を使って「名前などが載ったオブジェクト」に展開しているコードです。
            $tuid           = $ls->teacher_user_id;
            // $tuid && isset($users_map[$tuid]) = ID が 0 でない（偽の ID は使わない）かつ $users_map にその ID の行がある（先に DB でまとめて取った users の辞書）
            $ls->teacher    = ($tuid && isset($users_map[$tuid])) ? $users_map[$tuid] : null;
            $suid           = $ls->student_user_id;
            $ls->student    = ($suid && isset($users_map[$suid])) ? $users_map[$suid] : null;

            // $ss_by_ls = [
            //     10 => [
            //         ['lesson_schedule_id' => 10, 'student_user_id' => 201],
            //         ['lesson_schedule_id' => 10, 'student_user_id' => 202],
            //         ['lesson_schedule_id' => 10, 'student_user_id' => 203],
            //     ],
            //     9 => [
            //         ['lesson_schedule_id' => 9, 'student_user_id' => 201],
            //         ['lesson_schedule_id' => 9, 'student_user_id' => 202],
            //     ],
            //     8 => [
            //         ['lesson_schedule_id' => 8, 'student_user_id' => 301],
            //     ],
            // ];

            // $ss_by_ls = array(
            //     10 => array(
            //         array('lesson_schedule_id' => 10, 'student_user_id' => 201),
            //         array('lesson_schedule_id' => 10, 'student_user_id' => 202),
            //         array('lesson_schedule_id' => 10, 'student_user_id' => 203),
            //     ),
            //     9 => array(
            //         array('lesson_schedule_id' => 9, 'student_user_id' => 201),
            //         array('lesson_schedule_id' => 9, 'student_user_id' => 202),
            //     ),
            //     8 => array(
            //         array('lesson_schedule_id' => 8, 'student_user_id' => 301),
            //     ),
            // );

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

            // 「配列の行」→「生徒ID＋ユーザー情報付きオブジェクト」
            // 具体例（$ls->id === 10 で $users_map[201] と $users_map[202] があるとき）:
            // $ls->schedule_students = [
            //     オブジェクト { student_user_id: 201, student: (object) usersの201 },
            //     オブジェクト { student_user_id: 202, student: (object) usersの202 },
            //   ]
        }



        // 科目マスタ取得（L275-284）// --- 6. 科目マスタ：一覧 $subjects と ID→名前 $subject_names_by_id ---
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

        // 中央の時間割表示データ作成（L287-342）// --- 7. 中央メイン：時間帯IDごとに $lesson_slots（講師名・生徒表示・編集用ID・units）を構築 ---
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
        return array('lesson_slots' => $lesson_slots, 'time_slots' => $time_slots, 'subjects' => $subjects);
        // 呼び出し元では、このように使う。
        // $data = $this->build_lesson_slots_for_date($lesson_date);
        // $time_slots   = $data['time_slots'];
        // $lesson_slots = $data['lesson_slots'];
        // $subjects = $data['subjects'];
    }
    // retrun されるデータの中身

    // array(
    // 'time_slots'   => array( /* 下記 */ ),
    // 'lesson_slots' => array( /* 下記 */ ),
    // 'subjects'     => array( /* 下記 */ ),
    // );

    // time_slots =  array(
    //     (object) array('id' => 1, 'slot_name' => '1限', 'start_time' => '09:00:00', 'end_time' => '10:30:00'),
    //     (object) array('id' => 2, 'slot_name' => '2限', 'start_time' => '10:40:00', 'end_time' => '12:10:00'),
    //     (object) array('id' => 3, 'slot_name' => '3限', 'start_time' => '13:00:00', 'end_time' => '14:30:00'),
    // );

    // めっちゃ無駄じゃね？？？？
    // // 今はの私の頭での改善策→lesson_slotsをもっと簡潔にする
    // 具体的には、各コマごとに授業がいくつあるかをlesson_idをarrayで持つようにする.
    // さらに、その授業ごとに生徒、講師をオブジェクトで持つようにする.
    // さらに、生徒には(student_user_id,grade_id？,subject_id？,生徒の名前？),講師には(teacher_user_id,講師の名前？)を持つようにする.
    // 現状の（疑問、学年、科目、生徒の名前、講師の名前）これらはどのように取得して、変数に代入するのか？？？？？？？

    // lesson_slots = array(
    //     1 => array(
    //         array(
    //             'id'               => 101,
    //             'teacher'          => '山田 太郎',
    //             'student'          => '鈴木 花子、佐藤 次郎',
    //             'subject'          => '鈴木 花子(数学)、佐藤 次郎(英語)',
    //             'teacher_user_id'  => 12,
    //             'student_user_id'  => 201,  // レガシー列の代表（複数生徒時は 0 になり得る）
    //             'student_user_ids' => array(201, 202),
    //             'units'            => array(
    //                 array('grade_id' => 2, 'student_user_id' => 201, 'subject_id' => 3),
    //                 array('grade_id' => 2, 'student_user_id' => 202, 'subject_id' => 5),
    //             ),
    //             'subject_id'       => 3,
    //         ),
    //     ),
    //     2 => array(),  // このコマは 2026-03-29 に授業なし
    //     3 => array(
    //         array(
    //             'id'               => 105,
    //             'teacher'          => '田中 一郎',
    //             'student'          => '高橋 三郎',
    //             'subject'          => '高橋 三郎(国語)',
    //             'teacher_user_id'  => 15,
    //             'student_user_id'  => 205,
    //             'student_user_ids' => array(205),
    //             'units'            => array(
    //                 array('grade_id' => 1, 'student_user_id' => 205, 'subject_id' => 1),
    //             ),
    //             'subject_id'       => 1,
    //         ),
    //     ),
    // )

    // これも「科目名を取り出す関数」として別で撮っていたほうが良かったのか？？？？？
    //subjects = array(
    //     (object) array('id' => 1, 'subject_name' => '国語'),
    //     (object) array('id' => 2, 'subject_name' => '数学'),
    //     (object) array('id' => 3, 'subject_name' => '英語'),
    // )


    // 管理者ホーム画面を表示
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
        // 画面の基本設定（L19-20）
        $this->template->title       = '授業スケジュール';
        $this->template->style_sheet = 'admin.css';

        // 表示対象日を決める（L25-50）

        // URL に year がなければ date('Y')（サーバー実行時の今年）を使う。
        $year  = (int) \Input::get('year', date('Y'));
        // URL に month がなければ date('n')（サーバー実行時の今月）を使う。
        $month = (int) \Input::get('month', date('n'));
        //$month を「1 以上 12 以下」にそろえる（はみ出した値を切り詰める）
        $month = max(1, min(12, $month));
        //ブラウザの URL の ?date=2025-03-21 のような GET パラメータ date を文字列で取ります。
        $date_str = \Input::get('date'); //URL の ?date=2026-03-31 なら $date_str は '2026-03-31'
        
        // 「月の移動後の日付の表示」「授業保存後の日付表示」
        if ($date_str) {
            // $date_str（例: '2026-03-31'）を、書式 'Y-m-d' のルールで読み取り、その1日を表す DateTime（または失敗時は false）を1つ生成して、$display_dt に代入している。
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
        $lesson_date = $display_dt->format('Y-m-d');
        $data = $this->build_lesson_slots_for_date($lesson_date);
        $time_slots = $data['time_slots'];
        $lesson_slots = $data['lesson_slots'];
        $subjects = $data['subjects'];

        // 左サイドバー用データ作成（L53-134）
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

        $student_rows = \DB::select('students.user_id', 'students.grade_id', 'users.last_name', 'users.first_name')
            ->from('students')
            ->join('users', 'INNER')
            ->on('students.user_id', '=', 'users.id')
            ->order_by('students.grade_id', 'asc') 
            ->order_by('users.last_name', 'asc') 
            ->order_by('users.first_name', 'asc') 
            ->execute();
            
        // 生徒情報を取り出して、学年ごとに並べる　＃1
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
            // $stu->user_id = ... と書いた時点で その場で user_id プロパティが定義される
            $stu->user_id = (int) $sr['user_id'];
            // $stu->~~と書いた時点で、その場で プロパティが定義されて、そのプロパティに値を入れることができる。
            // 連想配列を (object) で包むと、キーがプロパティ名になる 汎用オブジェクト（実体は stdClass）になr
            $stu->user    = (object) array(
                'last_name'  => (string) $sr['last_name'],
                'first_name' => (string) $sr['first_name'],
            );
            // 『new \stdClass() は不要の書き方』
            // $stu = (object) array(
            //     'user_id' => (int) $sr['user_id'],
            //     'user'    => (object) array(
            //         'last_name'  => (string) $sr['last_name'],
            //         'first_name' => (string) $sr['first_name'],
            //     ),
            // );

            $students_by_grade_id[$gid][] = $stu;
        }
        // （$stu = ）stdClass_1 {
        //     $user_id(stdClass_1のプロパティ) = 42
        //     $user(stdClass_1のプロパティ) = stdClass_2 {
        //         $last_name(stdClass_2のプロパティ)  = 文字列（$sr['last_name'] を (string) したもの）
        //         $first_name(stdClass_2のプロパティ) = 文字列（$sr['first_name'] を (string) したもの）
        //     }
        // }

        // [
        //     1 => [ $stu_A, $stu_B, ... ],   // 学年ID 1 の生徒たち（各 $stu は上記の構造）
        //     2 => [ $stu_C, ... ],
        //     3 => [ ... ],
        // ]

        // 学年情報を取り出して、学年ごとに並べる　＃2
        // 全学年をマスタから出す・生徒ゼロの学年も枠だけ出す・表示順を grades に合わせるため。
        $grade_result = \DB::select('id', 'grade_name')
            ->from('grades')
            ->order_by('id', 'asc')
            ->execute();
        $grades = array();
        foreach ($grade_result as $grow) {
            // DB の行がすでに配列として揃っているので、(object) $grow の1行で id と grade_name をまとめてオブジェクト化できるから,$g = new \stdClass() は不要
            $g = (object) $grow;
            $gid = (int) $g->id;
            $g->students = isset($students_by_grade_id[$gid]) ? $students_by_grade_id[$gid] : array();
            $grades[] = $g;
        }
        // 学年ごとに生徒をまとめた配列を作る処理
        $students_by_grade = array();
        foreach ($grades as $grade) {
            $students_by_grade[] = array(
                'grade'   => $grade,
                'students' => $grade->students ?: array(),
            );
        }
        // $students_by_grade = [
        //     ['grade' => 1年, 'students' => [生徒A, 生徒B]],
        //     ['grade' => 2年, 'students' => [生徒C]],
        //     ['grade' => 3年, 'students' => []], // 生徒がいない学年
        //   ]

        // 右カレンダー用データ作成（L345-407） // --- 8. 右カレンダー周り：ヘッダ表示日・その月の日付グリッド $calendar_weeks ---
        $weekday_ja = array('日', '月', '火', '水', '木', '金', '土');
        $display_date = $display_dt->format('n/j') . '(' . $weekday_ja[(int) $display_dt->format('w')] . ')'; 

        
        // $display_dt = '2026-03-29'; 日曜だとする
        // $weekday_ja = array('日', '月', '火', '水', '木', '金', '土');　　　　日曜日は$weekday_ja[0]
        // $display_dt->format('n/j') 　　　　　　　　　　　　　　　　　　　　　　→→→→→→→→　で　3/29
        // '(' . $weekday_ja[(int) $display_date->format('w')] . ')'　　→→→→→→→→ で　(日)
        // 「.」コンマは結合
        //結果的に　"3/29(日)"　という表記になる

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
        // $start_w は 1日の曜日（0=日〜6=土） なので、その分だけ先頭に '' を入れます。（最初の週）
        for ($i = 0; $i < $start_w; $i++) {
            $week[] = '';
        }
        // 1日から最終日まで順に文字列で積み、7個たまるたびに1週分として $calendar_weeks に入れます。(カレンダー上の最初と最後の週以外)
        for ($d = 1; $d <= $last_day; $d++) {
            $week[] = (string) $d;
            if (count($week) === 7) {
                $calendar_weeks[] = $week;
                $week = array();
            }
        }
        // 最後の週が7列に足りなければ、残りを '' で埋めてから1行にします。（最後の週）
        if (count($week) > 0) {
            while (count($week) < 7) {
                $week[] = '';
            }
            $calendar_weeks[] = $week;
        }
        // 1日が土曜なら $start_w = 6 なので、最初の週は
        // ['', '', '', '', '', '', '1'] のように 前6マスが空白 になります。
        // ビューでは foreach ($calendar_weeks as $week) → foreach ($week as $day) でこの表を <tr> / <td> にしています。
        // 
        // $calendar_weeks = array(
        //     array('',   '',   '',   '1',  '2',  '3',  '4'),
        //     array('5',  '6',  '7',  '8',  '9',  '10', '11'),
        //     array('12', '13', '14', '15', '16', '17', '18'),
        //     array('19', '20', '21', '22', '23', '24', '25'),
        //     array('26', '27', '28', '29', '30', '',   ''),
        // )


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

        // $monthly_lessons = array(
        //     '2026-03-04' => array(
        //         1 => 'A',  // 時間枠1 → 名前の頭文字など
        //         2 => 'B',
        //     ),
        //     '2026-03-10' => array(
        //         1 => 'A',
        //         3 => 'C',
        //     ),
        // )

        $selected_day = (int) $display_dt->format('j');

        // 前月・次月リンク作成（L410-422）// --- 9. カレンダー ‹ › 用：前月・次月へ飛ぶ GET URL（year/month） ---
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

        // フロント（JS）向けデータ作成（L425-469）// --- 10. モーダル・フロント用：科目JSON、学年別生徒JSON、履修科目マップ $student_enrollments ---
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

        // ビューへ一括で渡す（L473-504） // --- 11. スケジュール画面ビューへ一括渡し ---
        // 画面を描画するために必要な全データを、1つの連想配列に詰めてビューへ渡す
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

    public function action_schedule_day(){

        $this->template->title = 'スケジュール詳細';
        $this->template->style_sheet = 'admin.css';
        
        $date_str = \Input::get('date');
        if ($date_str) {
            // createFromFormat は失敗すると false を返します。その状態で ->format() するとエラー
            $display_dt = \DateTime::createFromFormat('Y-m-d', $date_str);
        }else {
                \Response::redirect('admin/schedule');
                return;
            }
        
            $lesson_date = $display_dt->format('Y-m-d');
            $weekday_ja = array('日', '月', '火', '水', '木', '金', '土');
            $display_date = $display_dt->format('n/j') . '(' . $weekday_ja[(int) $display_dt->format('w')] . ')';

            $data = $this->build_lesson_slots_for_date($date_str);
            $time_slots = $data['time_slots'];
            $lesson_slots = $data['lesson_slots'];
            $subjects     = $data['subjects'];        
            
            $this->template->content = View::forge('admin/schedule/day', array(
                'display_date' => $display_date,
                'time_slots' => $time_slots,
                'lesson_slots' => $lesson_slots,
        ));
    }

    public function action_schedule_report(){
        $this->template->title = 'スケジュールレポート';
        $this->template->style_sheet = 'admin.css';
        
        $GET_schedule_id = \Input::get('schedule_id');
        $row = \DB::select('id', 'lesson_schedule_id', 'unit_name', 'homework_achievement_rate', 'homework_accuracy_rate', 'lesson_report', 'next_homework', 'parent_message')
            ->from('reports')
            ->where('lesson_schedule_id', $GET_schedule_id)
            ->execute();

        $reports = array();
        foreach ($row as $r) {
            // PHP の組み込み関数 is_array($変数) は、引数が 配列（array 型）かどうかを調べます。true / false を返す。
            $r = is_array($r) ? $r : (array) $r;
            foreach (array('homework_achievement_rate', 'homework_accuracy_rate', 'parent_message') as $key) {
                if (array_key_exists($key, $r) && $r[$key] === null) {
                    // $a = array(
                    //     'x' => 1,
                    //     'y' => null,
                    // );
                    
                    // array_key_exists('x', $a);  // true
                    // array_key_exists('y', $a);  // true（値は null だがキーはある）
                    // array_key_exists('z', $a);  // false
                    // isset($a['y']);             // false（値が null）

                    $r[$key] = '';
                }
            }
            $reports[] = (object) $r;
        };

        $this->template->content = View::forge('admin/schedule/report', array(
            'reports' => $reports,
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
            $row = \DB::select(\DB::expr('MIN(id) as min_id'))
                ->from('subjects')
                ->execute()
                ->current();
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
                \DB::delete('lesson_schedule_students')
                    ->where('lesson_schedule_id', '=', $id)
                    ->execute();
                if (\DBUtil::table_exists('lesson_schedule_student_subjects')) {
                    \DB::delete('lesson_schedule_student_subjects')
                        ->where('lesson_schedule_id', '=', $id)
                        ->execute();
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
                    \DB::insert('lesson_schedule_student_subjects')
                        ->set(array('lesson_schedule_id' => $schedule_id, 'student_user_id' => $student_user_id, 'subject_id' => $subject_id))
                        ->execute();
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


private function wants_json()
    {
        // FuelPHP: リクエストヘッダを取得（なければ null/false になる想定）
        $accept = (string) \Input::headers('Accept');
        $xrw    = (string) \Input::headers('X-Requested-With');
    
        // 1) Accept に application/json を含む
        $accept_wants_json = (stripos($accept, 'application/json') !== false);
        // stripos: 文字列に特定の単語が「含まれているか」を大文字小文字を無視して調べます。
    
        // 2) X-Requested-With が XMLHttpRequest
        $is_xhr = (strcasecmp($xrw, 'XMLHttpRequest') === 0);
        // strcasecmp: 文字列が「完全一致」するか（大文字小文字無視）を調べます。
    
        // 3) どちらか満たせば true
        return $accept_wants_json || $is_xhr;
    }

    private function build_schedule_redirect_url(){
        // 削除処理のあとに「元の表示（年/月/日）」へ戻すためのリダイレクトURLを組み立てて遷移する処理
        $redirect_params = array();
        // redirect_year,redirect_month,redirect_date を POST優先で取り
        // POSTになければ GET の year,month を参照します（dateはPOSTのみで、文字列として取得→trim）。
        $ry = \Input::post('redirect_year', \Input::get('year'));
        $rm = \Input::post('redirect_month', \Input::get('month'));
        $rd = trim((string) \Input::post('redirect_date', ''));

        // 値があるものだけ year,month,date を $redirect_params に詰めます
        if ($ry)       $redirect_params['year']  = $ry;
        if ($rm)       $redirect_params['month'] = $rm;
        if ($rd !== '') $redirect_params['date']  = $rd;

        // 第3引数がクエリ文字列になる（?year=...&month=...&date=...）
        return \Uri::create('admin/schedule', array(), $redirect_params);
    }

        /**
     * スケジュール削除用のコード
     */
    public function action_schedule_delete(){
        $is_json = $this->wants_json();
        $redirect_url = $this->build_schedule_redirect_url();

        // OST じゃない（例: GET で開いてしまった）
        if (\Input::method() !== 'POST') {
            if ($is_json) {
                return \Response::forge(
                    json_encode(array('ok' => false, 'error' => '不正なリクエストです。')),
                    405
                )->set_header('Content-Type', 'application/json; charset=utf-8');
            }
            \Response::redirect('admin/schedule');
            return;
        }

        $id = (int) \Input::post('lesson_schedule_id', 0);

        // POST だが ID が不正（0や空など）
        if ($id <= 0) {
            if ($is_json) {
                return \Response::forge(
                    json_encode(array('ok' => false, 'error' => '不正なリクエストです。')),
                    422
                )->set_header('Content-Type', 'application/json; charset=utf-8');
            }
            \Session::set_flash('error', '不正なリクエストです。');
            \Response::redirect('admin/schedule');
            return;
        }

        // POST で ID 正常、対象が存在して削除成功
        try {
            $schedule = \Model_Lesson_Schedule::find($id);
            if ($schedule) {
                $schedule->delete();
            }
            // 「見つからない」場合をどう扱うかは好みですが、現状コードに合わせるなら成功扱いでもOK

            if ($is_json) {
                return \Response::forge(
                    json_encode(array('ok' => true, 'redirect' => $redirect_url)),
                    200
                )->set_header('Content-Type', 'application/json; charset=utf-8');
            }

            \Session::set_flash('success', '削除しました。');
        } catch (\Exception $e) {
            if ($is_json) {
                return \Response::forge(
                    json_encode(array('ok' => false, 'error' => '削除に失敗しました。')),
                    500
                )->set_header('Content-Type', 'application/json; charset=utf-8');
            }
            \Session::set_flash('error', '削除に失敗しました。');
        }

        \Response::redirect($redirect_url);
        return;
    }

    /**
     * 講師・生徒 追加
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
     * ユーザー登録完了画面　特に必要ない
     */
    public function action_create_complete()
    {
        $this->template->title       = '登録完了';
        $this->template->style_sheet = 'admin.css';

        $this->template->content = View::forge('admin/user_create_complete');
    }

    /**
     * 講師・生徒を編集する画面を出す
     */
    public function action_user_list()
    {
        $this->template->title       = '講師・生徒を編集する';
        $this->template->style_sheet = 'admin.css';

        $grade_rows = \DB::select('id', 'grade_name')
            ->from('grades')
            ->order_by('id', 'asc')
            ->execute();
        $students_by_grade = array();
        foreach ($grade_rows as $grow) {
            $grade = (object) $grow;
            $gid   = (int) $grade->id;

            $student_rows = \DB::select('students.id', 'students.user_id', 'students.grade_id', 'users.last_name', 'users.first_name')
                ->from('students')
                ->join('users', 'INNER')
                ->on('students.user_id', '=', 'users.id')
                ->where('students.grade_id', $gid)
                ->order_by('users.last_name', 'asc')
                ->order_by('users.first_name', 'asc')
                ->execute();

            $students = array();
            foreach ($student_rows as $sr) {
                $stu            = new \stdClass();
                $stu->id        = (int) $sr['id'];
                $stu->user_id   = (int) $sr['user_id'];
                $stu->grade_id  = (int) $sr['grade_id'];
                $stu->user      = (object) array(
                    'id'         => (int) $sr['user_id'],
                    'last_name'  => (string) $sr['last_name'],
                    'first_name' => (string) $sr['first_name'],
                );
                $students[] = $stu;
            }

            $students_by_grade[] = array('grade' => $grade, 'students' => $students);
        }

        $teachers = array();
        foreach (\DB::select('id', 'last_name', 'first_name')
            ->from('users')
            ->where('role_id', 2)
            ->order_by('last_name', 'asc')
            ->order_by('first_name', 'asc')
            ->execute() as $tr) {
            $teachers[] = (object) $tr;
        }

        $this->template->content = View::forge('admin/user_list', array(
            'students_by_grade' => $students_by_grade,
            'teachers'          => $teachers,
        ));
    }

    /**
     * ユーザー編集画面、データ更新
     */
    public function action_edit_user($id)
    {
        $this->template->title       = 'ユーザー編集';
        $this->template->style_sheet = 'admin.css';

        $uid = (int) $id;
        $urow = \DB::select('id', 'role_id', 'username', 'password', 'first_name', 'last_name', 'created_at', 'updated_at')
            ->from('users')
            ->where('id', $uid)
            ->execute()
            ->current();
        $user = ($urow && isset($urow['id'])) ? (object) $urow : null;
        if (empty($user)) {
            \Session::set_flash('error', '指定されたユーザーが見つかりません。');
            \Response::redirect('admin/users/edit');
            return;
        }

        $is_student = ((int) $user->role_id === 3);
        $grades     = array();
        $subjects   = array();
        if ($is_student) {
            foreach (\DB::select('id', 'grade_name')
                ->from('grades')
                ->order_by('id', 'asc')
                ->execute() as $gr) {
                $grades[] = (object) $gr;
            }
            foreach (\DB::select('id', 'subject_name')
                ->from('subjects')
                ->order_by('id', 'asc')
                ->execute() as $sj) {
                $subjects[] = (object) $sj;
            }
        }

        $student = null;
        $subject_ids = array();
        $grade_id = '';

        if ($is_student) {
            $stu_row = \DB::select('id', 'user_id', 'grade_id')
                ->from('students')
                ->where('user_id', (int) $user->id)
                ->execute()
                ->current();
            $student = ($stu_row && isset($stu_row['id'])) ? (object) $stu_row : null;
            $grade_id = $student ? (string) $student->grade_id : '';

            foreach (\DB::select('subject_id')
                ->from('student_subjects')
                ->where('student_user_id', (int) $user->id)
                ->execute() as $rel) {
                $subject_ids[] = (int) $rel['subject_id'];
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

                    $now = date('Y-m-d H:i:s');
                    $user_set = array(
                        'last_name'  => $input['last_name'],
                        'first_name' => $input['first_name'],
                        'updated_at' => $now,
                    );
                    if ($raw_password !== '') {
                        // ログイン照合（password_verify）と整合させるため、必ず password_hash(PASSWORD_DEFAULT)
                        $user_set['password'] = password_hash($raw_password, PASSWORD_DEFAULT);
                    }
                    \DB::update('users')
                        ->set($user_set)
                        ->where('id', '=', (int) $user->id)
                        ->execute();

                    $user->last_name  = $input['last_name'];
                    $user->first_name = $input['first_name'];
                    if ($raw_password !== '') {
                        $user->password = $user_set['password'];
                    }

                    if ($is_student) {
                        if (empty($student)) {
                            \DB::insert('students')
                                ->set(array('user_id' => (int) $user->id, 'grade_id' => (int) $input['grade_id'], 'created_at' => $now, 'updated_at' => $now))
                                ->execute();
                        } else {
                            \DB::update('students')
                                ->set(array('grade_id' => (int) $input['grade_id'], 'updated_at' => $now))
                                ->where('user_id', '=', (int) $user->id)
                                ->execute();
                        }

                        \DB::delete('student_subjects')
                            ->where('student_user_id', '=', (int) $user->id)
                            ->execute();

                        foreach ($input['subject_ids'] as $sid) {
                            $sid = (int) $sid;
                            if ($sid <= 0) {
                                continue;
                            }
                            \DB::insert('student_subjects')
                                ->set(array('student_user_id' => (int) $user->id, 'subject_id' => $sid))
                                ->execute();
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
     * 【テスト用】指定ユーザーのパスワードを強制的に "testpass123" に変更する。→テスト用
     */
    public function action_force_test_password($id)
    {
        $uid = (int) $id;
        $urow = \DB::select('id')
            ->from('users')
            ->where('id', $uid)
            ->execute()
            ->current();
        if (empty($urow) || ! isset($urow['id'])) {
            \Session::set_flash('error', '指定されたユーザーが見つかりません。');
            \Response::redirect('admin/users/edit');
            return;
        }
        \DB::update('users')
            ->set(array('password' => password_hash('testpass123', PASSWORD_DEFAULT), 'updated_at' => date('Y-m-d H:i:s')))
            ->where('id', '=', $uid)
            ->execute();
        \Session::set_flash('success', 'このユーザーのパスワードを "testpass123" に強制変更しました。ログイン検証後に元に戻してください。');
        \Response::redirect('admin/edit_user/' . $uid);
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

        $uid = (int) $id;
        $urow = \DB::select('id', 'role_id', 'username', 'password', 'first_name', 'last_name')
            ->from('users')
            ->where('id', $uid)
            ->execute()
            ->current();
        $user = ($urow && isset($urow['id'])) ? (object) $urow : null;
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
                        $prow = \DB::select('id')
                            ->from('users')
                            ->where('id', (int) $parent_id)
                            ->execute()
                            ->current();
                        if ($prow && isset($prow['id'])) {
                            \DB::delete('users')
                                ->where('id', '=', (int) $parent_id)
                                ->execute();
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

            \DB::delete('users')
                ->where('id', '=', (int) $user->id)
                ->execute();

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
