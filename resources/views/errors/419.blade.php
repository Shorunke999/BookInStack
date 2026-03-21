<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>419 — Session Expired</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'DM Sans',sans-serif;background:#f8fafc;color:#0d0d14;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
    .wrap{text-align:center;max-width:420px;}
    .code{font-size:96px;font-weight:800;color:#e2e8f0;line-height:1;margin-bottom:8px;letter-spacing:-4px;}
    .icon{font-size:48px;margin-bottom:16px;}
    h1{font-size:24px;font-weight:700;margin-bottom:8px;}
    p{font-size:15px;color:#64748b;line-height:1.7;margin-bottom:28px;}
    .btn{display:inline-flex;align-items:center;gap:8px;background:#4f46e5;color:#fff;padding:12px 28px;border-radius:9px;font-size:14px;font-weight:600;text-decoration:none;transition:all .2s;cursor:pointer;border:none;font-family:inherit;}
    .btn:hover{background:#4338ca;transform:translateY(-1px);}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="code">419</div>
    <div class="icon">⏱️</div>
    <h1>Session Expired</h1>
    <p>Your session has timed out for security reasons. Please refresh the page and try again.</p>
    <button class="btn" onclick="window.location.reload()">↻ Refresh Page</button>
  </div>
</body>
</html>