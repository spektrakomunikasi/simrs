<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Pharmacy extends MY_Controller {
 public function index() {
  $this->allow(array('pharmacist','pharmacy_staff','facility_admin'));
  $list=$this->db->select('rx.id,rx.status,rx.created_at,rx.review_note,p.full_name,d.name AS doctor_name,
      m.name as medicine_name,pi.directions,pi.quantity,m.unit')
   ->from('prescriptions rx')->join('visits v','v.id=rx.visit_id')->join('patients p','p.id=v.patient_id')
   ->join('doctors d','d.id=v.doctor_id')->join('prescription_items pi','pi.prescription_id=rx.id')
   ->join('medicines m','m.id=pi.medicine_id')->where('rx.facility_id',$this->facility_id)
   ->where('v.facility_id',$this->facility_id)->where('p.facility_id',$this->facility_id)
   ->where('d.facility_id',$this->facility_id)->where('m.facility_id',$this->facility_id)
   ->order_by('rx.id','DESC')->limit(100)->get()->result();
  $this->render('pharmacy/index',array('prescriptions'=>$list));
 }
 public function review($id=0) {
  $this->allow(array('pharmacist')); $this->only_post();
  $rx=$this->found('prescriptions',$id);
  if ($rx->status!=='SUBMITTED') show_error('Resep sudah ditinjau.',409);
  $decision=(string)$this->input->post('decision',TRUE);
  $note=trim((string)$this->input->post('review_note',TRUE));
  if (!in_array($decision,array('VERIFIED','REJECTED'),TRUE) || strlen($note)>5000 || ($decision==='REJECTED' && $note==='')) show_error('Keputusan tidak valid.',422);
  $this->db->where('id',$rx->id)->where('facility_id',$this->facility_id)->where('status','SUBMITTED')
   ->update('prescriptions',array('status'=>$decision,'review_note'=>$note?:NULL,
    'pharmacist_user_id'=>$this->user_id,'reviewed_at'=>date('Y-m-d H:i:s')));
  if ($this->db->affected_rows()!==1) show_error('Resep sudah berubah status.',409);
  $this->audit('REVIEW_'.$decision,'prescriptions',$rx->id);
  redirect('pharmacy');
 }
}
