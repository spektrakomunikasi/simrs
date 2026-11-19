<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Dashboard extends MY_Controller {
 public function index() {
  $id=$this->facility_id;
  $stats=array(
   'patients'=>$this->db->where('facility_id',$id)->count_all_results('patients'),
   'doctors'=>$this->db->where(array('facility_id'=>$id,'is_active'=>1))->count_all_results('doctors'),
   'today_visits'=>$this->db->where(array('facility_id'=>$id,'visit_date'=>date('Y-m-d')))->count_all_results('visits'),
   'rx_pending'=>$this->db->where(array('facility_id'=>$id,'status'=>'SUBMITTED'))->count_all_results('prescriptions')
  );
  $this->render('dashboard',array('stats'=>$stats));
 }
}
