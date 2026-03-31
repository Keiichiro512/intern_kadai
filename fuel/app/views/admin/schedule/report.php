<?php
$reports = isset($reports) ? $reports : array();
?>
<main class="admin-main schedule-report-page">
    <div class="schedule-report-page__inner">
        <h1 class="schedule-report-page__heading">授業報告</h1>

        <?php if (empty($reports)): ?>
            <p class="schedule-report-page__empty">表示できるレポートがありません。</p>
        <?php else: ?>
            <?php foreach ($reports as $report): ?>
                <article class="schedule-report-card">
                    <div class="schedule-report-grid2">
                        <div class="schedule-report-box">
                            <span class="schedule-report-box__label">教科</span>
                            <span class="schedule-report-box__value">—</span>
                        </div>
                        <div class="schedule-report-box">
                            <span class="schedule-report-box__label">単元</span>
                            <span class="schedule-report-box__value"><?php echo ($report->unit_name !== null && $report->unit_name !== '') ? e($report->unit_name) : '—'; ?></span>
                        </div>
                    </div>

                    <div class="schedule-report-grid2">
                        <div class="schedule-report-box">
                            <span class="schedule-report-box__label">宿題達成率</span>
                            <span class="schedule-report-box__value">
                                <?php
                                $ach = $report->homework_achievement_rate;
                                echo ($ach !== null && $ach !== '') ? e($report->homework_achievement_rate) . '%' : '—';
                                ?>
                            </span>
                        </div>
                        <div class="schedule-report-box">
                            <span class="schedule-report-box__label">宿題正答率</span>
                            <span class="schedule-report-box__value">
                                <?php
                                $acc = $report->homework_accuracy_rate;
                                echo ($acc !== null && $acc !== '') ? e($report->homework_accuracy_rate) . '%' : '—';
                                ?>
                            </span>
                        </div>
                    </div>

                    <section class="schedule-report-block">
                        <h2 class="schedule-report-block__title">授業報告</h2>
                        <div class="schedule-report-block__body schedule-report-block__body--text">
                            <?php
                            $lr = $report->lesson_report;
                            echo ($lr !== null && $lr !== '') ? nl2br(e($lr)) : '—';
                            ?>
                        </div>
                    </section>

                    <section class="schedule-report-block">
                        <h2 class="schedule-report-block__title">宿題</h2>
                        <div class="schedule-report-block__body schedule-report-block__body--list">
                            <?php
                            $nh = $report->next_homework;
                            if ($nh !== null && $nh !== '') {
                                foreach (preg_split("/\r\n|\n|\r/", $nh) as $line) {
                                    $line = trim($line);
                                    if ($line === '') {
                                        continue;
                                    }
                                    echo '<p class="schedule-report-list-line">・' . e($line) . '</p>';
                                }
                            } else {
                                echo '<p class="schedule-report-list-line">—</p>';
                            }
                            ?>
                        </div>
                    </section>

                    <section class="schedule-report-block">
                        <h2 class="schedule-report-block__title">親御さんから</h2>
                        <div class="schedule-report-block__body schedule-report-block__body--text">
                            <?php
                            $pm = $report->parent_message;
                            // nl2br()（newline to br）は、文字列の 改行文字を <br> や <br /> 付きの HTML に置き換える関数です。
                            //注意：エスケープしてから nl2br() する必要がある。
                            echo ($pm !== null && $pm !== '') ? nl2br(e($pm)) : '—';
                            ?>
                        </div>
                    </section>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

        <p class="schedule-report-page__back">
            <a href="<?php echo e(Uri::create('admin/schedule')); ?>">スケジュールへ戻る</a>
        </p>
    </div>
</main>
