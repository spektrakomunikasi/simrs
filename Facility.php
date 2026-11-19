<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Facility extends MY_Controller {
 private function require_super_admin() {
  $u=$this->db->select('is_super_admin')->get_where('users',array('id'=>$this->user_id))->row();
  if (!$u || !(int)$u->is_super_admin) show_error('Hanya administrator pusat yang diizinkan.',403);
 }
 public function select() {
  $this->only_post();
  $id=(int)$this->input->post('facility_id');
  $valid=$this->db->from('facility_memberships m')->join('facilities f','f.id=m.facility_id')
   ->where('m.user_id',$this->user_id)->where('m.facility_id',$id)->where('f.active',1)->count_all_results();
  if (!$valid) show_error('Fasilitas tidak diizinkan.',403);
  $this->session->set_userdata('facility_id',$id); redirect('dashboard');
 }
 public function manage() {
  $this->require_super_admin(); $error='';
  if ($this->input->method(TRUE)==='POST') {
   $code=strtoupper(trim((string)$this->input->post('code',TRUE)));
   $name=trim((string)$this->input->post('name',TRUE));
   $type=(string)$this->input->post('facility_type',TRUE);
   if (!preg_match('/^[A-Z0-9-]{3,20}$/',$code) || strlen($name)<3 || strlen($name)>160 || !in_array($type,array('CLINIC','HOSPITAL'),TRUE)) $error='Kode, nama, atau jenis fasilitas tidak valid.';
   elseif ($this->db->get_where('facilities',array('code'=>$code))->row()) $error='Kode fasilitas sudah terdaftar.';
   else {
    $this->db->trans_begin();
    $ok=$this->db->insert('facilities',array('code'=>$code,'name'=>$name,'facility_type'=>$type,'active'=>1));
    $id=(int)$this->db->insert_id();
    $ok=$this->db->insert('facility_memberships',array('user_id'=>$this->user_id,'facility_id'=>$id,'role'=>'facility_admin')) && $ok;
    if (!$ok || $this->db->trans_status()===FALSE) { $this->db->trans_rollback(); $error='Gagal menambah fasilitas.'; }
    else { $this->db->trans_commit(); $this->audit('CREATE','facilities',$id); redirect('facility/manage'); return; }
   }
  }
  $facilities=$this->db->order_by('id')->get('facilities')->result();
  $this->render('facilities/manage',array('facilities'=>$facilities,'error'=>$error));
 }
 public function members() {
  $this->require_super_admin(); $error='';
  $roles=array('facility_admin','registration','doctor','nurse','pharmacist','pharmacy_staff','management');
  if ($this->input->method(TRUE)==='POST') {
   $uid=(int)$this->input->post('user_id'); $fid=(int)$this->input->post('facility_id');
   $role=(string)$this->input->post('role',TRUE);
   $user=$this->db->get_where('users',array('id'=>$uid,'is_active'=>1))->row();
   $facility=$this->db->get_where('facilities',array('id'=>$fid,'active'=>1))->row();
   $existing=$this->db->get_where('facility_memberships',array('user_id'=>$uid,'facility_id'=>$fid))->row();
   if (!$user || !$facility || !in_array($role,$roles,TRUE)) $error='Akun, fasilitas, atau peran tidak valid.';
   elseif ($existing) $error='Akun sudah terdaftar pada fasilitas ini. Mengganti peran belum disediakan.';
   elseif ($this->db->insert('facility_memberships',array('user_id'=>$uid,'facility_id'=>$fid,'role'=>$role))) {
    $this->audit('ADD_MEMBERSHIP','users',$uid); redirect('facility/members'); return;
   } else $error='Gagal menambahkan penugasan.';
  }
  $users=$this->db->select('id,username,full_name')->get_where('users',array('is_active'=>1))->result();
  $facilities=$this->db->get_where('facilities',array('active'=>1))->result();
  $members=$this->db->select('u.full_name,f.name as facility_name,m.role')->from('facility_memberships m')
   ->join('users u','u.id=m.user_id')->join('facilities f','f.id=m.facility_id')
   ->order_by('f.name')->order_by('u.full_name')->get()->result();
  $this->render('facilities/members',array('users'=>$users,'facilities'=>$facilities,'members'=>$members,'roles'=>$roles,'error'=>$error));
 }
}
