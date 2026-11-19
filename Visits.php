<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Visits extends MY_Controller {
 public function index() {
  $this->allow(array('facility_admin','registration','doctor','nurse'));
  $date=(string)$this->input->get('date',TRUE);
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) $date=date('Y-m-d');
  $q=$this->db->select('v.id,v.visit_date,v.queue_number,v.status,p.full_name,d.name as doctor_name,d.user_id as doctor_user_id')
   ->from('visits v')->join('patients p','p.id=v.patient_id')->join('doctors d','d.id=v.doctor_id')
   ->where('v.facility_id',$this->facility_id)->where('v.visit_date',$date);
  if ($this->role==='doctor') $q->where('d.user_id',$this->user_id);
  $visits=$q->order_by('v.queue_number')->limit(250)->get()->result();
  $this->render('visits/index',array('visits'=>$visits,'date'=>$date));
 }
 public function create() {
  $this->allow(array('facility_admin','registration')); $error='';
  if ($this->input->method(TRUE)==='POST') {
   $pid=(int)$this->input->post('patient_id');
   $did=(int)$this->input->post('doctor_id');
   $date=(string)$this->input->post('visit_date',TRUE);
   $date_valid=preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) && date('Y-m-d',strtotime($date))===$date && $date>=date('Y-m-d');
   $patient=$this->db->get_where('patients',array('id'=>$pid,'facility_id'=>$this->facility_id))->row();
   $doctor=$this->db->get_where('doctors',array('id'=>$did,'facility_id'=>$this->facility_id,'is_active'=>1))->row();
   if (!$patient || !$doctor || !$date_valid) $error='Pasien, dokter, atau tanggal kunjungan tidak valid.';
   else {
    $weekday=(int)date('N',strtotime($date));
    $sch=$this->db->select('SUM(quota) as total_quota')->from('doctor_schedules')
     ->where(array('facility_id'=>$this->facility_id,'doctor_id'=>$did,'weekday'=>$weekday,'is_active'=>1))->get()->row();
    $max=(int)$sch->total_quota;
    if ($max===0) $error='Tidak ada jadwal dokter aktif untuk tanggal tersebut.';
    else {
     // Lock counter per fasilitas + dokter + tanggal; mencegah nomor antrean ganda.
     $this->db->trans_begin();
     $this->db->query('INSERT INTO queue_counters (facility_id,doctor_id,visit_date,last_no) VALUES (?,?,?,0) ON DUPLICATE KEY UPDATE last_no=last_no',array($this->facility_id,$did,$date));
     $counter=$this->db->query('SELECT last_no FROM queue_counters WHERE facility_id=? AND doctor_id=? AND visit_date=? FOR UPDATE',array($this->facility_id,$did,$date))->row();
     $next=$counter ? (int)$counter->last_no+1 : 1;
     if ($next>$max) { $this->db->trans_rollback(); $error='Kuota dokter untuk hari tersebut penuh.'; }
     else {
      $ok=$this->db->where(array('facility_id'=>$this->facility_id,'doctor_id'=>$did,'visit_date'=>$date))
       ->update('queue_counters',array('last_no'=>$next));
      $ok=$this->db->insert('visits',array('facility_id'=>$this->facility_id,'patient_id'=>$pid,'doctor_id'=>$did,
       'visit_date'=>$date,'queue_number'=>$next,'visit_type'=>'OUTPATIENT','status'=>'WAITING','created_by'=>$this->user_id)) && $ok;
      $vid=(int)$this->db->insert_id();
      if (!$ok || $this->db->trans_status()===FALSE) { $this->db->trans_rollback(); $error='Pendaftaran gagal, coba ulang.'; }
      else { $this->db->trans_commit(); $this->audit('CREATE','visits',$vid); redirect('visits/show/'.$vid); return; }
     }
    }
   }
  }
  $patients=$this->db->select('id,medical_record_no,full_name')->from('patients')
    ->where('facility_id',$this->facility_id)->order_by('id','DESC')->limit(300)->get()->result();
  $doctors=$this->db->select('id,name,specialty')->get_where('doctors',array('facility_id'=>$this->facility_id,'is_active'=>1))->result();
  $this->render('visits/form',array('error'=>$error,'patients'=>$patients,'doctors'=>$doctors));
 }
 public function show($id=0) {
  $this->allow(array('facility_admin','registration','doctor','nurse'));
  $visit=$this->db->select('v.*,p.full_name,p.medical_record_no,d.name as doctor_name,d.user_id as doctor_user_id')
   ->from('visits v')->join('patients p','p.id=v.patient_id')->join('doctors d','d.id=v.doctor_id')
   ->where('v.id',(int)$id)->where('v.facility_id',$this->facility_id)->where('p.facility_id',$this->facility_id)
   ->where('d.facility_id',$this->facility_id)->get()->row();
  if (!$visit) show_404();
  $this->audit('READ','visits',$visit->id);
  if ($this->role==='doctor' && (int)$visit->doctor_user_id!==$this->user_id) show_error('Kunjungan ini bukan penugasan Anda.',403);
  $can_edit=($this->role==='doctor' && (int)$visit->doctor_user_id===$this->user_id);
  $note=$can_edit ? $this->db->get_where('medical_notes',array('facility_id'=>$this->facility_id,'visit_id'=>$visit->id))->row() : NULL;
  $rx=$can_edit ? $this->db->get_where('prescriptions',array('facility_id'=>$this->facility_id,'visit_id'=>$visit->id))->result() : array();
  $medicines=$can_edit ? $this->db->get_where('medicines',array('facility_id'=>$this->facility_id,'is_active'=>1))->result() : array();
  $this->render('visits/show',array('visit'=>$visit,'can_edit'=>$can_edit,'note'=>$note,'prescriptions'=>$rx,'medicines'=>$medicines));
 }
 public function save_note($id=0) {
  $this->only_post(); $visit=$this->found('visits',$id); $this->doctor_for_visit($visit);
  if ($visit->status==='CANCELLED') show_error('Kunjungan dibatalkan.',409);
  $fields=array();
  foreach(array('subjective','objective','assessment','plan') as $k) {
   $fields[$k]=trim((string)$this->input->post($k,TRUE));
   if (strlen($fields[$k])>20000) show_error('Catatan terlalu panjang.',422);
  }
  if ($fields['assessment']==='' || $fields['plan']==='') show_error('Assessment dan Plan wajib diisi.',422);
  $existing=$this->db->get_where('medical_notes',array('facility_id'=>$this->facility_id,'visit_id'=>$visit->id))->row();
  $saved_note_id=$existing ? (int)$existing->id : NULL;
  if ($existing && $existing->status==='FINAL') show_error('Catatan final tidak dapat diubah.',409);
  $this->db->trans_begin();
  if ($existing) {
   if ((int)$existing->author_user_id!==$this->user_id) show_error('Catatan dibuat dokter lain.',403);
   $ok=$this->db->where('id',$existing->id)->where('status','DRAFT')->update('medical_notes',$fields);
  } else {
   $fields['facility_id']=$this->facility_id; $fields['visit_id']=$visit->id; $fields['author_user_id']=$this->user_id;
   $ok=$this->db->insert('medical_notes',$fields); $saved_note_id=(int)$this->db->insert_id();
  }
  $ok=$this->db->where('id',$visit->id)->where('facility_id',$this->facility_id)
   ->update('visits',array('status'=>'IN_PROGRESS')) && $ok;
  if (!$ok || $this->db->trans_status()===FALSE) { $this->db->trans_rollback(); show_error('Gagal menyimpan catatan.',500); }
  $this->db->trans_commit(); $this->audit('SAVE_DRAFT','medical_notes',$saved_note_id);
  redirect('visits/show/'.$visit->id);
 }
 public function finalize_note($id=0) {
  $this->only_post(); $visit=$this->found('visits',$id); $this->doctor_for_visit($visit);
  $note=$this->db->get_where('medical_notes',array('facility_id'=>$this->facility_id,'visit_id'=>$visit->id,
   'author_user_id'=>$this->user_id,'status'=>'DRAFT'))->row();
  if (!$note || trim($note->assessment)==='' || trim($note->plan)==='') show_error('Simpan draft SOAP dahulu.',422);
  $this->db->trans_begin();
  $ok=$this->db->where('id',$note->id)->where('status','DRAFT')->update('medical_notes',array('status'=>'FINAL','finalized_at'=>date('Y-m-d H:i:s')));
  $ok=$this->db->where('id',$visit->id)->where('facility_id',$this->facility_id)->update('visits',array('status'=>'FINISHED')) && $ok;
  if (!$ok || $this->db->trans_status()===FALSE) { $this->db->trans_rollback(); show_error('Gagal finalisasi.',500); }
  $this->db->trans_commit(); $this->audit('FINALIZE','medical_notes',$note->id);
  redirect('visits/show/'.$visit->id);
 }
 public function add_prescription($id=0) {
  $this->only_post(); $visit=$this->found('visits',$id); $this->doctor_for_visit($visit);
  if ($visit->status==='CANCELLED') show_error('Kunjungan dibatalkan.',409);
  $medicine_id=(int)$this->input->post('medicine_id');
  $directions=trim((string)$this->input->post('directions',TRUE));
  $qty=(float)$this->input->post('quantity');
  $medicine=$this->db->get_where('medicines',array('id'=>$medicine_id,'facility_id'=>$this->facility_id,'is_active'=>1))->row();
  if (!$medicine || $directions==='' || strlen($directions)>255 || $qty<=0 || $qty>9999) show_error('Data resep tidak valid.',422);
  $this->db->trans_begin();
  $ok=$this->db->insert('prescriptions',array('facility_id'=>$this->facility_id,'visit_id'=>$visit->id,'doctor_user_id'=>$this->user_id,'status'=>'SUBMITTED'));
  $rxid=(int)$this->db->insert_id();
  $ok=$this->db->insert('prescription_items',array('prescription_id'=>$rxid,'medicine_id'=>$medicine_id,'directions'=>$directions,'quantity'=>$qty)) && $ok;
  if (!$ok || $this->db->trans_status()===FALSE) { $this->db->trans_rollback(); show_error('Resep gagal disimpan.',500); }
  $this->db->trans_commit(); $this->audit('SUBMIT','prescriptions',$rxid); redirect('visits/show/'.$visit->id);
 }
}
