# simrs
Sistem Informasi Rumah Sakit dan Klinik
# Roadmap 12 Modul dan Status
| No | Modul | Tahap 1 | Pengembangan lanjutan |
|---|---|---|---|
| 1 | Pendaftaran pasien | **Dasar ada** | validasi NIK/Dukcapil sesuai akses, deduplikasi, asuransi, cetak kartu |
| 2 | Pendaftaran IGD | Belum | triase, monitoring, status emergensi, alih rawat |
| 3 | Sinkron jadwal dokter | **Jadwal internal ada** | kuota per sesi, cuti, dokter pengganti, notifikasi |
| 4 | Riwayat medis | **SOAP kunjungan terbatas** | longitudinal RME, diagnosis terkode, lampiran, alergi, akses terkontrol |
| 5 | Rawat inap | Belum | kamar/bed, pindah, catatan perawat, resume |
| 6 | Tindakan ringan/sedang/operasi | Belum | order tindakan, informed consent, ruang operasi dan tim |
| 7 | Rawat jalan | **Registrasi, antrean, SOAP ada** | poli, tindakan, kontrol, rujukan, billing |
| 8 | Fisioterapi | Belum | rencana terapi, jadwal, sesi, evaluasi |
| 9 | Form pemeriksaan dokter | **SOAP dasar ada** | vital sign, ICD, order lab, e-sign, addendum |
| 10 | Farmasi | **Resep elektronik sederhana ada** | dispensing, stok, retur, invoice |
| 11 | Stok farmasi | **Hanya master obat** | gudang, batch, expiry, stok minimum, PO, mutasi dan opname |
| 12 | Apoteker | **Review status manual ada** | workflow pengkajian lengkap, interaksi obat, konseling |
**Tahap 2:** modul IGD, kamar/bed, admisi, alur rawat inap; domain model lengkap dokter/perawat.
**Tahap 3:** tindakan/operasi, fisioterapi, farmasi inventori, dispensing, audit obat, tarif/billing.
**Tahap 4:** SATUSEHAT FHIR, antrean/BPJS sesuai akses sah, reporting, observability, pengujian keamanan, pemulihan bencana, compliance sign-off.
**Tambahan kritis sebelum go-live:** validasi regulasi terbaru, SOP dan otorisasi klinis, hak akses granular, riwayat revisi/addendum RME, cross-facility consent, uji integrasi & migrasi, high availability, monitoring dan cybersecurity.
