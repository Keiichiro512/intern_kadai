<?php
$display_date = isset($display_date) ? $display_date : ''; 
$time_slots = isset($time_slots) ? $time_slots : array();
$lesson_slots = isset($lesson_slots) ? $lesson_slots : array();
?>

<div class="schedule-day">
  <?php if($display_date): ?>
    <h1><?php echo $display_date; ?></h1>
  <?php else: ?>
    <h1>日付が指定されていません</h1>
  <?php endif; ?>  
</div>

<div class="schedule-day__time-slots">
  <?php foreach ($time_slots as $time_slot): ?>
    <div class="schedule-day__time-slot">
      <h2><?php echo $time_slot->slot_name; ?></h2>
      <?php foreach ((isset($lesson_slots[$time_slot->id]) ? $lesson_slots[$time_slot->id] : array()) as $lesson_slot): ?>
        <a href="<?php echo Uri::create('admin/schedule/report', array(), array('schedule_id' => $lesson_slot['id'])); ?>" class="schedule-day__lesson-slot">
          <?php echo $lesson_slot['teacher']; ?>
          <?php echo $lesson_slot['student']; ?>
          <?php echo $lesson_slot['subject']; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>

