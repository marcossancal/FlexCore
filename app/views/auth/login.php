<?php
$error   = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
$appName = DB::setting('app_name', 'FlexCore');
$accent  = DB::setting('color_accent', '#00d4ff');
?><!DOCTYPE html>
<html lang="<?= h(currentLang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= __('auth.login') ?> — <?= h($appName) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap" rel="stylesheet">
  <style>
    :root { --ac: <?= h($accent) ?>; }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh; background: #07090e;
      font-family: 'DM Sans', sans-serif; color: #eef0f8;
      display: flex; align-items: center; justify-content: center;
      padding: 16px;
      background-image: radial-gradient(ellipse 60% 50% at 50% -10%, color-mix(in srgb, var(--ac) 12%, transparent), transparent);
    }
    .box { width: 100%; max-width: 400px; }
    .logo { text-align: center; margin-bottom: 36px; }
    .logo-mark {
      display: inline-flex; align-items: center; gap: 10px;
      font-family: 'Syne', sans-serif; font-size: 1.6rem; font-weight: 800; color: var(--ac);
    }
    .logo-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--ac); }
    .logo-sub { color: #68718f; font-size: .82rem; margin-top: 6px; }
    .card {
      background: #111622; border: 1px solid rgba(255,255,255,.06);
      border-radius: 16px; padding: 32px;
    }
    .card-title { font-family: 'Syne',sans-serif; font-size: 1.1rem; font-weight: 700; margin-bottom: 24px; }
    .field { margin-bottom: 16px; }
    .field label { display: block; font-size: .78rem; font-weight: 700; color: #a0a8c0; margin-bottom: 6px; }
    .field input {
      width: 100%; background: #161c2e; border: 1px solid rgba(255,255,255,.1);
      border-radius: 8px; color: #eef0f8; padding: 10px 14px;
      font-family: 'DM Sans', sans-serif; font-size: .9rem;
      outline: none; transition: border-color .18s;
    }
    .field input:focus { border-color: var(--ac); }
    .btn-login {
      width: 100%; background: var(--ac); color: #000;
      border: none; border-radius: 8px; padding: 11px;
      font-family: 'DM Sans', sans-serif; font-size: .95rem; font-weight: 700;
      cursor: pointer; margin-top: 8px; transition: filter .18s;
    }
    .btn-login:hover { filter: brightness(1.1); }
    .error {
      background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.2);
      color: #fca5a5; border-radius: 8px; padding: 10px 14px;
      font-size: .84rem; margin-bottom: 18px;
    }
    .footer { text-align: center; margin-top: 24px; color: #68718f; font-size: .75rem; }
  </style>
</head>
<body>
<div class="box">
  <div class="logo">
    <div class="logo-mark">
      <div class="logo-dot"></div>
      <?= h($appName) ?>
    </div>
    <div class="logo-sub"><?= __('auth.login_subtitle') ?></div>
  </div>

  <div class="card">
    <div class="card-title"><?= __('auth.login_title') ?></div>
    <?php if ($error): ?>
    <div class="error">❌ <?= h($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="<?= url('login');?>">
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
