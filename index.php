<?php
/*********************************
 * @Author  WebSlide Studio
 * @Site    http://www.webslide.hu 
 * @Date    2012-11-05
 * @Version 0.1a
 *  
 * Open source project
 ********************************/
 
 
     
header('Content-Type: text/html; charset=utf-8');
	
require_once('setup.php');
	 
$functions 			= 	array();
$functions_error 	= 	array();
$hasProblem 		= 	false;
$fileList 			=	array();
$csv_header 		= 	array('#', 'File Name', 'Warning', 'sample code');
$log_dir			=	'C:/wamp/www/list/log/';
//scanned directory $argv[1] absout path C:\wamp\www\check
$dir = isset($argv[2])?$argv[2]:'';
function buildErrorArray()
{
	global $deprecated_functions,$functions,$functions_error;
	
	$i = 0;
	foreach ($deprecated_functions AS $key => $value)
	{
		$functions[$i] = $key.'(';
		$functions_error[$i] = $value;
		$i++;
		$functions[$i] = $key.' (';
		$functions_error[$i] = $value;
		$i++; 
	}
}	


function readFileCheck($file)
{
	global $functions,$functions_error;

	$handle = @fopen($file, "r");

	if ($handle) 
	{
		$i = 0;
		$stop = 0;
		while (!feof($handle))
		{ 
			$i++;
			$buffer = fgets($handle, 4096); 
			$originalbuffer[$i] = $buffer;
			
			if ($i>3)
			{
				unset($originalbuffer[$i-3]);
			}
				if (str_replace('/*','',$buffer)!=$buffer)
					$stop = 1;
				if (str_replace('*/','',$buffer)!=$buffer)
				{
					$buffer = explode('*/',$buffer);
					$buffer = $buffer[count($buffer)-1];
				        $stop = 0;
				}
				if ($stop==0)
				{ 
					if (str_replace('//','',$buffer)!=$buffer)
					{
						$buffer = explode('//',$buffer);
						$buffer = $buffer[0];
					}
					if (str_replace('##','',$buffer)!=$buffer)
					{
						$buffer = explode('//',$buffer);
						$buffer = $buffer[0];
					}
					
					foreach ($functions AS $key => $value)
					{ 
						$check = preg_replace('/[\s]+'.$value.'[\s]*\()/',"<span style=\"color: #FF99FF; background-color: #666;\">$0</span>",$buffer);
						if ($check!=$buffer)
						{
							$save = $originalbuffer[$i];
							$originalbuffer[$i] =  strip_tags($check);
							$error[$i] = array($functions_error[$key],$originalbuffer);
							$originalbuffer[$i] = $save;
						} 
					}	
				}
		}
		fclose($handle);                  
	}
	if (isset($error))
		writeProblem($file,$error);
}
//create csv log file files
function write_log($warning){
	static $xls_file_name  = '';
	global $csv_header,$log_dir;
	if($xls_file_name == null){
		$file_name  = time()."_log.xls";
	}else{
		$file_name = $xls_file_name;
	}
	
	$fp = fopen($log_dir.$file_name, 'a+');	
	if(!$fp){
		
		echo "Unable to create log file permission issue!";
		exit;
	}
	fputcsv($fp, $warning, "\t", '"');
	$xls_file_name = $file_name;	
	fclose($fp);	
	
}
//return array of deprecated function warning 
function writeProblem($file,$error)
{ 
	global $hasProblem;
	$records  = array();
	$records['file'] = $file;
	$hasProblem = true;
	$file = str_replace('//','/',$file);
	foreach ($error AS $key => $value)
	{
		$records['message'] = 'Line '.$key.': '.$value[0];
		$i = 2;
		$sample_code = '';
		foreach ($value[1] AS $kk => $kvalue)
		{
			$sample_code = '('.$key-$i.')'.' '.$kvalue;
			$i--;
		}
		
		$records['sample_code'] = $sample_code;  
		write_log($records);			
	}
	
 	
}



function listFiles($directory)
{	
		
	if (realpath($directory)== str_replace('index.php','',$_SERVER['SCRIPT_FILENAME']))
		return ;

	if(is_dir($directory))
	{
		$direc = opendir($directory);
        	while(false !== ($file = readdir($direc)))
		{
           
            		if($file !="." && $file != "..")
			{
                		if(is_file($directory."/".$file))
				{
					if (substr($file,-4)=='.php')
					{
						outAjax($directory.'/'.$file);
					}
				}
                		else if(is_dir($directory."/".$file))
				{
                    			listFiles($directory."/".$file);
                		}
                	}
            	}
    	}
	closedir($direc);
    return ;
}


function outAjax($file)
{
	global $fileList;
	$fileList[] = $file;
}
function read_file($fileList){
	$i =1;
	echo PHP_EOL.'Scaning ........';
	foreach($fileList as $files){
		readFileCheck($files);
		//echo PHP_EOL.' Files scanned...'.PHP_EOL.
		$i++;
	}
	echo PHP_EOL.'Scanned completed .';
}
//read files set array
if($dir != null){
	listFiles($dir);
	$out_put = '';
	if(count($fileList)>0){
		echo  "Script will scan deprecated functions between version 5.2 to 5.5  in total files is ".count($fileList).PHP_EOL;
		//scan files
		buildErrorArray();
		//set header in xls file
		write_log(array('File name','Warning Message','Sample code'));
		read_file($fileList);
	}else{
		echo PHP_EOL."No files found in this directory";
		exit;
	}
}else{
	echo "Please pass directory path";
	exit;	
}	
	

?>
