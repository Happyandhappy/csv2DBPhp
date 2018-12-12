<?php

	set_time_limit(100000);
	require_once('Model.php');
	
	// download zip file from url
	function PullZip($url){		
		$destination = "ipgold". uniqid(time(), true) .".zip";
		$fh = fopen($destination, 'w');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_FILE, $fh); 
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // this will follow redirects
		curl_exec($ch);
		curl_close($ch);
		fclose($fh);
		chmod($destination, 0777);
		return $destination;
	}

	function deleteDir($dirPath) {
	    if (! is_dir($dirPath)) {
	    	return;
	        throw new InvalidArgumentException("$dirPath must be a directory");
	    }
	    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
	        $dirPath .= '/';
	    }
	    $files = glob($dirPath . '*', GLOB_MARK);
	    foreach ($files as $file) {
	        if (is_dir($file)) {
	            deleteDir($file);
	        } else {
	            unlink($file);
	        }
	    }
	    rmdir($dirPath);
	}
	
	// Extract csv files from zip
	function Unzip($file, $path){
		$zip = new ZipArchive;
		$res = $zip->open($file);
		if ($res === TRUE) {
		  $zip->extractTo($path);
		  $zip->close();
		  echo 'extract all!<br>';
		} else {
		  echo 'error!<br>';
		}
	}

	// get all file name list in directory
	function getCSVfilelist($dir){
		$list = scandir($dir);
	    return array_slice($list, 2, count($list));
	}

	# Unit to store splitted file data to mysql
	function CSV2DB_Unit($file, $model){
		echo $file . "<br>";
		$model->creatTable();
		$model->setfilepath($file);
		$model->importCSV();
	}
	
	function splitCSV($filepath){		
	    ### delete temp files and create new temp files
	    $dirName = 'temp';
	    if (is_dir($dirName))
	    	deleteDir($dirName);	    
		mkdir($dirName);
		chmod($dirName, 0777);		
	    $names = array();
	    $name = 0;


		$file = fopen($filepath, 'r');
		$file_out = fopen($dirName . "/" . (string)$name, "a");
		array_push($names, $dirName."/".(string)$name);
		$cnt = 0;

		while (($line = fgets($file)) !== FALSE){			
			$cnt++;
			fwrite($file_out,$line);
			if ($cnt > 100000){
				$cnt = 0;
				$name++;
				fclose($file_out);				
				$file_out = fopen($dirName . "/" . (string)$name, "a");
				array_push($names, $dirName."/".(string)$name);
			}
		}

		fclose($file_out);
		fclose($file);
		foreach ($names as $name){
			chmod($name, 0777);
		}
		
		return $names;		
	}

	function CSVtoDB($dir, $csv, $model){		
		$filePath = $dir . "/" . $csv;
		$names = splitCSV($filePath);
		foreach ($names as $key => $value) {			
			CSV2DB_Unit($value, $model);
			$model->isHeader = false;			
		}			
	}

	function getModifiedDate($filename){
		return date("Y-m-d H:i:s", filemtime("./" . CSV_DIR . "/" . $filename));
	}

	function getModifiedfileList($filelist){
		$mfilelist = array();
		$pastArr = array();
		if (file_exists(LOG_FILE)){
			$lines = file(LOG_FILE);
			foreach ($lines as $line) {
			    $vals = explode(",", $line);
			    if (count($vals)==2)
			    	$pastArr[$vals[0]] = $vals[1];
			}
		}
		$curArr = array();
		foreach ($filelist as $file) {
			$curArr[$file] = getModifiedDate($file);
		}

		// var_dump($pastArr);
		foreach ($curArr as $key => $value) {
			if (isset($pastArr[$key])){
				if (strcmp(trim($pastArr[$key]),trim($curArr[$key]))){
					echo $pastArr[$key] . " , " . $curArr[$key]. "<br>";
					array_push($mfilelist, $key);	
				}
			}else{
				array_push($mfilelist, $key);
			}
		}
		$file = fopen(LOG_FILE, "w");
		foreach ($curArr as $key => $value) {
			
			fwrite($file, "{$key},{$value}\n");
			// fwrite($file, $key.",".$value.'\n');
		}
		fclose($file);
		return $mfilelist;
	}

	function main(){
		
		// pull zip file from server
		$zipfile = PullZip(ZIP_URL);		
		// create folder
		if (!is_dir(CSV_DIR))mkdir(CSV_DIR, 0777);
		chmod(CSV_DIR, 0777);
		// extract zip file 
		Unzip($zipfile,'./'.CSV_DIR);		
		
		// get all csv file names 
		$filelist = getCSVfilelist('./'.CSV_DIR);
		foreach ($filelist as $file){
			chmod(CSV_DIR . '/' . $file, 0777);
		}

		// import csv data to db
		for ($i = 0; $i < count($filelist); $i++){
			$modelName = str_replace("IPGOLD", '', explode(".",$filelist[$i])[0]);
			$model = null;
			// echo $modelName;exit();
			switch ($modelName) {
				case '201':
					$model = new IPGOLD201();
					break;
				case '202':
					$model = new IPGOLD202();
					break;
				case '203':
					$model = new IPGOLD203();
					break;
				case '204':
					$model = new IPGOLD204();
					break;			
				case '206':
					$model = new IPGOLD206();
					break;
				case '207':
					$model = new IPGOLD207();
					break;
				case '208':
					$model = new IPGOLD208();
					break;
				case '220':
					$model = new IPGOLD220();
					break;
				case '221':
					$model = new IPGOLD221();
					break;
				case '222':
					$model = new IPGOLD222();
					break;

				default:
					break;
			}
			if (isset($model)){
				CSVtoDB('./'.CSV_DIR, $filelist[$i], $model);
				echo $filelist[$i]. " Done!<br>";
			}
		}
	}

	main();
