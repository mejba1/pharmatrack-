<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Customer Portal') — PharmaTrack</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body { background:#f1f4f9; }
    .brand { font-weight:800; letter-spacing:-.5px; }
    .brand span { color:#0d6efd; }
    .stat-card { background:#fff; border:1px solid #e9eef5; border-radius:12px; padding:16px; }
    .stat-value { font-size:24px; font-weight:700; }
    .stat-label { font-size:12px; color:#6c757d; }
  </style>
</head>
<body>
@yield('body')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
