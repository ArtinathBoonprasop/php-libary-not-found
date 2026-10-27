<?php
// ค่าตั้งของระบบ ใช้ร่วมกันทุกหน้า แก้ที่นี่ที่เดียว
const APP_NAME = 'ระบบยืม–คืนอุปกรณ์ห้องสมุด';
const LOAN_DAYS = 7; // ยืมได้กี่วัน
const FINE_PER_DAY = 5; // ค่าปรับต่อชิ้นต่อวัน (บาท)
const ALERT_PERCENT = 80; // ยืมออกไปเกินกี่ % ให้เตือน
 
date_default_timezone_set('Asia/Bangkok');
