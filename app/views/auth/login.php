<?php
$error   = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
$appName = DB::setting('app_name', 'FlexCore');
$accent  = DB::setting('color_accent', '#4f7ef8');
?><!DOCTYPE html>
<html lang="<?= h(currentLang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('auth.login') ?> — <?= h($appName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{--ac:<?= h($accent) ?>;--ac-glow:color-mix(in srgb,var(--ac) 15%,transparent);--font:'Plus Jakarta Sans',sans-serif;--tr:.15s cubic-bezier(.4,0,.2,1)}
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{
      min-height:100vh;background:#0b0f1a;
      font-family:var(--font);color:#e8edf5;
      display:flex;align-items:center;justify-content:center;padding:16px;
      background-image:radial-gradient(ellipse 70% 50% at 50% -5%,color-mix(in srgb,var(--ac) 10%,transparent),transparent);
      -webkit-font-smoothing:antialiased;
    }
    .box{width:100%;max-width:380px}
    .logo{text-align:center;margin-bottom:32px}
    .logo-mark{display:inline-flex;align-items:center;gap:10px;font-size:1.3rem;font-weight:700;letter-spacing:-.02em;color:var(--ac)}
    .logo-icon{width:32px;height:32px;border-radius:9px;background:var(--ac);display:flex;align-items:center;justify-content:center}
    .logo-icon svg{width:15px;height:15px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round}
    .logo-sub{color:#5a6478;font-size:.78rem;margin-top:5px}
    .card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:28px}
    .card-title{font-size:1rem;font-weight:700;letter-spacing:-.01em;margin-bottom:22px;color:#e8edf5}
    .field{margin-bottom:14px}
    .field label{display:block;font-size:.63rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#5a6478;margin-bottom:5px}
    .field input{
      width:100%;background:#0b0f1a;
      border:1px solid rgba(255,255,255,.12);border-radius:7px;
      color:#e8edf5;padding:9px 12px;
      font-family:var(--font);font-size:.84rem;
      outline:none;transition:all var(--tr);
    }
    .field input:focus{border-color:var(--ac);box-shadow:0 0 0 3px var(--ac-glow)}
    .btn-login{
      width:100%;background:var(--ac);color:#fff;
      border:none;border-radius:7px;padding:10px;
      font-family:var(--font);font-size:.88rem;font-weight:700;
      cursor:pointer;margin-top:6px;transition:all var(--tr);letter-spacing:-.01em;
    }
    .btn-login:hover{filter:brightness(1.08);box-shadow:0 0 0 3px var(--ac-glow)}
    .error{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:#f87171;border-radius:7px;padding:10px 13px;font-size:.8rem;margin-bottom:16px;display:flex;align-items:center;gap:8px}
    .footer{text-align:center;margin-top:20px;color:#5a6478;font-size:.7rem}
  </style>
</head>
<body>
<div class="box">
  <div class="logo">
    <div class="logo-mark">
      <div class="logo-icon">
        <svg viewBox="0 0 14 14"><path d="M2 2h4v4H2zM8 2h4v4H8zM2 8h4v4H2z"/><circle cx="10" cy="10" r="2"/></svg>
      </div>
      <?= h($appName) ?>
    </div>
    <div class="logo-sub"><?= __('auth.login_subtitle') ?></div>
  </div>
  <div class="card">
    <div class="card-title"><?= __('auth.login_title') ?></div>
    <?php if ($error): ?>
    <div class="error">❌ <?= h($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="<?= url('login'); ?>">
      <div class="field">
        <label><?= __('auth.email') ?></label>
        <input type="email" name="email" autofocus required placeholder="<?= __('auth.email_placeholder') ?>">
      </div>
      <div class="field">
        <label><?= __('auth.password') ?></label>
        <input type="password" name="password" required placeholder="<?= __('auth.pass_placeholder') ?>">
      </div>
      <button type="submit" class="btn-login"><?= __('auth.sign_in') ?> →</button>
    </form>
  </div>
  <div class="footer"><?= h($appName) ?> v<?= defined('APP_VERSION') ? h(APP_VERSION) : '—' ?></div>
</div>
</body>
</html>
