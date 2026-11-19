<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Medicines extends MY_Controller {
 public function index() {
  $this->allow(array('facility_admin','pharmacist','pharmacy_staff','doctor'));
  $medicines=$this->db->select('id,name,unit,is_active')->from('medicines')
   ->where('facility_id',$this->facility_id)->order_by('name')->get()->result();
  $this->render('medicines/index',array('medicines'=>$medicines));
 }
 public function create() {
  $this->allow(array('facility_admin')); $error='';
  if ($this->input->method(TRUE)==='POST') {
   $name=trim((string)$this->input->post('name',TRUE));
   $unit=trim((string)$this->input->post('unit',TRUE));
   if (strlen($name)<3 || strlen($name)>180 || strlen($unit)<1 || strlen($unit)>40) $error='Nama obat atau satuan tidak valid.';
   elseif ($this->db->get_where('medicines',array('facility_id'=>$this->facility_id,'name'=>$name))->row()) $error='Nama obat sudah ada.';
   elseif ($this->db->insert('medicines',array('facility_id'=>$this->facility_id,'name'=>$name,'unit'=>$unit))) {
    $this->audit('CREATE','medicines',(int)$this->db->insert_id()); redirect('medicines'); return;
   } else $error='Gagal menyimpan master obat.';
  }
  $this->render('medicines/form',array('error'=>$error));
 }
}
