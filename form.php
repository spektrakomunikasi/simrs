<div class="heading"><div><span class="eyebrow">PENDAFTARAN</span><h1>Pasien Baru</h1><p>Nomor rekam medis dibuat otomatis sesudah disimpan.</p></div></div>
<div class="panel form-panel"><?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="<?= site_url('patients/new') ?>"><?= csrf_field() ?>
<div class="form-grid"><label>Nama lengkap *<input name="full_name" required maxlength="140" value="<?= e(set_value('full_name')) ?>"></label>
<label>NIK (opsional, 16 digit)<input name="national_id" maxlength="16" pattern="[0-9]{16}" value="<?= e(set_value('national_id')) ?>"></label>
<label>Tanggal lahir <input type="date" name="birth_date" value="<?= e(set_value('birth_date')) ?>"></label>
<label>Jenis kelamin <select name="sex"><option value="U">Tidak dinyatakan</option><option value="M">Laki-laki</option><option value="F">Perempuan</option></select></label>
<label>No. telepon <input name="phone" maxlength="25" value="<?= e(set_value('phone')) ?>"></label>
<label class="span2">Alamat <textarea name="address" rows="3"><?= e(set_value('address')) ?></textarea></label></div>
<div class="actions"><button class="btn btn-primary" type="submit">Simpan Pasien</button><a class="btn" href="<?= site_url('patients') ?>">Batal</a></div></form></div>
