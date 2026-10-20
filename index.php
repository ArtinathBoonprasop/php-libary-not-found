<?php
$team = 'ระบบยืม–คืนหนังสือห้องสมุด';
$topic = '(Library Borrowing System)';
$problem = 'ความยุ่งยากในการทำงาน , ความยุ่งยากในจัดการข้อมูลหนังสือ';
$members = ['นาย อาทินาถ บุญประสพ (Developer A)', 'นาย อำนวยชัย เพิ่มเจริญ (Developer B)'];
?>
<!DOCTYPE html>
<html lang="th">
<head><meta charset="UTF-8"><title><?php echo $topic; ?></title></head>
<body>
  <h1><?php echo $topic; ?></h1>
  <p>ทีม: <?php echo $team; ?></p>
  <p>ปัญหาที่แก้: <?php echo $problem; ?></p>
  <h2>สมาชิก</h2>
  <ul>
    <?php foreach ($members as $m): ?>
      <li><?php echo $m; ?></li>
    <?php endforeach; ?>
  </ul>
  <p>อัปเดตล่าสุด: <?php echo date('d/m/Y H:i'); ?></p>
</body>
</html>
