<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Doctors extends MY_Controller {
 public function index() {
  $this->allow(array('facility_admin','registration','doctor','nurse'));
  $list=$this->db->select('d.*,u.username')->from('doctors d')->join('users u','u.id=d.user_id','left')
   ->where('d.facility_id',$this->facility_id)->order_by('d.name')->get()->result();
  $this->render('doctors/index',array('doctors'=>$list));
 }
 public function create() {
  $this->allow(array('facility_admin')); $error='';
  if ($this->input->method(TRUE)==='POST') {
   $name=trim((string)$this->input->post('name',TRUE));
   $specialty=trim((string)$this->input->post('specialty',TRUE));
   $sip=trim((string)$this->input->post('sip_no',TRUE));
   $uid=(int)$this->input->post('user_id');
   if (strlen($name)<3 || strlen($specialty)<3 || $uid<1) $error='Isi nama, spesialisasi/poli dan akun dokter.';
   elseif (!$this->db->get_where('facility_memberships',array('user_id'=>$uid,'facility_id'=>$this->facility_id,'role'=>'doctor'))->row()) $error='Akun bukan dokter di fasilitas ini.';
   elseif ($this->db->get_where('doctors',array('facility_id'=>$this->facility_id,'user_id'=>$uid))->row()) $error='Akun dokter sudah terhubung.';
   else {
    $ok=$this->db->insert('doctors',array('facility_id'=>$this->facility_id,'user_id'=>$uid,
     'name'=>$name,'specialty'=>$specialty,'sip_no'=>$sip?:NULL));
    if ($ok) { $id=(int)$this->db->insert_id(); $this->audit('CREATE','doctors',$id); redirect('doctors'); return; }
    $error='Gagal menyimpan dokter.';
   }
  }
  $user_options=$this->db->select('u.id,u.full_name')->from('facility_memberships m')
   ->join('users u','u.id=m.user_id')->where('m.facility_id',$this->facility_id)
   ->where('m.role','doctor')->where('u.is_active',1)->get()->result();
  $this->render('doctors/form',array('error'=>$error,'user_options'=>$user_options));
 }
 public function schedules() {
  $this->allow(array('facility_admin','registration','doctor','nurse'));
  $schedules=$this->db->select('s.*,d.name as doctor_name,d.specialty')->from('doctor_schedules s')
    ->join('doctors d','d.id=s.doctor_id')->where('s.facility_id',$this->facility_id)
    ->order_by('s.weekday')->order_by('s.start_time')->get()->result();
  $this->render('doctors/schedules',array('schedules'=>$schedules));
 }
 public function create_schedule() {
  $this->allow(array('facility_admin')); $error='';
  if ($this->input->method(TRUE)==='POST') {
   $doc=(int)$this->input->post('doctor_id'); $weekday=(int)$this->input->post('weekday');
   $start=(string)$this->input->post('start_time',TRUE); $end=(string)$this->input->post('end_time',TRUE);
   $quota=(int)$this->input->post('quota'); $room=trim((string)$this->input->post('room_label',TRUE));
   if (!$this->db->get_where('doctors',array('id'=>$doc,'facility_id'=>$this->facility_id,'is_active'=>1))->row()
    || $weekday<1 || $weekday>7 || !preg_match('/^\d\d:\d\d$/',$start) || !preg_match('/^\d\d:\d\d$/',$end)
    || $start>=$end || $quota<1 || $quota>500) $error='Jadwal atau kuota tidak valid.';
   else {
    $ok=$this->db->insert('doctor_schedules',array('facility_id'=>$this->facility_id,'doctor_id'=>$doc,
      'weekday'=>$weekday,'start_time'=>$start,'end_time'=>$end,'quota'=>$quota,'room_label'=>$room?:NULL));
    if ($ok) { $this->audit('CREATE','doctor_schedules',(int)$this->db->insert_id()); redirect('doctors/schedules'); return; }
    $error='Gagal menyimpan jadwal.';
   }
  }
  $doctors=$this->db->get_where('doctors',array('facility_id'=>$this->facility_id,'is_active'=>1))->result();
  $this->render('doctors/schedule_form',array('error'=>$error,'doctors'=>$doctors));
 }
}
