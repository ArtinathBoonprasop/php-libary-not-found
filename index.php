<?php
// ==============================
// ข้อมูลตัวอย่าง Dashboard
// ยังไม่เชื่อมฐานข้อมูล
// ==============================

$totalBooks = 100;
$borrowedBooks = 30;
$availableBooks = $totalBooks - $borrowedBooks;

$usedPercent = $totalBooks > 0
    ? round(($borrowedBooks / $totalBooks) * 100)
    : 0;

// รายการยืมหนังสือตัวอย่าง 5 รายการ
$recentBorrows = [
    [
        'member' => 'สมชาย ใจดี',
        'book' => 'การเขียนโปรแกรม PHP',
        'quantity' => 2,
        'due_date' => '30/10/2026',
        'fine' => 0
    ],
    [
        'member' => 'สมหญิง รักเรียน',
        'book' => 'พื้นฐานฐานข้อมูล MySQL',
        'quantity' => 1,
        'due_date' => '29/10/2026',
        'fine' => 0
    ],
    [
        'member' => 'อนันต์ นักศึกษา',
        'book' => 'ระบบเครือข่ายคอมพิวเตอร์',
        'quantity' => 1,
        'due_date' => '25/10/2026',
        'fine' => 20
    ],
    [
        'member' => 'กิตติพงษ์ ขยันเรียน',
        'book' => 'การรักษาความปลอดภัยทางไซเบอร์',
        'quantity' => 2,
        'due_date' => '28/10/2026',
        'fine' => 0
    ],
    [
        'member' => 'นภัสสร เทคโนโลยี',
        'book' => 'การพัฒนาเว็บไซต์ด้วย HTML และ CSS',
        'quantity' => 1,
        'due_date' => '02/11/2026',
        'fine' => 0
    ]
];

// ตรวจสอบรายการที่เลยกำหนดคืน
$today = new DateTime('today');
$overdueCount = 0;

foreach ($recentBorrows as $borrow) {
    $dueDate = DateTime::createFromFormat(
        'd/m/Y',
        $borrow['due_date']
    );

    if ($dueDate && $dueDate < $today) {
        $overdueCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Library Borrowing System</title>

    <!-- เรียกใช้ไฟล์ CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dashboard">

    <!-- ส่วนหัว -->
    <header class="header">
        <div>
            <h1>Library Dashboard</h1>
            <p>ระบบจัดการยืม–คืนหนังสือห้องสมุด</p>
        </div>

        <div class="date">
            วันที่ <?php echo date('d/m/Y'); ?>
        </div>
    </header>

    <!-- การ์ดสรุปข้อมูล 4 ช่อง -->
    <section class="stats">

        <div class="stat-card">
            <p class="label">หนังสือทั้งหมด</p>
            <h2 class="blue">
                <?php echo number_format($totalBooks); ?> เล่ม
            </h2>
            <p class="description">Total Books</p>
        </div>

        <div class="stat-card">
            <p class="label">ถูกยืมไป</p>
            <h2 class="orange">
                <?php echo number_format($borrowedBooks); ?> เล่ม
            </h2>
            <p class="description">Borrowed Books</p>
        </div>

        <div class="stat-card">
            <p class="label">พร้อมให้ยืม</p>
            <h2 class="green">
                <?php echo number_format($availableBooks); ?> เล่ม
            </h2>
            <p class="description">Available Books</p>
        </div>

        <div class="stat-card">
            <p class="label">เปอร์เซ็นต์การยืม</p>
            <h2 class="purple">
                <?php echo $usedPercent; ?>%
            </h2>
            <p class="description">Used Percent</p>
        </div>

    </section>

    <!-- ตารางรายการยืมหนังสือล่าสุด -->
    <section class="panel">
        <h3>รายการยืมหนังสือล่าสุด</h3>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ชื่อสมาชิก</th>
                        <th>ชื่อหนังสือ</th>
                        <th>จำนวน</th>
                        <th>วันกำหนดคืน</th>
                        <th>ค่าปรับ</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($recentBorrows as $borrow): ?>
                        <?php
                        $dueDate = DateTime::createFromFormat(
                            'd/m/Y',
                            $borrow['due_date']
                        );

                        $isOverdue = $dueDate && $dueDate < $today;
                        ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($borrow['member']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($borrow['book']); ?>
                            </td>

                            <td>
                                <?php echo (int)$borrow['quantity']; ?> เล่ม
                            </td>

                            <td>
                                <?php echo htmlspecialchars($borrow['due_date']); ?>
                            </td>

                            <td>
                                <?php echo number_format($borrow['fine']); ?> บาท
                            </td>

                            <td>
                                <?php if ($isOverdue): ?>
                                    <span class="status overdue">
                                        เลยกำหนดคืน
                                    </span>
                                <?php else: ?>
                                    <span class="status normal">
                                        กำลังยืม
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- กล่องแจ้งเตือน -->
    <section class="alert">
        <h3>⚠ ข้อความแจ้งเตือน</h3>

        <?php if ($overdueCount > 0): ?>
            <p>
                มีรายการยืมที่เลยกำหนดคืน
                <?php echo $overdueCount; ?> รายการ
                กรุณาตรวจสอบการคืนหนังสือ
            </p>
        <?php else: ?>
            <p>ไม่มีรายการยืมที่เลยกำหนดคืน</p>
        <?php endif; ?>
    </section>

    <!-- ส่วนท้าย -->
    <footer class="footer">
        Library Borrowing System
        &copy; <?php echo date('Y'); ?>
    </footer>

</div>

</body>
</html>
