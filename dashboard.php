<div class="heading"><div><span class="eyebrow">OVERVIEW</span><h1>Dashboard Operasional</h1><p>Ringkasan data pada <?= e($current_facility->name) ?>.</p></div></div>
<div class="stats"><div class="stat"><div class="stat-icon">♙</div><div class="stat-num"><?= (int)$stats['patients'] ?></div><div class="stat-text">Pasien terdaftar</div></div>
<div class="stat"><div class="stat-icon">⚕</div><div class="stat-num"><?= (int)$stats['doctors'] ?></div><div class="stat-text">Dokter aktif</div></div>
<div class="stat"><div class="stat-icon">▤</div><div class="stat-num"><?= (int)$stats['today_visits'] ?></div><div class="stat-text">Kunjungan hari ini</div></div>
<div class="stat"><div class="stat-icon">✚</div><div class="stat-num"><?= (int)$stats['rx_pending'] ?></div><div class="stat-text">Resep perlu verifikasi</div></div></div>
<div class="panel"><h2>Alur pelayanan Tahap 1</h2><div class="flow-items"><div>1. Registrasi pasien</div><div>2. Jadwal dokter</div><div>3. Antrean rawat jalan</div><div>4. Catatan SOAP</div><div>5. Resep elektronik</div><div>6. Verifikasi apoteker</div></div>
<p class="hint">Status ini adalah prototipe dasar. Modul IGD, rawat inap, operasi, fisioterapi, dan stok farmasi belum diimplementasikan.</p></div>
