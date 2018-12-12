<?php
set_time_limit(100000);
/**
 * Model : Dynamic model for table
 * Created by Johan	 
 */
require_once ('config.php');

class Model
{
	/**
	 * constructor
	 * @param :  name(string) 
	 */
	public function __construct($name)
	{
		$this->Name = $name;  // table name		
		$this->Dbcon = null;
		$this->fields = array();  // schema
		$this->types = array();	  // schema
		$this->values = array();  // values
		$this->isHeader = true;
		$this->Query1 = "";
		$this->Query2 = "";
	}


	/**
	 * func name: format
	 * @param 	: text(string)
	 * @return 	: string
	 */
	function format(){
		for ($i = 0 ;  $i < count($this->values); $i++){
			$this->values[$i] = $this->Dbcon->real_escape_string(str_replace("'", "''", str_replace('\n', '', str_replace('"', '', $this->values[$i]))));
		}		
	}

	/**
	 * func Name: runQuery
	 * @param 	: query(string)
	 * @return 	: none
	 */

	public function runQuery($query){
		$result = $this->Dbcon->query($query);		
		if (!$result){
			echo $query."<br>";
			echo "Query Error!"."<br>";			
		}
	}

	/**
	 *getDate
	 *@param : str(string)
	 *@return : date(string)
	 */
	public function getDate($str){
		$time = strtotime($str);
		$newformat = date('Y-m-d',$time);
		return $newformat;
	}

	/**
	 * getValue
	 * @param: none
	 * @return : none
	 */	
	function getValues(){			
		for ($i = 0 ; $i < count($this->types); $i++){
			if (strpos($this->types[$i], 'INT')!== false || strpos($this->types[$i], 'DOUBLE') !== false)
				$this->values[$i] = floatval($this->values[$i]);				
			else if(strpos($this->types[$i], 'DATE')!==false)
				$this->values[$i] = $this->getDate($this->values[$i]);				
		}
	}
	/**
	 * func Name: checkTableExists
	 * @param 	: tableName(string)
	 * @return 	: boolean
	 */	
	public function checkTableExists(){		
		$querydb = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;				
		$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS) or die("Could not connect!". mysqli_connetion_error());	
		
		if (mysqli_query($con, $querydb)) {
			$this->Dbcon = new mysqli(DB_HOST, DB_USER,DB_PASS,DB_NAME); // db instance
			if(mysqli_connect_error()) {
				echo "My sql connection Error!";
				exit;
			}
		} else {
		    echo "Error: " . $sql . "<br>" . mysqli_error($con);
		    exit;
		}		
		$query  = "SELECT COUNT(*) FROM information_schema.tables WHERE table_name = '{$this->Name}'";
		$result = $this->Dbcon->query($query);
		$count  = $result->num_rows;
		while($r=$result->fetch_assoc()) {
		    if ($r['COUNT(*)'] == 1)
		    	return true;
		   	else
		   		return false;
		}
	}

	function setQuery($query1, $query2){
		$this->Query1 = $query1;
		$this->Query2 = $query2;
	}

	function importCSV(){
		if ($this->isHeader){
			$this->runQuery($this->Query1);
		}else{
			$this->runQuery($this->Query2);
		}
	}

	/**
	 * func Name: creatTable
	 * @param 	: none
	 * @return 	: none
	 */	
	public function creatTable(){		

		$query = "CREATE TABLE `{$this->Name}` (`{$this->fields[0]}` int(10) NOT NULL ,";
		for ($i = 1; $i < count($this->fields) ; $i++){
			$query .= "`{$this->fields[$i]}` {$this->types[$i]}, ";				
		}
		// if ($this->Name == 'IPGOLD204' || $this->Name=='IPGOLD202'|| $this->Name=='IPGOLD207'|| $this->Name=='IPGOLD208'){
		// 	$query .= "PRIMARY KEY (`{$this->fields[0]}`, `{$this->fields[1]}`, `{$this->fields[2]}`) ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";	
		// }else if ($this->Name=='IPGOLD220'){
		// 	$query .= "PRIMARY KEY (`{$this->fields[0]}`, `{$this->fields[1]}`) ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";	
		// }
		//else 
		$query .= "PRIMARY KEY (`{$this->fields[0]}`) ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";		
		if (!$this->checkTableExists($this->Name)){
			$this->runQuery($query);			
		}
		$this->runQuery("SET sql_mode='';");
	}

	/**
	 * func Name: save
	 * @param 	: none
	 * @return 	: none
	 */	
	public function save(){
			
		// check record is already existed or not
		if (strpos($this->types[0], "TEXT")!==false){			
			$query = "SELECT * from  `{$this->Name}`  WHERE `{$this->fields[0]}`= '{$this->values[0]}'";
		}
		else
			$query = "SELECT * from  `{$this->Name}`  WHERE `{$this->fields[0]}`= {$this->values[0]}";		
		
		$res = $this->Dbcon->query($query);
		
		if ($res->num_rows > 0){
			// true, update record
			$query = "UPDATE `{$this->Name}` SET ";
			for ($i = 1 ; $i < count($this->fields); $i++){
				if (strpos($this->types[$i], 'INT') !== false || strpos($this->types[$i], 'DOUBLE') !== false)
					$query .= "`{$this->fields[$i]}` = {$this->values[$i]},";				
				else
					$query .= "`{$this->fields[$i]}` = '{$this->values[$i]}',";
				
			}

			if (strpos($this->types[0], 'TEXT')!==false)
				$query = substr($query, 0, strlen($query)-1) . " where `{$this->fields[0]}`='{$this->values[0]}'";
			else
				$query = substr($query, 0, strlen($query)-1) . " where `{$this->fields[0]}`={$this->values[0]}";
			$this->runQuery($query);
		}else{
			// false, insert new record
			$query = "INSERT into `{$this->Name}` (" . join(",", $this->fields) . ") VALUES ( ";
			for ($i = 0 ; $i < count($this->fields) ; $i++){
					if (strpos($this->types[$i], 'INT')!==false)
					$query .= "{$this->values[$i]},";
				else
					$query .= "'{$this->values[$i]}',";
			}
			$query = substr($query, 0, strlen($query)-1) . ")";
			$this->runQuery($query);
		}		
		// echo $query;
	}

	/**
	 * set
	 * @param : text(string)
	 * @return : none
	 */
	public function set($values){
		$this->values= $values;
		$this->format();
		$this->getValues();		
	}
}


/**
 * IPGOLD201
 */
class IPGOLD201 extends Model
{			

	function __construct()
	{
		parent::__construct('IPGOLD201');
		$this->types=array(
			"INT",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"DATE",
			"DATE",
			"TEXT",
			"INT(2) DEFAULT NULL",			
			"INT(5) DEFAULT NULL",			
			"INT(10) DEFAULT NULL",
			"INT(10) DEFAULT NULL",
			"INT(10) DEFAULT NULL",			
		);
		$this->fields = array(
			"tm_number",
			'type_of_mark_code',			
			'cpi_status_code',
			'live_or_dead_code',	
			'trademark_type',
			'madrid_application_indicator',
			'lodgement_date',
			'registered_from_date',
			'country',
			'Australian',
			'entity',
			'applicant_no',
			'lodgement_year',	
			'registered_from_year'			
		);
	}

	function setfilepath($path){
		$pa = str_replace('\\',"/",getcwd());
		$path = $pa . "/" . str_replace("\\","//", $path);		
		$query1 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD201` FIELDS TERMINATED BY ','  optionally enclosed by '".'"' ."'  LINES TERMINATED BY '".'\n'."' IGNORE 1 LINES;";
		$query2 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD201` FIELDS TERMINATED BY ','  optionally enclosed by '".'"' ."'  LINES TERMINATED BY '".'\n'."';";				
		parent::setQuery($query1, $query2);
	}
}

/**
 * IPGOLD202
 */
class IPGOLD202 extends Model
{
	
	function __construct()
	{
		parent::__construct('IPGOLD202');
		$this->types=array(
			"INT",
			"INT(10) DEFAULT NULL UNIQUE",
			"INT(10) DEFAULT NULL UNIQUE",
			"TEXT",
			"INT(2) DEFAULT NULL",
			"INT(2) DEFAULT NULL",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"INT(10) DEFAULT NULL",
			"DOUBLE  DEFAULT NULL",
			"DOUBLE  DEFAULT NULL",
			"INT(10) DEFAULT NULL",
			"TEXT",
			"INT(10) DEFAULT NULL",
			"TEXT",
			"TEXT",
			"TEXT",
			"INT(10) DEFAULT NULL",
			"INT(10) DEFAULT NULL",
			"TEXT",
			"TEXT",
			"INT(5) DEFAULT NULL",
		);

		$this->fields = array(
			"tm_number",
			"ipa_id",
			"fmr_ipa_id",
			"country",
			"australian",
			"entity",
			"name",
			"cleanname",
			"corp_desg",
			"state",
			"postcode",
			"lat",
			"lon",
			"sa2_code",
			"sa2_name",
			"lga_code",
			"lga_name",
			"gcc_name",
			"elect_div",
			"abn",
			"acn",
			"entity_type",
			"applicant_type",
			"big",
		);
	}
	function setfilepath($path){
		$pa = str_replace('\\',"/",getcwd());
		$path = $pa . "/" . str_replace("\\","//", $path);
		$query1 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD202` FIELDS TERMINATED BY ','  optionally enclosed by '".'"' ."'  LINES TERMINATED BY '".'\n'."' IGNORE 1 LINES;";
		$query2 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD202` FIELDS TERMINATED BY ','  optionally enclosed by '".'"' ."'  LINES TERMINATED BY '".'\n'."';";
		parent::setQuery($query1, $query2);
	}
}


/**
 * IPGOLD203
 */
class IPGOLD203 extends Model
{
	
	function __construct()
	{
		parent::__construct('IPGOLD203');
		$this->types=array(
			"INT",
			"INT(10) DEFAULT NULL",
			"TEXT",
			"TEXT",			
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"DATE",
			"DATE",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"DATE",
			"DATE",
			"DATE",
			"DATE",
			"DATE",			
			"INT(10) DEFAULT NULL",
			"INT(10) DEFAULT NULL",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"DATE",
			"INT(10) DEFAULT NULL",
			"TEXT",
			"INT(10) DEFAULT NULL",
			"INT(10) DEFAULT NULL",
			"INT(10) DEFAULT NULL",
			"DATE",			
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT"
		);
		$this->fields = array(
			"tm_number",
			"status_code",
			"status_code_desc",
			"type_of_mark_code",
			"expedite_flag_ind",
			"non_use_flag_ind",
			"cpi_status_code",
			"live_or_dead_code",
			"trademark_type",
			"lodgement_date",
			"PRIORITY_DATE__DIVISIONAL_DATE",			
			'kind_colour_ind',
			'kind_scent_ind',
			'kind_shape_ind',
			'kind_sound_ind',
			'acceptance_due_date',
			'sealing_due_date',
			'registered_from_date',
			'sealing_date',
			'renewal_due_date',
			'divisional_number',
			'part_assign_parent_number',
			'priority_number',
			'priority_country_code',
			'section45_application_code',
			'court_orders_ind',
			'ir_number_notify_date',
			'ir_number',
			'ir_number_extension_value',
			'part_transform_parent_number',
			'transform_ir_number',
			'transform_ir_extension_no',
			'ir_renewal_due_date',
			'act1955_reg_acpt_code',
			'revocation_acpt_pend_ind',
			'gs_assistance_ind',
			'lodgement_type_code',
			'madrid_application_indicator'
		);
	}

	function setfilepath($path){
		$pa = str_replace('\\',"/",getcwd());
		$path = $pa . "/" . str_replace("\\","//", $path);
		$query1 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD203` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."' IGNORE 1 LINES;";
		$query2 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD203` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."';";
		parent::setQuery($query1, $query2);
	}
}


/**
 * IPGOLD204
 */
class IPGOLD204 extends Model
{
	
	function __construct()
	{
		parent::__construct('IPGOLD204');
		$this->types=array(
			"INT",			
			"INT(10) DEFAULT NULL UNIQUE",
			"INT(10) DEFAULT NULL UNIQUE",
			"TEXT",
		);
		$this->fields = array(
			'tm_number',
			'class_code',
			'occ_num',
			'description_text'
		);
	}

	function setfilepath($path){
		$pa = str_replace('\\',"/",getcwd());
		$path = $pa . "/" . str_replace("\\","//", $path);
		$query1 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD204` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."' IGNORE 1 LINES;";
		$query2 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD204` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."';";
		parent::setQuery($query1, $query2);
	}
}


/**
 * IPGOLD206
 */
class IPGOLD206 extends Model
{
	
	function __construct()
	{
		parent::__construct('IPGOLD206');
		$this->types=array(
			"INT",			
			"INT(10) DEFAULT NULL",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
		);
		$this->fields = array(
			'tm_number',
			'self_filed',
			'name',
			'cleanname',
			'country',
			'state',
			'firm_id',
		);
	}

	function setfilepath($path){
		$pa = str_replace('\\',"/",getcwd());
		$path = $pa . "/" . str_replace("\\","//", $path);
		$query1 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD206` FIELDS TERMINATED BY ','  optionally enclosed by '".'"' ."'  LINES TERMINATED BY '".'\n'."' IGNORE 1 LINES;";
		$query2 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD206` FIELDS TERMINATED BY ','  optionally enclosed by '".'"' ."'  LINES TERMINATED BY '".'\n'."';";
		parent::setQuery($query1, $query2);
	}
}

/**
 * IPGOLD207
 */
class IPGOLD207 extends Model
{
	
	function __construct()
	{
		parent::__construct('IPGOLD207');
		$this->types=array(
			"INT",			
			"INT(10) DEFAULT NULL UNIQUE",
			"INT(10) DEFAULT NULL UNIQUE",
		);
		$this->fields = array(
			'tm_number',
			'report_no',
			'class_count'
		);
	}

	function setfilepath($path){
		$pa = str_replace('\\',"/",getcwd());
		$path = $pa . "/" . str_replace("\\","//", $path);
		$query1 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD207` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."' IGNORE 1 LINES;";
		$query2 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD207` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."';";
		parent::setQuery($query1, $query2);
	}	
}


/**
 * IPGOLD208
 */
class IPGOLD208 extends Model
{
	
	function __construct()
	{
		parent::__construct('IPGOLD208');
		$this->types=array(
			"INT",			
			"INT(10) DEFAULT NULL UNIQUE",
			"INT(10) DEFAULT NULL UNIQUE",
			"TEXT",
			"INT(10) DEFAULT NULL",
			"DATE",
			"DATE",
			"DATE",
			"DATE",
			"DATE",
			"DATE",
			"TEXT",
			"DATE",
			"DATE",
			"DATE",
			"TEXT",
			"DATE",
			"DATE",
			"DATE",
			"TEXT",
			"DATE",
			"DATE",
			"TEXT",
			"INT(10) DEFAULT NULL",
			"TEXT",
			"TEXT",
			"DATE",
			"DATE",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"TEXT",
			"INT(10) DEFAULT NULL",
			"TEXT",
			"TEXT",
			"DATE",
			"INT(10) DEFAULT NULL",
			"TEXT",
			"DATE",
			"DATE",
			"INT(10) DEFAULT NULL",
			"DATE",
			"TEXT",
			"DATE",
			"DATE",
			"INT(10) DEFAULT NULL",
			"INT(10) DEFAULT NULL",
			"TEXT",
			"TEXT",
			"DATE",
			"DATE",
			"DATE",
			"INT(10) DEFAULT NULL",
			"DATE",
			"INT(10) DEFAULT NULL",
			"DATE",
			"DATE"
		);
		$this->fields = array(
			'tm_number',
			'opp_seq_no',
			'opposition_status_code',
			'opposition_type',
			'opposition_code',
			'non_use_lodged_date',
			'opp_extension_date',
			'opp_lodged_date',
			'ev_support_extension_date',
			'ev_support_lodged_date',
			'ev_support_served_date',
			'ev_support_type',
			'ev_answer_extension_date',
			'ev_answer_lodged_date',
			'ev_answer_served_date',
			'ev_answer_type',
			'ev_reply_extension_date',
			'ev_reply_lodged_date',
			'ev_reply_served_date',
			'ev_reply_type',
			'withdrawal_lodged_date',
			'referred_date',
			'person_referred_code',
			'opp_act_year',
			'status_text',
			'prop_agent_msg_text',
			'opp_created_date',
			'opp_modified_date',
			'opp_status_line_1_text',
			'opp_status_line_2_text',
			'opp_status_line_3_text',
			'opp_status_line_4_text',
			'opp_status_line_5_text',
			'opp_status_line_6_text',
			'opp_status_line_7_text',
			'hearing_number',
			'hearing_type',
			'hearing_code',
			'decided_date',
			'decision_type',
			'pending_type',
			'resume_date',
			'appeal_lodged_date',
			'hearing_status_code',
			'deferred_date',
			'hear_stat_desc_text',
			'hear_created_date',
			'hear_modified_date',
			'opp_evi_seq_no',
			'opp_evidence_status',
			'opp_evidence_type',
			'opp_evidence_code',
			'amend_enter_date',
			'corro_lodgment_date',
			'ev_served_date',
			'amendment_no',
			'final_appeal_date',
			'opp_evi_act_year',
			'opp_evi_created_date',
			'opp_evi_modified_date'
		);
	}
	function setfilepath($path){
		$pa = str_replace('\\',"/",getcwd());
		$path = $pa . "/" . str_replace("\\","//", $path);
		$query1 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD208` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."' IGNORE 1 LINES;";
		$query2 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD208` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."';";
		parent::setQuery($query1, $query2);
	}	
}


/**
 * IPGOLD220
 */
class IPGOLD220 extends Model
{
	
	function __construct()
	{
		parent::__construct('IPGOLD220');
		$this->types=array(
			"INT",			
			"INT(10) DEFAULT NULL UNIQUE",
			"DATE",
			"TEXT",
			"TEXT",
			"DATE"
		);
		$this->fields = array(
			'tm_number',
			'ci_number',
			'approval_date',
			'ci_text',
			'header_tm_case_no',
			'last_amend_date'
		);
	}

	function setfilepath($path){
		$pa = str_replace('\\',"/",getcwd());
		$path = $pa . "/" . str_replace("\\","//", $path);
		$query1 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD220` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."' IGNORE 1 LINES;";
		$query2 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD220` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."';";
		parent::setQuery($query1, $query2);
	}
}


/**
 * IPGOLD221
 */
class IPGOLD221 extends Model
{
	
	function __construct()
	{
		parent::__construct('IPGOLD221');
		$this->types=array(
			"INT",			
			"INT(10) DEFAULT NULL",			
			"TEXT",
			"TEXT",
		);
		$this->fields = array(
			'tm_number',
			'occ_number',
			'endorsement_text',
			'endorsement_type'
		);
	}
	function setfilepath($path){
		$pa = str_replace('\\',"/",getcwd());
		$path = $pa . "/" . str_replace("\\","//", $path);
		$query1 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD221` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."' IGNORE 1 LINES;";
		$query2 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD221` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."';";
		parent::setQuery($query1, $query2);
	}	
}


/**
 * IPGOLD222
 */
class IPGOLD222 extends Model
{
	
	function __construct()
	{
		parent::__construct('IPGOLD222');
		$this->types=array(
			"INT",			
			"TEXT",
			"TEXT",
		);
		$this->fields = array(
			'tm_number',
			'trademark_text',
			'device_phrase_text'
		);
	}

	function setfilepath($path){
		$pa = str_replace('\\',"/",getcwd());
		$path = $pa . "/" . str_replace("\\","//", $path);
		$query1 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD222` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."' IGNORE 1 LINES;";
		$query2 = "LOAD DATA LOCAL INFILE '{$path}' REPLACE INTO TABLE `IPGOLD222` FIELDS TERMINATED BY ','  enclosed by '".'"' ."'  LINES TERMINATED BY '".'\r\n'."';";
		parent::setQuery($query1, $query2);
	}	
}