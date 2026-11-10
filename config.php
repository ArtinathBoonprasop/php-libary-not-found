<?php
date_default_timezone_set('Asia/Bangkok');

const APP_NAME = 'ระบบยืม–คืนหนังสือห้องสมุด';
const LOAN_DAYS = 7;
const FINE_PER_DAY = 5;
const ALERT_PERCENT = 80;

$teamName = 'library-not-found';
$projectTitle = APP_NAME;
$problemText = 'การจดบันทึกการยืม–คืนด้วยมือทำให้ตามหนังสือค้างส่งยาก และคำนวณค่าปรับผิดพลาดได้ง่าย';
$userName = 'อาจารย์นางสาวมัลลิกา เรืองเดช';
$today = date('Y-m-d');
$totalItems = 15;
$currentPage = basename($_SERVER['SCRIPT_NAME']);

// เมนูแบบเดิม (about.php และ server.php ที่ใช้เมนูด้านบนยังเรียกใช้อยู่)
$navItems = [
    ['file' => 'index.php', 'label' => 'หน้าแรก'],
    ['file' => 'about.php', 'label' => 'เกี่ยวกับระบบ'],
    ['file' => 'server.php', 'label' => 'เซิร์ฟเวอร์'],
];

// ไอคอน SVG (เส้นสไตล์เดียวกันทั้งชุด)
$icons = [
    'home' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
    'server' => '<rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>',
    'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'chart' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
    'bell' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
    'list' => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
    'book' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
    'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'menu' => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
];

// เมนูด้านข้างแบบจัดกลุ่ม (hash คือลิงก์ลัดไปส่วนต่างๆ ในหน้าแรก)
$navGroups = [
    [
        'title' => 'เมนูหลัก',
        'items' => [
            ['file' => 'index.php', 'label' => 'หน้าแรก', 'icon' => 'home', 'color' => '#7c5cff'],
            ['file' => 'about.php', 'label' => 'เกี่ยวกับระบบ', 'icon' => 'info', 'color' => '#0ea5e9'],
            ['file' => 'server.php', 'label' => 'เซิร์ฟเวอร์', 'icon' => 'server', 'color' => '#14b8a6'],
        ],
    ],
    [
        'title' => 'ทางลัดในหน้าแรก',
        'items' => [
            ['file' => 'index.php', 'hash' => '#team', 'label' => 'สมาชิกทีม', 'icon' => 'users', 'color' => '#ec4899'],
            ['file' => 'index.php', 'hash' => '#stats', 'label' => 'สถิติห้องสมุด', 'icon' => 'chart', 'color' => '#f59e0b'],
            ['file' => 'index.php', 'hash' => '#status', 'label' => 'สถานะสต็อก', 'icon' => 'bell', 'color' => '#ef4444', 'badge' => true],
            ['file' => 'index.php', 'hash' => '#loans', 'label' => 'รายการยืม–คืน', 'icon' => 'list', 'color' => '#8b5cf6'],
        ],
    ],
];

$members = [
    ['name' => 'นายอาทินาถ', 'code' => '034', 'role' => 'ผู้พัฒนาหน้าเว็บ (Frontend)'],
    ['name' => 'นายอำนวยชัย เพิ่มเจริญ', 'code' => '035', 'role' => 'ผู้พัฒนาตรรกะ PHP (Logic)'],
];

$goals = [
    'บันทึกและติดตามรายการยืม–คืนหนังสือได้ในที่เดียว',
    'คำนวณวันครบกำหนดและค่าปรับอัตโนมัติ ลดความผิดพลาด',
    'แจ้งเตือนเมื่อหนังสือถูกยืมเกินเกณฑ์ที่กำหนด',
];

$loans = [
    ['borrower' => 'สมชาย ใจดี', 'itemName' => 'พื้นฐานภาษา PHP', 'qty' => 2, 'borrowDate' => date('Y-m-d', strtotime('-12 days'))],
    ['borrower' => 'สมหญิง รักเรียน', 'itemName' => 'เครือข่ายคอมพิวเตอร์', 'qty' => 1, 'borrowDate' => date('Y-m-d', strtotime('-9 days'))],
    ['borrower' => 'ธนากร มีสุข', 'itemName' => 'ฐานข้อมูล MySQL', 'qty' => 3, 'borrowDate' => date('Y-m-d', strtotime('-5 days'))],
    ['borrower' => 'พิมพ์ชนก แสนดี', 'itemName' => 'HTML และ CSS', 'qty' => 1, 'borrowDate' => date('Y-m-d', strtotime('-3 days'))],
    ['borrower' => 'กิตติ ทองคำ', 'itemName' => 'JavaScript เบื้องต้น', 'qty' => 2, 'borrowDate' => date('Y-m-d', strtotime('-1 days'))],
    ['borrower' => 'ปริญญา ศรีสุข', 'itemName' => 'Git และ GitHub', 'qty' => 3, 'borrowDate' => date('Y-m-d', strtotime('-8 days'))],
];