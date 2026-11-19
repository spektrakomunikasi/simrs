<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login • Spektra SIMRS</title><link rel="stylesheet" href="<?= base_url('assets/style.css') ?>"></head><body class="auth-bg">
<div class="auth-card"><div class="auth-brand">✚ <strong>Spektra SIMRS</strong></div><span class="tag">MULTI-FASILITAS</span><h1>Selamat datang</h1><p class="muted">Masuk dengan akun petugas yang terdaftar.</p>
<?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= site_url('login/submit') ?>"><?= csrf_field() ?>
<label>Username <input name="username" autocomplete="username" required></label>
<label>Password <input type="password" name="password" autocomplete="current-password" required></label>
<button class="btn btn-primary full" type="submit">Masuk ke Dashboard</button></form>
<p class="hint">Untuk evaluasi perangkat lunak, gunakan akun dan data pasien sintetis.</p></div></body></html>
