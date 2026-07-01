<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Customer Portal') — PharmaTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  @php $ps = \App\Support\PortalSettings::all(); @endphp
  <style>
    :root{
      --brand1:{{ $ps['portal_primary'] ?: '#4f46e5' }}; --brand2:{{ $ps['portal_accent'] ?: '#0ea5e9' }}; --ink:#0f172a; --muted:#64748b;
      --line:#e8edf3; --bg:#f4f7fb; --card:#ffffff;
    }
    *{ font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; }
    body{ background:var(--bg); color:var(--ink); }
    a{ text-decoration:none; }
    .brand{ font-weight:800; letter-spacing:-.6px; }
    .brand span{ background:linear-gradient(90deg,var(--brand1),var(--brand2)); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
    .grad{ background:linear-gradient(135deg,var(--brand1),var(--brand2)); }
    .card-soft{ background:var(--card); border:1px solid var(--line); border-radius:16px; box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .btn-grad{ background:linear-gradient(135deg,var(--brand1),var(--brand2)); border:0; color:#fff; font-weight:600; }
    .btn-grad:hover{ filter:brightness(1.05); color:#fff; }
    .form-control,.form-select{ border-radius:10px; border-color:var(--line); padding:.5rem .75rem; }
    .form-control:focus,.form-select:focus{ border-color:var(--brand1); box-shadow:0 0 0 .2rem rgba(79,70,229,.12); }
    .stat{ background:var(--card); border:1px solid var(--line); border-radius:14px; padding:16px 18px; }
    .stat .v{ font-size:26px; font-weight:800; line-height:1; }
    .stat .l{ font-size:12px; color:var(--muted); margin-top:6px; }
    .stat .ic{ width:40px;height:40px;border-radius:11px; display:flex;align-items:center;justify-content:center; font-size:18px; }
    .pill{ border:1px solid var(--line); background:#fff; border-radius:999px; padding:.35rem .85rem; font-size:13px; font-weight:600; color:var(--muted); cursor:pointer; }
    .pill.active{ background:linear-gradient(135deg,var(--brand1),var(--brand2)); color:#fff; border-color:transparent; }
    .table-clean td,.table-clean th{ border-color:var(--line); font-size:13px; vertical-align:middle; }
    .table-clean th{ color:var(--muted); font-weight:600; text-transform:uppercase; font-size:11px; letter-spacing:.03em; }
    .chip{ font-size:11px; font-weight:600; padding:.2rem .55rem; border-radius:999px; }
    [x-cloak]{ display:none !important; }
    .auth-hero{ background:linear-gradient(150deg,var(--brand1),var(--brand2)); color:#fff; }
    .auth-hero .feat{ display:flex; gap:12px; align-items:flex-start; margin-bottom:18px; }
    .auth-hero .feat i{ font-size:18px; opacity:.9; }
  </style>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
@yield('body')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
