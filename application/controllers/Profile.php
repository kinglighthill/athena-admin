<?php
class Profile extends CI_Controller {

  function __construct() {
    parent::__construct();
    $this->load->helper("url");
  }

  function index() {
    $this->load->view("header");
    $this->load->view("navigation");
    $this->load->view("content-start");
    $this->load->view("student-profile");
    $this->load->view("content-end");
    $this->load->view("footer");
  }
}
?>
