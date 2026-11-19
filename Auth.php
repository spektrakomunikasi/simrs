<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Auth extends CI_Controller {
 public function __construct() { parent::__construct(); date_default_timezone_set('Asia/Jakarta'); }
 public function login() {
  if ((int)$this->db->count_all('users')===0) { redirect('setup'); return; }
  if ($this->session->userdata('user_id')) { redirect('dashboard'); return; }
  $this->load->view('auth/login',array('error'=>$this->session->flashdata('auth_error')));
 }
 public function submit() {
  if ($this->input->method(TRUE)!=='POST') show_error('Metode tidak diperbolehkan.',405);
  $username=trim((string)$this->input->post('username',TRUE));
  $password=(string)$this->input->post('password',FALSE);
  $scope=hash('sha256',strtolower($username).'|'.$this->input->ip_address());
  $throttle=$this->db->get_where('login_throttles',array('scope_hash'=>$scope))->row();
  if ($throttle && $throttle->locked_until && strtotime($throttle->locked_until)>time()) {
   $this->session->set_flashdata('auth_error','Terlalu banyak percobaan. Ulangi sekitar 15 menit lagi.');
   redirect('login'); return;
  }
  $user=$this->db->get_where('users',array('username'=>$username,'is_active'=>1))->row();
  // Password hash dummy mengurangi perbedaan waktu saat user tidak ditemukan.
  $dummy='$2y$10$5xN2b5BJcCKxCSvMYlLEWebxAnmwI0CtIghlx/Tx0xHHxAy6BgDui';
  $valid=password_verify($password,$user ? $user->password_hash : $dummy);
  if (!$user || !$valid) {
   $attempts=$throttle && (!$throttle->locked_until || strtotime($throttle->locked_until)>time()) ? ((int)$throttle->attempts+1) : 1;
   $data=array('attempts'=>min($attempts,100),'locked_until'=>$attempts>=5?date('Y-m-d H:i:s',time()+900):NULL);
   if ($throttle) $this->db->where('scope_hash',$scope)->update('login_throttles',$data);
   else { $data['scope_hash']=$scope; $this->db->insert('login_throttles',$data); }
   $this->session->set_flashdata('auth_error','Username atau password salah.');
   redirect('login'); return;
  }
  $mem=$this->db->select('m.facility_id')->from('facility_memberships m')
   ->join('facilities f','f.id=m.facility_id')->where('m.user_id',$user->id)
   ->where('f.active',1)->order_by('m.facility_id')->get()->row();
  if (!$mem) { $this->session->set_flashdata('auth_error','Tidak ada fasilitas yang diizinkan.'); redirect('login'); return; }
  $this->db->delete('login_throttles',array('scope_hash'=>$scope));
  $this->session->sess_regenerate(TRUE);
  $this->session->set_userdata(array('user_id'=>(int)$user->id,'facility_id'=>(int)$mem->facility_id));
  $this->db->insert('audit_logs',array('facility_id'=>(int)$mem->facility_id,'user_id'=>(int)$user->id,
    'event_type'=>'LOGIN','entity_type'=>'users','entity_id'=>$user->id,'ip_address'=>$this->input->ip_address()));
  redirect('dashboard');
 }
 public function logout() {
  if ($this->input->method(TRUE)!=='POST') show_error('Metode tidak diperbolehkan.',405);
  $this->session->sess_destroy(); redirect('login');
 }
}
