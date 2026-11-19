<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Patients extends MY_Controller {
 public function index() {
  $this->allow(array('facility_admin','registration','doctor','nurse'));
  $q=trim((string)$this->input->get('q',TRUE));
  $this->db->select('id,medical_record_no,full_name,birth_date,sex,created_at')->from('patients')
   ->where('facility_id',$this->facility_id);
  if ($q!=='') {
   $this->db->group_start()->like('full_name',$q)->or_like('medical_record_no',$q)->group_end();
  }
  $patients=$this->db->order_by('id','DESC')->limit(100)->get()->result();
  $this->render('patients/index',array('patients'=>$patients,'q'=>$q));
 }
 public function create() {
  $this->allow(array('registration','facility_admin')); $error='';
  if ($this->input->method(TRUE)==='POST') {
   $name=trim((string)$this->input->post('full_name',TRUE));
   $nik=trim((string)$this->input->post('national_id',TRUE));
   $birth=trim((string)$this->input->post('birth_date',TRUE));
   $sex=(string)$this->input->post('sex',TRUE);
   $phone=trim((string)$this->input->post('phone',TRUE));
   $address=trim((string)$this->input->post('address',TRUE));
   $birth_valid=($birth==='' || (preg_match('/^\d{4}-\d{2}-\d{2}$/',$birth) && date('Y-m-d',strtotime($birth))===$birth && $birth<=date('Y-m-d')));
   if (strlen($name)<3 || strlen($name)>140 || ($nik!=='' && !preg_match('/^[0-9]{16}$/',$nik)) || !$birth_valid || !in_array($sex,array('M','F','U'),TRUE))
    $error='Data tidak valid. Nama minimal 3 karakter, NIK 16 digit jika diisi, tanggal lahir tidak boleh di masa depan.';
   elseif ($nik!=='' && $this->db->get_where('patients',array('facility_id'=>$this->facility_id,'national_id'=>$nik))->row())
    $error='NIK sudah terdaftar di fasilitas ini; periksa data pasien sebelum membuat baru.';
   else {
    $this->db->trans_begin();
    $ok=$this->db->insert('patients',array('facility_id'=>$this->facility_id,'full_name'=>$name,
     'national_id'=>$nik!==''?$nik:NULL,'birth_date'=>$birth!==''?$birth:NULL,'sex'=>$sex,
     'phone'=>$phone?:NULL,'address'=>$address?:NULL,'created_by'=>$this->user_id));
    $id=(int)$this->db->insert_id();
    $mrn='RM-'.str_pad((string)$this->facility_id,2,'0',STR_PAD_LEFT).'-'.str_pad((string)$id,7,'0',STR_PAD_LEFT);
    $ok=$this->db->where('id',$id)->update('patients',array('medical_record_no'=>$mrn)) && $ok;
    if (!$ok || $this->db->trans_status()===FALSE) { $this->db->trans_rollback(); $error='Gagal menyimpan pasien.'; }
    else { $this->db->trans_commit(); $this->audit('CREATE','patients',$id); redirect('patients/show/'.$id); return; }
   }
  }
  $this->render('patients/form',array('error'=>$error));
 }
 public function show($id=0) {
  $this->allow(array('facility_admin','registration','doctor','nurse'));
  $patient=$this->found('patients',$id); $this->audit('READ','patients',$patient->id);
  // Menampilkan riwayat administratif kunjungan saja; SOAP dibatasi pada dokter penanggung jawab.
  $visits=$this->db->select('v.id,v.visit_date,v.queue_number,v.status,d.name as doctor_name')
   ->from('visits v')->join('doctors d','d.id=v.doctor_id')->where('v.facility_id',$this->facility_id)
   ->where('v.patient_id',$patient->id)->order_by('v.id','DESC')->get()->result();
  $this->render('patients/show',array('patient'=>$patient,'visits'=>$visits));
 }
}
