<?php
require_once 'config.php';

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function thDate($d) {
    $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $t = strtotime($d);
    return date('j', $t) . ' ' . $m[(int) date('n', $t)] . ' ' . (date('Y', $t) + 543);
}

$borrowedBooks = 0;
$dueSoonCount = 0;
$totalFine = 0;
$lateCount = 0;
$loanRows = [];

foreach ($loans as $loan) {
    $dueDate = date('Y-m-d', strtotime($loan['borrowDate'] . ' +' . LOAN_DAYS . ' days'));
    $daysLate = (int) floor((strtotime($today) - strtotime($dueDate)) / 86400);
    $isLate = $daysLate > 0;
    $daysLeft = -$daysLate;
    $dueSoon = !$isLate && $daysLeft <= 2;
    $status = $isLate ? 'late' : ($dueSoon ? 'soon' : 'ok');
    $dueSoonCount += $dueSoon ? 1 : 0;
    $fine = $isLate ? $daysLate * FINE_PER_DAY * $loan['qty'] : 0;

    $borrowedBooks += $loan['qty'];
    $totalFine += $fine;
    $lateCount += $isLate ? 1 : 0;

    $loanRows[] = [
        'borrower' => $loan['borrower'],
        'itemName' => $loan['itemName'],
        'qty' => $loan['qty'],
        'borrowDate' => $loan['borrowDate'],
        'dueDate' => $dueDate,
        'daysLate' => $daysLate,
        'isLate' => $isLate,
        'daysLeft' => $daysLeft,
        'dueSoon' => $dueSoon,
        'status' => $status,
        'fine' => $fine,
    ];
}

// เรียงรายการที่เสี่ยงที่สุดขึ้นก่อน (เกินกำหนดนานสุด → ใกล้ครบกำหนด → ปกติ)
usort($loanRows, function ($a, $b) {
    return $a['daysLeft'] <=> $b['daysLeft'];
});
$okCount = count($loanRows) - $lateCount - $dueSoonCount;

$topLoans = $loanRows;
usort($topLoans, function ($a, $b) {
    return $b['qty'] <=> $a['qty'];
});
$topLoans = array_slice($topLoans, 0, 4);
$maxQty = count($topLoans) > 0 && $topLoans[0]['qty'] > 0 ? $topLoans[0]['qty'] : 1;

$worst = null;
foreach ($loanRows as $row) {
    if ($row['isLate'] && ($worst === null || $row['daysLate'] > $worst['daysLate'])) {
        $worst = $row;
    }
}

$availableBooks = $totalItems - $borrowedBooks;
$usedPercent = $totalItems > 0 ? round($borrowedBooks / $totalItems * 100, 1) : 0;
$stockHigh = $usedPercent >= ALERT_PERCENT;
$needAlert = $stockHigh || $lateCount > 0;
$statusText = $needAlert ? 'ระบบแจ้งเตือนวิกฤต' : 'ระบบปกติเสถียร';

$ringLength = 339.29;
$ringOffset = round($ringLength * (1 - min($usedPercent, 100) / 100), 2);

$stats = [
    ['label' => 'หนังสือทั้งหมด', 'value' => $totalItems, 'unit' => 'เล่ม', 'color' => '#38bdf8'],
    ['label' => 'ถูกยืมอยู่', 'value' => $borrowedBooks, 'unit' => 'เล่ม', 'color' => '#a78bfa'],
    ['label' => 'คงเหลือ', 'value' => $availableBooks, 'unit' => 'เล่ม', 'color' => '#34d399'],
    ['label' => 'ค่าปรับรวม', 'value' => $totalFine, 'unit' => 'บาท', 'color' => '#fb7185'],
    ['label' => 'ใกล้ครบกำหนด', 'value' => $dueSoonCount, 'unit' => 'รายการ', 'color' => '#fbbf24'],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $projectTitle; ?> | <?php echo $teamName; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script>document.documentElement.classList.add('js');</script>
<style>
@property --angle{syntax:'<angle>';initial-value:0deg;inherits:false}
@view-transition{navigation:auto}

:root{
  --bg:#020308;--text:#f8fafc;--muted:#94a3b8;
  --c1:#8b5cf6;--c2:#06b6d4;--c3:#f43f5e;--ok:#10b981;--warn:#f43f5e;
  --ease:cubic-bezier(0.16, 1, 0.3, 1);--mx:50vw;--my:30vh;--side:280px;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
[id]{scroll-margin-top:32px}
body{font-family:'Prompt','Segoe UI',Tahoma,sans-serif;background:var(--bg);color:var(--text);
     min-height:100vh;overflow-x:hidden;line-height:1.65;animation:pageIn 1s var(--ease) both}
@keyframes pageIn{from{opacity:0;filter:blur(15px)}to{opacity:1;filter:none}}

/* Ultimate Cyberpunk Background Elements */
.cyber-space{position:fixed;inset:0;z-index:-4;pointer-events:none;background:radial-gradient(circle at 50% 0%, #1e1b4b 0%, #020308 75%)}
.aurora{position:fixed;inset:0;z-index:-3;overflow:hidden;pointer-events:none}
.aurora i{position:absolute;width:55vmax;height:55vmax;border-radius:50%;filter:blur(130px);
          opacity:.4;animation:drift 18s ease-in-out infinite alternate;will-change:transform}
.aurora i:nth-child(1){background:#7c3aed;top:-10%;left:-10%}
.aurora i:nth-child(2){background:#0891b2;bottom:-15%;right:-10%;animation-duration:22s;animation-delay:-4s}
.aurora i:nth-child(3){background:#db2777;top:35%;left:40%;opacity:.25;animation-duration:28s;animation-delay:-8s}
@keyframes drift{to{transform:translate3d(12vmax, 15vmax, 0) scale(1.35) rotate(50deg)}}

.grid-bg{position:fixed;inset:0;z-index:-2;pointer-events:none;
  background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),
                   linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);
  background-size:50px 50px;
  -webkit-mask-image:radial-gradient(circle 600px at var(--mx) var(--my),#000,rgba(0,0,0,.08));
  mask-image:radial-gradient(circle 600px at var(--mx) var(--my),#000,rgba(0,0,0,.08))}

.spot{position:fixed;inset:0;z-index:-1;pointer-events:none;
  background:radial-gradient(700px circle at var(--mx) var(--my),rgba(139,92,246,.18),transparent 70%)}

/* Neon Glass Sidebar */
.side{position:fixed;top:0;left:0;bottom:0;width:var(--side);z-index:60;display:flex;flex-direction:column;gap:22px;
  padding:24px 16px;background:rgba(3,4,12,0.82);border-right:1px solid rgba(255,255,255,.08);
  backdrop-filter:blur(28px) saturate(200%);-webkit-backdrop-filter:blur(28px) saturate(200%);
  overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.15) transparent;
  box-shadow:15px 0 50px rgba(0,0,0,.5);animation:slideIn 1.1s var(--ease) both}
@keyframes slideIn{from{opacity:0;transform:translateX(-35px)}to{opacity:1;transform:none}}

.brand{display:flex;align-items:center;gap:14px;padding:8px;text-decoration:none;color:var(--text)}
.logo{width:46px;height:46px;flex:none;display:grid;place-items:center;border-radius:14px;color:#fff;
  background:linear-gradient(135deg,var(--c1),var(--c2));box-shadow:0 0 30px rgba(139,92,246,.7)}
.logo svg{width:24px;height:24px}
.brand strong{display:block;font-size:16px;line-height:1.2;letter-spacing:-0.01em}
.brand small{display:block;font-size:12px;color:var(--muted)}

.side-nav{position:relative;display:flex;flex-direction:column;gap:18px}
.side-ind{position:absolute;left:0;right:0;top:0;height:0;border-radius:14px;opacity:0;z-index:0;pointer-events:none;
  background:linear-gradient(135deg,rgba(139,92,246,.5),rgba(6,182,212,.3));
  box-shadow:0 0 30px rgba(139,92,246,.4),inset 0 0 0 1px rgba(255,255,255,.2)}
.side-ind.ready{opacity:1;transition:transform .35s var(--ease),height .35s var(--ease),opacity .2s}
.side-group{display:flex;flex-direction:column;gap:4px}
.side-title{padding:0 12px 8px;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);font-weight:600}
.side-nav a{position:relative;z-index:1;display:flex;align-items:center;gap:14px;padding:10px 12px;border-radius:14px;
  color:#94a3b8;text-decoration:none;font-size:14px;font-weight:500;transition:color .2s}
.side-nav a:hover,.side-nav a.on{color:#fff}
.side-nav a.active{color:#fff;background:rgba(139,92,246,.3)}
.js .side-nav a.active{background:none}
.js .side-nav a.active:not(.on):not(:hover){color:#94a3b8}

.ico{width:36px;height:36px;flex:none;display:grid;place-items:center;border-radius:12px;color:#fff;
  background:linear-gradient(135deg,var(--ic),color-mix(in srgb,var(--ic) 50%,#000));
  box-shadow:0 8px 22px -8px var(--ic);transition:transform .3s var(--ease)}
.side-nav a:hover .ico{transform:scale(1.12) rotate(-6deg)}
.ico svg{width:18px;height:18px}
.lbl{flex:1}
.badge{min-width:22px;height:22px;padding:0 6px;border-radius:99px;display:grid;place-items:center;
  font-size:11px;font-weight:700;color:#fff;background:var(--warn);animation:pulseBadge 2s infinite}
@keyframes pulseBadge{0%{box-shadow:0 0 0 0 rgba(244,63,94,.8)}70%{box-shadow:0 0 0 12px transparent}100%{box-shadow:0 0 0 0 transparent}}

.rules{margin-top:auto;padding:16px;border-radius:20px;background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.07)}
.rules .side-title{padding-left:0}
.rule{display:flex;justify-content:space-between;gap:10px;padding:6px 0;font-size:13px;color:var(--muted)}
.rule b{color:var(--c2);font-weight:600}
.side-user{display:flex;align-items:center;gap:14px;padding:12px;border-radius:18px;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.07)}
.side-user .ico{border-radius:50%}
.side-user strong{display:block;font-size:13px;line-height:1.4}
.side-user small{display:block;font-size:11px;color:var(--muted)}

.menu-btn{display:none;position:fixed;top:16px;left:16px;z-index:70;width:46px;height:46px;place-items:center;
  border-radius:14px;color:#fff;cursor:pointer;background:rgba(15,23,42,.85);border:1px solid rgba(255,255,255,.12);
  backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}
.menu-btn svg{width:22px;height:22px}
.scrim{position:fixed;inset:0;z-index:55;background:rgba(0,0,0,0.65);opacity:0;pointer-events:none;transition:opacity .4s}

/* Layout Main Content */
.content{margin-left:var(--side)}
main{max-width:1200px;margin:0 auto;padding:50px 32px 90px}
section{margin-bottom:28px}
.muted{color:var(--muted)}.small{font-size:13px}
h2{font-size:22px;font-weight:600;margin-bottom:12px;letter-spacing:-0.01em}

/* Hero Section */
.hero-top{display:grid;grid-template-columns:1fr minmax(280px,340px);gap:24px;align-items:center}
.eyebrow{display:inline-flex;align-items:center;gap:10px;padding:6px 16px;border-radius:99px;
  border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);font-size:13px;color:var(--c2);font-weight:500}
.dot{width:8px;height:8px;border-radius:50%;background:var(--ok);animation:ping 2s infinite}
@keyframes ping{0%{box-shadow:0 0 0 0 rgba(16,185,129,.8)}70%{box-shadow:0 0 0 12px transparent}100%{box-shadow:0 0 0 0 transparent}}

.title{font-size:clamp(36px,5vw,60px);line-height:1.15;font-weight:800;margin:16px 0 12px;
  letter-spacing:-0.02em;background:linear-gradient(120deg,#fff 20%,var(--c2) 55%,var(--c1) 85%);
  -webkit-background-clip:text;background-clip:text;color:transparent;
  animation:wipe 1.2s var(--ease) both}
@keyframes wipe{from{clip-path:inset(0 100% 0 0)}to{clip-path:inset(0 0 0 0)}}

.lead{font-size:16px;max-width:680px;color:#cbd5e1;font-weight:300}
.members{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;margin-top:32px}

/* Bento Card Style 4.0 - Ultimate Cyber Glass */
.card{position:relative;padding:26px;border-radius:26px;background:rgba(255,255,255,.03);
  border:1px solid rgba(255,255,255,.07);backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px);
  box-shadow:0 25px 60px -20px rgba(0,0,0,.75), inset 0 1px 0 rgba(255,255,255,.1);
  isolation:isolate;transition:transform .35s var(--ease),border-color .35s,box-shadow .35s}
.card::before{content:'';position:absolute;inset:0;border-radius:inherit;padding:1px;pointer-events:none;
  background:conic-gradient(from var(--angle),rgba(255,255,255,.08) 0 70%,var(--c2),var(--c1),var(--c3),rgba(255,255,255,.08));
  -webkit-mask:linear-gradient(#000 0 0) content-box,linear-gradient(#000 0 0);
  -webkit-mask-composite:xor;mask-composite:exclude;animation:spin 7s linear infinite}
@keyframes spin{to{--angle:360deg}}
.card::after{content:'';position:absolute;inset:0;border-radius:inherit;pointer-events:none;z-index:-1;
  background:radial-gradient(380px circle at var(--x,50%) var(--y,0%),rgba(139,92,246,.22),transparent 65%);
  opacity:0;transition:opacity .3s}
.card:hover::after{opacity:1}
.card:hover{border-color:rgba(139,92,246,.3);box-shadow:0 30px 70px -15px rgba(139,92,246,.25), inset 0 1px 0 rgba(255,255,255,.2)}
.card.tilt-on{transition:transform .12s linear}

/* Scroll Reveal */
.js .reveal{opacity:0;translate:0 30px;filter:blur(12px);
  transition:opacity .9s var(--ease) calc(var(--i,0)*80ms),
             translate .9s var(--ease) calc(var(--i,0)*80ms),
             filter .9s var(--ease) calc(var(--i,0)*80ms),
             transform .12s linear}
.js .reveal.in{opacity:1;translate:0 0;filter:none}

/* Member & Stats */
.member{display:flex;gap:16px;align-items:center}
.avatar{width:56px;height:56px;border-radius:16px;display:grid;place-items:center;font-weight:700;font-size:18px;
  background:linear-gradient(135deg,var(--c1),var(--c2));box-shadow:0 0 30px rgba(139,92,246,.5)}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:18px;margin-top:36px}
.num{font-size:clamp(36px,4vw,48px);font-weight:800;margin-top:6px;letter-spacing:-0.02em;
  background:linear-gradient(90deg,#fff,var(--c2));-webkit-background-clip:text;background-clip:text;color:transparent}
.num em{font-style:normal;font-size:14px;color:var(--muted);-webkit-text-fill-color:var(--muted);margin-left:6px;font-weight:400}

/* Live Card & Ticker */
.live{display:flex;flex-direction:column;gap:8px}
.live-clock{font-size:44px;font-weight:800;line-height:1.1;font-variant-numeric:tabular-nums;
  background:linear-gradient(90deg,#fff,var(--c2));-webkit-background-clip:text;background-clip:text;color:transparent;
  text-shadow:0 0 35px rgba(6,182,212,.5)}
.live-sep{height:1px;background:rgba(255,255,255,.08);margin:8px 0}

.ticker{overflow:hidden;margin:28px 0;
  -webkit-mask-image:linear-gradient(90deg,transparent,#000 10%,#000 90%,transparent);
  mask-image:linear-gradient(90deg,transparent,#000 10%,#000 90%,transparent)}
.ticker-track{display:flex;width:max-content;animation:marquee 40s linear infinite}
.ticker:hover .ticker-track{animation-play-state:paused}
@keyframes marquee{to{transform:translateX(-50%)}}
.chip{display:inline-flex;align-items:center;gap:10px;margin-right:14px;padding:10px 18px;border-radius:99px;
  background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);font-size:13px;white-space:nowrap;color:#cbd5e1}
.chip i{width:7px;height:7px;border-radius:50%}
.chip .good{background:var(--ok);box-shadow:0 0 10px var(--ok)}
.chip .bad{background:var(--warn);box-shadow:0 0 10px var(--warn)}

/* Gauge & Status Grid */
.gauge-row{display:grid;grid-template-columns:minmax(280px,340px) 1fr;gap:18px}
.gauge{display:grid;place-items:center;aspect-ratio:1}
.ring{width:100%;max-width:260px;transform:rotate(-90deg);filter:drop-shadow(0 0 18px rgba(139,92,246,.6))}
.ring circle{fill:none;stroke-width:9;stroke-linecap:round}
.ring .track{stroke:rgba(255,255,255,.05)}
.ring .bar{stroke:url(#g);stroke-dasharray:339.29;animation:ring 2s var(--ease) .3s both}
@keyframes ring{from{stroke-dashoffset:339.29}}
.gauge-text{position:absolute;text-align:center;font-size:42px;font-weight:800;letter-spacing:-0.02em}
.gauge-text small{display:block;font-size:13px;color:var(--muted);font-weight:400}
.status{display:flex;flex-direction:column;justify-content:center;gap:12px}
.status.danger{box-shadow:0 0 60px -10px rgba(244,63,94,.3);animation:breathe 3s ease-in-out infinite}
@keyframes breathe{50%{box-shadow:0 0 90px -5px rgba(244,63,94,.55)}}

/* Mini Progress Bars */
.mini{display:grid;gap:10px;margin-top:14px}
.mini-row{display:grid;grid-template-columns:160px 1fr 28px;gap:14px;align-items:center;font-size:13px}
.mini-row span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#cbd5e1}
.mini-bar{height:8px;border-radius:99px;background:rgba(255,255,255,.06);overflow:hidden}
.mini-bar i{display:block;height:100%;width:var(--w);border-radius:inherit;transform-origin:left;
  background:linear-gradient(90deg,var(--c2),var(--c1),var(--c3));
  box-shadow:0 0 12px rgba(139,92,246,.5);
  animation:fill 1.2s var(--ease) calc(.4s + var(--d)*.1s) both}
@keyframes fill{from{transform:scaleX(0)}}

/* Pills & Table */
.pill{display:inline-flex;align-items:center;gap:8px;width:max-content;padding:5px 14px;border-radius:99px;font-size:12px;font-weight:600}
.pill.ok{background:rgba(16,185,129,.12);color:var(--ok);border:1px solid rgba(16,185,129,.25)}
.pill.late{background:rgba(244,63,94,.12);color:var(--warn);border:1px solid rgba(244,63,94,.25)}
.pulse{width:6px;height:6px;border-radius:50%;background:var(--warn);box-shadow:0 0 8px var(--warn);animation:ping2 1.5s infinite}
@keyframes ping2{0%{box-shadow:0 0 0 0 rgba(244,63,94,.9)}70%{box-shadow:0 0 0 8px transparent}100%{box-shadow:0 0 0 0 transparent}}

.tbl{overflow-x:auto;margin-top:10px}
table{width:100%;border-collapse:collapse;min-width:700px}
th,td{text-align:left;padding:16px 12px;border-bottom:1px solid rgba(255,255,255,.06);font-size:14px}
th{color:var(--muted);font-weight:500;font-size:13px}
.row td{transition:background .2s}
.row:hover td{background:rgba(255,255,255,.04)}
.js .table-card.in .row{animation:rowIn .7s var(--ease) calc(var(--i)*70ms + .2s) both}
@keyframes rowIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}

footer{text-align:center;color:var(--muted);font-size:13px;margin-top:48px;font-weight:300}

@media(max-width:1100px){.hero-top{grid-template-columns:1fr}}
@media(max-width:980px){
  .content{margin-left:0}
  .side{transform:translateX(-105%);animation:none;transition:transform .5s var(--ease);box-shadow:20px 0 50px rgba(0,0,0,.7)}
  body.side-open .side{transform:none}
  body.side-open .scrim{opacity:1;pointer-events:auto}
  .menu-btn{display:grid}
  main{padding-top:80px}
}
@media(max-width:760px){.gauge-row{grid-template-columns:1fr}.mini-row{grid-template-columns:110px 1fr 28px}}
@media(prefers-reduced-motion:reduce){
  *,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}
}

/* ---- Improvements ---- */
.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
:focus-visible{outline:2px solid var(--c2);outline-offset:3px;border-radius:8px}
.card::before{animation-play-state:paused}
.card:hover::before,.card:focus-within::before{animation-play-state:running}
.tbl-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.tbl-head h2{margin:0}
.toolbar{display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin:16px 0 8px}
.search{flex:1 1 240px}
.search input{width:100%;padding:11px 16px;border-radius:14px;font:inherit;font-size:14px;color:var(--text);
  background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1)}
.search input::placeholder{color:var(--muted)}
.filters{display:flex;gap:8px;flex-wrap:wrap}
.btn,.filters button{font:inherit;font-size:13px;font-weight:500;cursor:pointer;color:#cbd5e1;padding:9px 16px;border-radius:99px;
  background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);transition:background .2s,color .2s,border-color .2s}
.btn:hover,.filters button:hover{background:rgba(255,255,255,.09);color:#fff}
.filters button[aria-pressed="true"]{color:#fff;background:rgba(139,92,246,.35);border-color:rgba(139,92,246,.7)}
.filters b{margin-left:6px;opacity:.7;font-weight:600}
.pill.soon{background:rgba(251,191,36,.12);color:#fbbf24;border:1px solid rgba(251,191,36,.28)}
.row.is-late td:first-child{box-shadow:inset 3px 0 0 var(--warn)}
td.fine-cell b{font-variant-numeric:tabular-nums}
td.fine-cell.has-fine b{color:var(--warn)}
tfoot td{font-weight:600;border-bottom:0;padding-top:18px}
.empty td{text-align:center;color:var(--muted);padding:36px 12px}
@media print{.side,.menu-btn,.scrim,.aurora,.grid-bg,.spot,.cyber-space,.toolbar,.ticker{display:none!important}
  .content{margin:0}body,.card{background:#fff!important;color:#000!important;box-shadow:none!important}
  .muted,th{color:#444!important}.num,.title{-webkit-text-fill-color:#000;color:#000}}
</style>
</head>
<body>
<div class="cyber-space"></div>
<div class="aurora"><i></i><i></i><i></i></div>
<div class="grid-bg"></div>
<div class="spot"></div>

<button class="menu-btn" id="menuBtn" aria-label="เปิดเมนู">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $icons['menu']; ?></svg>
</button>
<div class="scrim" id="scrim"></div>

<aside class="side">
  <a class="brand" href="index.php">
    <span class="logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $icons['book']; ?></svg></span>
    <span>
      <strong><?php echo $teamName; ?></strong>
      <small>ระบบห้องสมุดอัจฉริยะ</small>
    </span>
  </a>

  <nav class="side-nav">
    <span class="side-ind"></span>
    <?php foreach ($navGroups as $group) { ?>
      <div class="side-group">
        <span class="side-title"><?php echo $group['title']; ?></span>
        <?php foreach ($group['items'] as $item) {
            $hash = $item['hash'] ?? '';
            $isActive = $item['file'] === $currentPage && $hash === '';
            $isSpy = $hash !== '' && $item['file'] === $currentPage;
        ?>
          <a href="<?php echo $item['file'] . $hash; ?>"<?php echo $isActive ? ' class="active"' : ''; ?><?php echo $isSpy ? ' data-spy="' . substr($hash, 1) . '"' : ''; ?> style="--ic:<?php echo $item['color']; ?>">
            <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $icons[$item['icon']]; ?></svg></span>
            <span class="lbl"><?php echo $item['label']; ?></span>
            <?php if (!empty($item['badge']) && $lateCount > 0) { ?>
              <span class="badge"><?php echo $lateCount; ?></span>
            <?php } ?>
          </a>
        <?php } ?>
      </div>
    <?php } ?>
  </nav>

  <div class="rules">
    <span class="side-title">กฎข้อบังคับระบบ</span>
    <div class="rule"><span>ระยะยืมสูงสุด</span><b><?php echo LOAN_DAYS; ?> วัน</b></div>
    <div class="rule"><span>อัตราค่าปรับ</span><b><?php echo FINE_PER_DAY; ?> ฿/วัน</b></div>
    <div class="rule"><span>เกณฑ์เตือนภัย</span><b><?php echo ALERT_PERCENT; ?>%</b></div>
  </div>

  <div class="side-user">
    <span class="ico" style="--ic:#8b5cf6"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $icons['user']; ?></svg></span>
    <div>
      <strong><?php echo $userName; ?></strong>
      <small>ผู้ใช้ระบบหลัก</small>
    </div>
  </div>
</aside>

<div class="content">
<main>
  <section class="hero">
    <div class="hero-top">
      <div class="hero-main">
        <span class="eyebrow reveal" style="--i:0"><span class="dot"></span> ทีม <?php echo $teamName; ?> · ระบบออนไลน์</span>
        <h1 class="title"><?php echo $projectTitle; ?></h1>
        <p class="lead reveal" style="--i:2"><?php echo $problemText; ?></p>
        <p class="muted reveal" style="--i:3; margin-top:6px;">ผู้ใช้ระบบ: <?php echo $userName; ?></p>
      </div>

      <aside class="card live reveal" data-tilt style="--i:3" data-server-ms="<?php echo time() * 1000; ?>">
        <span class="muted small">เวลาประเทศไทย (Real-time)</span>
        <div class="live-clock" id="liveClock">--:--:--</div>
        <div class="muted small"><?php echo $today; ?></div>
        <?php if ($worst !== null) { ?>
          <div class="live-sep"></div>
          <span class="muted small">ค้างส่งนานที่สุด</span>
          <strong><?php echo htmlspecialchars($worst['borrower']); ?></strong>
          <div class="muted small"><?php echo htmlspecialchars($worst['itemName']); ?> · เกินกำหนด <?php echo $worst['daysLate']; ?> วัน</div>
        <?php } ?>
      </aside>
    </div>

    <div class="members" id="team">
      <?php foreach ($members as $index => $member) { ?>
        <div class="card member reveal" data-tilt style="--i:<?php echo $index + 3; ?>">
          <div class="avatar"><?php echo $member['code']; ?></div>
          <div>
            <strong><?php echo $member['name']; ?></strong>
            <div class="muted small"><?php echo $member['role']; ?></div>
          </div>
        </div>
      <?php } ?>
    </div>
  </section>

  <section class="stats" id="stats">
    <?php foreach ($stats as $index => $stat) { ?>
      <div class="card stat reveal" data-tilt style="--i:<?php echo $index; ?>">
        <span class="muted small"><?php echo $stat['label']; ?></span>
        <div class="num"><span data-count="<?php echo $stat['value']; ?>" data-decimals="<?php echo $stat['unit'] === 'บาท' ? 2 : 0; ?>">0</span><em><?php echo $stat['unit']; ?></em></div>
      </div>
    <?php } ?>
  </section>

  <section class="ticker reveal">
    <div class="ticker-track">
      <?php for ($round = 0; $round < 2; $round++) { ?>
        <?php foreach ($loanRows as $row) { ?>
          <span class="chip"><i class="<?php echo $row['isLate'] ? 'bad' : 'good'; ?>"></i><?php echo htmlspecialchars($row['borrower']); ?> ยืม <?php echo htmlspecialchars($row['itemName']); ?> (<?php echo $row['qty']; ?> เล่ม)</span>
        <?php } ?>
      <?php } ?>
    </div>
  </section>

  <section class="gauge-row" id="status">
    <div class="card gauge reveal" data-tilt>
      <svg viewBox="0 0 120 120" class="ring">
        <defs>
          <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#06b6d4"/>
            <stop offset="0.5" stop-color="#8b5cf6"/>
            <stop offset="1" stop-color="#f43f5e"/>
          </linearGradient>
        </defs>
        <circle class="track" cx="60" cy="60" r="54"/>
        <circle class="bar" cx="60" cy="60" r="54" style="stroke-dashoffset:<?php echo $ringOffset; ?>"/>
      </svg>
      <div class="gauge-text"><span data-count="<?php echo $usedPercent; ?>" data-decimals="1">0</span>%<small>อัตราการยืม</small></div>
    </div>

    <div class="card status reveal<?php echo $needAlert ? ' danger' : ''; ?>" style="--i:2">
      <span class="pill <?php echo $needAlert ? 'late' : 'ok'; ?>"><?php echo $statusText; ?></span>
      <h2><?php echo $needAlert ? ($stockHigh ? 'แจ้งเตือน: สต็อกหนังสือวิกฤต' : 'แจ้งเตือน: มีรายการเกินกำหนดคืน') : 'สถานะสต็อกหนังสือปกติ'; ?></h2>
      <p class="muted">
        <?php echo $needAlert
            ? ($stockHigh
                ? 'ยอดถูกยืมพุ่งสูงถึง ' . $usedPercent . '% ทะลุกรอบความปลอดภัย ' . ALERT_PERCENT . '% กรุณาติดตามหนังสือคืนด่วน'
                : 'มีหนังสือเกินกำหนดคืน ' . $lateCount . ' รายการ กรุณาติดตามและแจ้งค่าปรับให้ผู้ยืม')
            : 'อัตราการใช้งานอยู่ที่ ' . $usedPercent . '% ยังต่ำกว่าเกณฑ์แจ้งเตือน ' . ALERT_PERCENT . '% ระบบบริหารจัดการราบรื่น'; ?>
      </p>
      <p class="muted small">พบรายการเกินกำหนดชำระ <?php echo $lateCount; ?> รายการ จากทั้งหมด <?php echo count($loanRows); ?> รายการ</p>

      <div class="mini">
        <span class="muted small">หนังสือยอดฮิต (ถูกยืมสูงสุด)</span>
        <?php foreach ($topLoans as $index => $top) { ?>
          <div class="mini-row">
            <span><?php echo htmlspecialchars($top['itemName']); ?></span>
            <div class="mini-bar"><i style="--w:<?php echo round($top['qty'] / $maxQty * 100); ?>%;--d:<?php echo $index; ?>"></i></div>
            <b><?php echo $top['qty']; ?></b>
          </div>
        <?php } ?>
      </div>
    </div>
  </section>

  <section class="card table-card reveal" id="loans">
    <div class="tbl-head">
      <h2>ประวัติและรายการยืม–คืนล่าสุด</h2>
      <button type="button" class="btn" id="exportCsv">ส่งออกเป็น CSV</button>
    </div>
    <div class="toolbar">
      <label class="search"><span class="sr-only">ค้นหาผู้ยืมหรือชื่อหนังสือ</span>
        <input type="search" id="q" placeholder="ค้นหาชื่อผู้ยืมหรือชื่อหนังสือ" autocomplete="off">
      </label>
      <div class="filters" role="group" aria-label="กรองตามสถานะ">
        <button type="button" data-filter="all" aria-pressed="true">ทั้งหมด<b><?php echo count($loanRows); ?></b></button>
        <button type="button" data-filter="late" aria-pressed="false">เกินกำหนด<b><?php echo $lateCount; ?></b></button>
        <button type="button" data-filter="soon" aria-pressed="false">ใกล้ครบกำหนด<b><?php echo $dueSoonCount; ?></b></button>
        <button type="button" data-filter="ok" aria-pressed="false">ปกติ<b><?php echo $okCount; ?></b></button>
      </div>
    </div>
    <p class="muted small" id="resultInfo" aria-live="polite"></p>
    <div class="tbl">
      <table>
        <caption class="sr-only">รายการยืมหนังสือ เรียงตามความเร่งด่วน</caption>
        <thead>
          <tr><th scope="col">ผู้ยืม</th><th scope="col">ชื่อหนังสือ</th><th scope="col">จำนวน</th><th scope="col">วันที่ยืม</th><th scope="col">กำหนดคืน</th><th scope="col">สถานะ</th><th scope="col">ค่าปรับสะสม</th></tr>
        </thead>
        <tbody>
          <?php foreach ($loanRows as $index => $row) { ?>
            <tr class="row<?php echo $row['isLate'] ? ' is-late' : ''; ?>" style="--i:<?php echo $index; ?>"
                data-status="<?php echo e($row['status']); ?>" data-fine="<?php echo (int) $row['fine']; ?>"
                data-search="<?php echo e($row['borrower'] . ' ' . $row['itemName']); ?>">
              <td><?php echo e($row['borrower']); ?></td>
              <td><?php echo e($row['itemName']); ?></td>
              <td><?php echo (int) $row['qty']; ?> เล่ม</td>
              <td><?php echo thDate($row['borrowDate']); ?></td>
              <td><?php echo thDate($row['dueDate']); ?></td>
              <td>
                <?php if ($row['isLate']) { ?>
                  <span class="pill late"><i class="pulse"></i>เกิน <?php echo $row['daysLate']; ?> วัน</span>
                <?php } elseif ($row['dueSoon']) { ?>
                  <span class="pill soon"><?php echo $row['daysLeft'] === 0 ? 'ครบกำหนดวันนี้' : 'อีก ' . $row['daysLeft'] . ' วันครบกำหนด'; ?></span>
                <?php } else { ?>
                  <span class="pill ok">ปกติ · เหลือ <?php echo $row['daysLeft']; ?> วัน</span>
                <?php } ?>
              </td>
              <td class="fine-cell<?php echo $row['fine'] > 0 ? ' has-fine' : ''; ?>"><b><?php echo number_format($row['fine'], 2); ?></b> บาท</td>
            </tr>
          <?php } ?>
          <tr class="empty" id="emptyRow" hidden><td colspan="7">ไม่พบรายการที่ตรงกับคำค้นหา ลองเปลี่ยนคำค้นหรือเลือกสถานะ “ทั้งหมด”</td></tr>
        </tbody>
        <tfoot>
          <tr><td colspan="6">ค่าปรับรวมของรายการที่แสดง</td><td><span id="fineSum"><?php echo number_format($totalFine, 2); ?></span> บาท</td></tr>
        </tfoot>
      </table>
    </div>
  </section>

  <footer>ระบบจัดการห้องสมุดอัตโนมัติ · ทีม <?php echo $teamName; ?> · อัปเดตข้อมูลแบบเรียลไทม์</footer>
</main>
</div>

<script>
(() => {
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const root = document.documentElement;

  // 1) Fluid Spotlight Mouse Tracker
  let tx = innerWidth / 2, ty = innerHeight / 3, x = tx, y = ty;
  let raf = 0;
  const follow = () => {
    x += (tx - x) * 0.22;
    y += (ty - y) * 0.22;
    root.style.setProperty('--mx', x.toFixed(1) + 'px');
    root.style.setProperty('--my', y.toFixed(1) + 'px');
    // หยุดวนลูปเมื่อไฟตามเมาส์ทัน เพื่อลดภาระเครื่อง
    raf = (Math.abs(tx - x) > 0.5 || Math.abs(ty - y) > 0.5) ? requestAnimationFrame(follow) : 0;
  };
  addEventListener('pointermove', e => {
    tx = e.clientX; ty = e.clientY;
    if (!reduce && !raf) raf = requestAnimationFrame(follow);
  }, { passive: true });

  // 2) Scroll Reveal Observer
  const io = new IntersectionObserver(entries => {
    entries.forEach(en => {
      if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
    });
  }, { threshold: 0.15 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));

  // 3) Smooth Counter Animation
  const fmt = (v, d) => v.toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
  const count = el => {
    const end = parseFloat(el.dataset.count);
    const d = parseInt(el.dataset.decimals || '0', 10);
    if (reduce) { el.textContent = fmt(end, d); return; }
    const t0 = performance.now(), dur = 1800;
    const step = t => {
      const p = Math.min((t - t0) / dur, 1);
      const e = p === 1 ? 1 : 1 - Math.pow(2, -10 * p);
      el.textContent = fmt(end * e, d);
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  };
  const io2 = new IntersectionObserver(entries => {
    entries.forEach(en => {
      if (en.isIntersecting) { count(en.target); io2.unobserve(en.target); }
    });
  }, { threshold: 0.4 });
  document.querySelectorAll('[data-count]').forEach(el => io2.observe(el));

  // 4) 3D Bento Card Tilt Effect
  if (!reduce) {
    const tiltCards = [...document.querySelectorAll('[data-tilt]')];
    const rects = new Map();
    addEventListener('scroll', () => rects.clear(), { passive: true });

    tiltCards.forEach(el => {
      el.addEventListener('pointerenter', () => {
        el.classList.add('tilt-on');
        rects.set(el, el.getBoundingClientRect());
      });
      el.addEventListener('pointermove', e => {
        let r = rects.get(el);
        if (!r) { r = el.getBoundingClientRect(); rects.set(el, r); }
        const px = Math.min(Math.max((e.clientX - r.left) / r.width, 0), 1);
        const py = Math.min(Math.max((e.clientY - r.top) / r.height, 0), 1);
        el.style.setProperty('--x', (px * 100) + '%');
        el.style.setProperty('--y', (py * 100) + '%');
        el.style.transform = `perspective(1000px) rotateX(${(0.5 - py) * 7}deg) rotateY(${(px - 0.5) * 9}deg) translateY(-5px)`;
      });
      el.addEventListener('pointerleave', () => {
        el.classList.remove('tilt-on');
        el.style.transform = '';
        rects.delete(el);
      });
    });
  }

  // 5) Sidebar ScrollSpy & Indicator
  const sideNav = document.querySelector('.side-nav');
  if (sideNav) {
    const ind = sideNav.querySelector('.side-ind');
    const links = [...sideNav.querySelectorAll('a')];
    const home = sideNav.querySelector('a.active');
    let rest = home;

    const place = el => {
      if (!el) { ind.style.opacity = 0; return; }
      ind.style.height = el.offsetHeight + 'px';
      ind.style.transform = `translateY(${el.offsetTop}px)`;
      ind.style.opacity = 1;
    };
    const setRest = el => {
      rest = el;
      links.forEach(a => a.classList.toggle('on', a === el));
      place(el);
    };

    setRest(home);
    requestAnimationFrame(() => ind.classList.add('ready'));
    links.forEach(a => a.addEventListener('pointerenter', () => place(a)));
    sideNav.addEventListener('pointerleave', () => place(rest));
    addEventListener('resize', () => place(rest));

    const spyLinks = links.filter(a => a.dataset.spy);
    const spy = new IntersectionObserver(entries => {
      entries.forEach(en => {
        const link = spyLinks.find(a => a.dataset.spy === en.target.id);
        if (!link) return;
        if (en.isIntersecting) setRest(link);
        else if (rest === link) setRest(home);
      });
    }, { rootMargin: '-35% 0px -55% 0px' });
    spyLinks.forEach(a => {
      const target = document.getElementById(a.dataset.spy);
      if (target) spy.observe(target);
    });
  }

  // 6) Mobile Drawer Handler
  const closeMenu = () => { document.body.classList.remove('side-open'); const b = document.getElementById('menuBtn'); if (b) b.setAttribute('aria-expanded', 'false'); };
  addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });
  const menuBtn = document.getElementById('menuBtn');
  if (menuBtn) menuBtn.addEventListener('click', () => menuBtn.setAttribute('aria-expanded', document.body.classList.toggle('side-open')));
  const scrim = document.getElementById('scrim');
  if (scrim) scrim.addEventListener('click', closeMenu);
  document.querySelectorAll('.side-nav a').forEach(a => a.addEventListener('click', closeMenu));

  // 7) Real-time Clock Sync
  const liveClock = document.getElementById('liveClock');
  if (liveClock) {
    const box = liveClock.closest('[data-server-ms]');
    const offset = parseInt(box.dataset.serverMs, 10) - Date.now();
    const tick = () => {
      liveClock.textContent = new Date(Date.now() + offset)
        .toLocaleTimeString('th-TH', { timeZone: 'Asia/Bangkok', hour12: false });
    };
    tick();
    setInterval(tick, 500);
  }

  // 8) Table search / filter / CSV export
  const q = document.getElementById('q');
  if (q) {
    const rows = [...document.querySelectorAll('#loans tbody .row')];
    const btns = [...document.querySelectorAll('[data-filter]')];
    const info = document.getElementById('resultInfo');
    const empty = document.getElementById('emptyRow');
    const sum = document.getElementById('fineSum');
    let mode = 'all';
    const apply = () => {
      const term = q.value.trim().toLowerCase();
      let shown = 0, fine = 0;
      rows.forEach(r => {
        const ok = (mode === 'all' || r.dataset.status === mode) && (!term || r.dataset.search.toLowerCase().includes(term));
        r.hidden = !ok;
        if (ok) { shown++; fine += +r.dataset.fine; }
      });
      empty.hidden = shown > 0;
      sum.textContent = fine.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      info.textContent = 'แสดง ' + shown + ' จาก ' + rows.length + ' รายการ';
    };
    q.addEventListener('input', apply);
    btns.forEach(b => b.addEventListener('click', () => {
      mode = b.dataset.filter;
      btns.forEach(x => x.setAttribute('aria-pressed', x === b));
      apply();
    }));
    document.getElementById('exportCsv').addEventListener('click', () => {
      const esc = t => '"' + t.replace(/"/g, '""') + '"';
      const head = [...document.querySelectorAll('#loans thead th')].map(th => esc(th.textContent.trim()));
      const lines = [head.join(',')];
      rows.filter(r => !r.hidden).forEach(r =>
        lines.push([...r.cells].map(c => esc(c.textContent.replace(/\s+/g, ' ').trim())).join(',')));
      const blob = new Blob(['\ufeff' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'loans-<?php echo $today; ?>.csv';
      a.click();
      URL.revokeObjectURL(a.href);
    });
    apply();
  }
})();
</script>
</body>
</html>