<?php
class AthenaDB extends CI_Model {
  /**
   * [__construct AthenaDB Constructor]
   */
  public function __construct() {
    $this->load->database();
    define("UNIQUE_KEY_FAIL", 1062);
    define("FOREIGN_KEY_FAIL", 1452);
  }
  /**
   * [verifyHandle verify if handle code exists in the database.]
   * @param  [string] $handle [10 digit string handle code.]
   * @return [string]         [1 if exists and 0 if not.]
   */
  public function verifyHandle($handle) {
    $query = $this->db->get_where("staffs", array("handle"=>$handle));
    if (count($query->row_array()) > 0) {
      return "1";
    }
    return "0";
  }
  /**
   * [nullifyHandle clears the handle code from the staff record in the staffs
   * table]
   * @param  [string] $handle [10 string digit handle code.]
   * @return [array]         [array(0)['wipe'] for result of handle wipe and array(1)['log'] for
   *                         result of handle logging]
   */
  public function nullifyHandle($handle) {
    $query = $this->db->get_where("staffs", array("handle"=>$handle));
    if (count($query->result()) > 0) {
      $data = array("handle" => "");
      $this->db->set($data);
      $this->db->where("handle", $handle);
      $o1 = $this->toIntStr($this->db->update("staffs", $data));
      date_default_timezone_set("Africa/Lagos");
      $data = array(
        "handle"=>$handle,
        "date_used" => date("Y-m-d h:i:s"));
      $o2 = $this->db->insert("used_handles", $data);
      return array("wipe"=>$this->toIntStr($o1), "log"=>$this->toIntStr($o2));
    } else {
      return array("wipe"=>0, "log"=>0);
    }
  }
  /**
   * [getStaffInfoByHandle gets UserInfo by matched handle]
   * @param  [string] $handle [10 digit string handle code]
   * @return [array]         [associative array of data with keys matching the
   *                         database design for staffs table.]
   */
  function getStaffInfoByHandle($handle) {
    $query = $this->db->get_where("staffs", array("handle"=>$handle));
    return $query->result()[0];
  }
  /**
   * [verifyKey checks whether security id mathces the uses. this function is
   * used for access control.]
   * @param  [string] $key [25 characters key]
   * @param  [string] $uid [user id]
   * @return [string]      [1 on match, 0 if not.]
   */
  function verifyKey($key, $uid) {
    $query = $this->db->get_where("staffs", array("security_id"=>$key, "id"=>$uid));
    if (count($query->result()) > 0) {
      return "1";
    }
    return "0";
  }
  /**
   * [getTable gets the whole spcified tableand returns them as a json array]
   * @param  [string] $table [name of table]
   * @return [json string]        [rows associative array]
   */
  function getTable($table) {
    $query = $this->db->get($table);
    return $query->result();
  }
  /**
   * [getVersion returns the version number of the specified table based on
   * records fromthe sync table.]
   * @param  [string] $table [table name]
   * @return [int]        [table version number]
   */
  function getVersion($table) {
    $query = $this->db->get_where("sync", array("name"=>$table));
    $row = $query->result();
    if (count($row) > 0) {
      return $row[0]->version;
    }
    return -1;
  }
  /**
   * [flagTaken flags the taken column of sync table in the row that matches
   * name of table given.]
   * @param  [string] $table [table name]
   * @param  [bool] $bool  [description]
   * @return [type]        [description]
   */
  function flagTaken($table, $tinyInt) {
    $data = array("taken" => $tinyInt);
    $this->db->set($data);
    $this->db->where("name", $table);
    return $this->toIntStr($this->db->update("sync", $data));
  }
  /**
   * [incrementVersion function for upgrading table version in sync table]
   * @param  [string] $table [table name]
   */
  function incrementVersion($table) {
    $query = $this->db->get_where("sync", array("name"=>$table));
    $row = $query->result()[0];
    if ($row->taken == 1) {
      $data = array(
        "taken" => 0,
        "version" => ++$row->version);
      $this->db->set($data);
      $this->db->where("name", $row->name);
      return $this->db->update("sync", $data);
    }
    return false;
  }
  /**
   * [insertData model function for simple insertion of data to a specified
   * table]
   * @param  [string] $table [table name]
   * @param  [string-array(assoc)] $data  [data to insert to columns specified
   *                                      by associative key]
   * @return [string]        [1 on success, 2 on unique constraint fail, 3 on
   *                         foreign key constraint fail and 4 on unknown error]
   */
  function insertData($table, $data) {
    $debug = $this->db->db_debug;
    $this->db->db_debug = false;
    if ($this->db->insert($table, $data)) {
      return "1";
    } else {
      $error = $this->db->error();
      $this->db->db_debug = $debug;
      if ($error['code'] == UNIQUE_KEY_FAIL) {
        return "2";
      } elseif ($error['code'] == FOREIGN_KEY_FAIL) {
        //return "3";
        print_r($error);
      } else {
        return "4";
      }
    }
  }
  function deleteData($table, $where, $val) {
    $this->db->where($where, $val);
    return $this->toIntStr($this->db->delete($table));
  }
  /**
   * [updateData model function for modifying column data of a specified table
   * on row matching the specified $where array condition]
   * @param  [string] $table [table name.]
   * @param  [type] $where [array where index 0 is column name and index 1
   *                       is the value of the column for which condition data
   *                       will be updated]
   * @param  [string-array(assoc)] $data  [associative array to modify row
   *                                      affected by where condition.]
   * @return [boolean]        [1 on success, 2 on unique constraint fail, 3 on
   *                          foreign key constraint fail and 4 on unknown error]
   */
  function updateData($table, $where, $data) {
    $this->db->set($data);
    $this->db->where($where[0], $where[1]);
    $debug = $this->db->db_debug;
    $this->db->db_debug = false;
    if ($this->db->update($table, $data)) {
      return "1";
    } else {
      $error = $this->db->error();
      $this->db->db_debug = $debug;
      if ($error['code'] == UNIQUE_KEY_FAIL) {
        return "2";
      } elseif ($error['code'] == FOREIGN_KEY_FAIL) {
        return "3";
      } else {
        return "4";
      }
    }
  }
  /**
   * [toIntStr Utility function to convert boolean to string.]
   * @param  [boolean] $bool [boolean value.]
   * @return [string]       [string 1 for true and string 0 for false.]
   */
  private function toIntStr($bool) {
    return $bool ? "1" : "0";
  }
  // private function toInt($bool) {
  //   return $bool ? 1 : 0;
  // }
  function verifyModule($mid) {
    $module = $this->db->get_where("modules", array('id'=>$mid));
    return $this->toIntStr(count($module->result()) == 1);
  }
  function getModulePack() {
    $this->db->select("id, hash, mid, security_id");
    $staffsBio = $this->db->get("staffs")->result();
    $this->db->select("id, hash, mid");
    $studentsBio = $this->db->get("students")->result();
    $pack = array(
      "staffs"=>$staffsBio,
      "students"=>$studentsBio
    );
    return $pack;
  }
  function getLectureHallForModule($mid) {
    $this->db->select("hall_id");
    $hall = $this->db->get_where("modules", array("id"=>$mid))->result();
    if (count($hall) > 0) {
      return $hall[0]->hall_id;
    }
    return "0";
  }
  function getModuleName($mid) {
    $this->db->select("name");
    $module = $this->db->get_where("modules", array("id"=>$mid))->result();
    if (count($module) > 0) {
      return $module[0]->name;
    }
    return "0";
  }
  function logModuleStats($mid, $nUsed, $batteryLevel) {
    date_default_timezone_set("Africa/Lagos");
    $data = array(
      "last_time_sync"=> date("Y-m-d h:i:s"),
      "sessions_used"=>$nUsed,
      "last_battery_level"=>$batteryLevel
    );
    $this->db->set($data);
    $this->db->where("id", $mid);
    $this->db->update("modules", $data);
    if ($this->db->affected_rows() == "1") {
      return "1";
    } else {
      return "0";
    }
  }
}
?>
