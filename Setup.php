<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Setup extends CI_Controller {
 public function __construct() { parent::__construct(); date_default_timezone_set('Asia/Jakarta'); }
 public function index() {
  if ((int)$this->db->count_all('users')!==0) show_404();
  $error='';
  if ($this->input->method(TRUE)==='POST') {
   $key=(string)$this->input->post('setup_key',FALSE);
   $name=trim((string)$this->input->post('full_name',TRUE));
   $username=trim((string)$this->input->post('username',TRUE));
   $password=(string)$this->input->post('password',FALSE);
   if (!hash_equals((string)$this->config->item('setup_key'),$key)) $error='Kunci instalasi salah.';
   elseif (strlen($name)<3 || !preg_match('/^[a-zA-Z0-9_.-]{4,60}$/',$username) || strlen($password)<12)
    $error='Nama minimal 3 karakter, username minimal 4 (huruf/angka), password minimal 12 karakter.';
   else {
    $this->db->trans_begin();
    $ok=$this->db->insert('users',array('full_name'=>$name,'username'=>$username,
      'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'is_super_admin'=>1,'is_active'=>1));
    $uid=(int)$this->db->insert_id();
    $facilities=$this->db->get_where('facilities',array('active'=>1))->result();
    foreach ($facilities as $f) $ok=$this->db->insert('facility_memberships',array('user_id'=>$uid,'facility_id'=>$f->id,'role'=>'facility_admin')) && $ok;
    if (!$ok || $this->db->trans_status()===FALSE) { $this->db->trans_rollback(); $error='Gagal membuat admin. Periksa database.'; }
    else { $this->db->trans_commit(); redirect('login'); return; }
   }
  }
  $this->load->view('setup/index',array('error'=>$error));
 }
}
