<div class="heading"><div><span class="eyebrow">MASTER DATA</span><h1>Data Pasien</h1><p>Terbatas pada fasilitas aktif. Pencarian maksimal 100 hasil.</p></div>
<?php if (in_array($active_role,array('registration','facility_admin'),TRUE)): ?><a class="btn btn-primary" href="<?= site_url('patients/new') ?>">+ Pasien Baru</a><?php endif; ?></div>
<div class="panel"><form class="filter" method="get" action="<?= site_url('patients') ?>"><input name="q" value="<?= e($q) ?>" placeholder="Cari nama / nomor RM"><button class="btn" type="submit">Cari</button></form>
<div class="table-wrap"><table><thead><tr><th>Nomor RM</th><th>Nama Pasien</th><th>Tanggal Lahir</th><th>Jenis Kelamin</th><th></th></tr></thead><tbody>
<?php foreach ($patients as $p): ?><tr><td class="mono"><?= e($p->medical_record_no) ?></td><td><strong><?= e($p->full_name) ?></strong></td><td><?= date_indo($p->birth_date) ?></td><td><?= e($p->sex) ?></td><td><a href="<?= site_url('patients/show/'.$p->id) ?>">Detail</a></td></tr><?php endforeach; ?>
<?php if (!$patients): ?><tr><td colspan="5" class="empty">Belum ada data pasien.</td></tr><?php endif; ?></tbody></table></div></div>
