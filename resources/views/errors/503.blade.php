<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>503 — Under Maintenance</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'DM Sans',sans-serif;background:#0d0d14;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
    .wrap{text-align:center;max-width:440px;}
    .logo{font-size:22px;font-weight:800;color:#fff;margin-bottom:40px;letter-spacing:-.3px;}
    .logo span{color:#818cf8;}
    .icon{font-size:52px;margin-bottom:20px;}
    h1{font-size:28px;font-weight:800;margin-bottom:10px;}
    p{font-size:15px;color:rgba(255,255,255,.6);line-height:1.7;margin-bottom:32px;}
    .badge{display:inline-flex;align-items:center;gap:8px;padding:8px 18px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:20px;font-size:13px;color:rgba(255,255,255,.7);margin-bottom:32px;}
    .dot{width:8px;height:8px;background:#f59e0b;border-radius:50%;animation:pulse 1.5s infinite;}
    @keyframes pulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.4;transform:scale(1.3);}}
    .hint{font-size:12px;color:rgba(255,255,255,.3);}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="logo">BookIn<span>Stack</span></div>
    <div class="icon">🔧</div>
    <div class="badge"><span class="dot"></span> Maintenance in progress</div>
    <h1>We'll be right back</h1>
    <p>BookInStack is undergoing scheduled maintenance to improve your experience. We'll be back shortly.</p>
    <div class="hint">If this continues, contact support.</div>
  </div>
</body>
</html>