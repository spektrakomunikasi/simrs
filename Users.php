<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Users extends MY_Controller {
 public function index() {
  $this->allow(array('facility_admin'));
  $list=$this->db->select('u.id,u.full_name,u.username,u.is_active,m.role')->from('facility_memberships m')
   ->join('users u','u.id=m.user_id')->where('m.facility_id',$this->facility_id)->order_by('u.full_name')->get()->result();
  $this->render('users/index',array('users'=>$list));
 }
 public function create() {
  $this->allow(array('facility_admin')); $error='';
  $roles=array('facility_admin','registration','doctor','nurse','pharmacist','pharmacy_staff','management');
  if ($this->input->method(TRUE)==='POST') {
   $name=trim((string)$this->input->post('full_name',TRUE));
   $username=trim((string)$this->input->post('username',TRUE));
   $password=(string)$this->input->post('password',FALSE);
   $role=(string)$this->input->post('role',TRUE);
   if (strlen($name)<3 || !preg_match('/^[a-zA-Z0-9_.-]{4,60}$/',$username) || strlen($password)<12 || !in_array($role,$roles,TRUE))
    $error='Periksa nama, username, password (minimal 12 karakter), dan peran.';
   elseif ($this->db->get_where('users',array('username'=>$username))->row()) $error='Username sudah digunakan.';
   else {
    $this->db->trans_begin();
    $ok=$this->db->insert('users',array('full_name'=>$name,'username'=>$username,
      'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'is_active'=>1));
    $uid=(int)$this->db->insert_id();
    $ok=$this->db->insert('facility_memberships',array('user_id'=>$uid,'facility_id'=>$this->facility_id,'role'=>$role)) && $ok;
    if (!$ok || $this->db->trans_status()===FALSE) { $this->db->trans_rollback(); $error='Gagal menyimpan pengguna.'; }
    else { $this->db->trans_commit(); $this->audit('CREATE','users',$uid); redirect('users'); return; }
   }
  }
  $this->render('users/form',array('error'=>$error,'roles'=>$roles));
 }
}
