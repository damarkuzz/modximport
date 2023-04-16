<?
#Help
  ##$page = $modx->getObject('modResource', 29);
  ##print_pre($page->getTVValue('image_gallery')); получить значение TV-поля галереи
#
	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '-1');
define('MODX_API_MODE', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/index.php');
$modx = new modX();
$modx->initialize('web');

include 'functions.php';

#Прасинг таблицы
$str = file_get_contents('https://docs.google.com/spreadsheets/u/0/d/1Dm4MMBb14PVOxWXtRfB0RylWhCwkawEcAJz6RvuZMIg/export?format=tsv&id=1Dm4MMBb14PVOxWXtRfB0RylWhCwkawEcAJz6RvuZMIg&gid=0');
$str = str_replace("\t", '|', $str);
file_put_contents('list.csv', $str);

 if (($csvFile = fopen("list.csv", "r")) !== FALSE) 
  {
  
    while (($data = fgetcsv($csvFile, 0, "|")) !== FALSE) 
    {        
      $array[] = $data; 
    }
  
    fclose($csvFile);
  }

unset($array[0]);

#Конфигурация импорта
$import_mode = "create";
//$import_mode = "update";

foreach($array as $key=>$arr) {
	$title = $arr[0];
	$description = $arr[2];
	$price = $arr[3];
	$yt_link = $arr[14];
	$parent_name = $arr[1];
	$uid = $arr[18];
	$photo_array = [$arr[4], $arr[5], $arr[6], $arr[7], $arr[8], $arr[9], $arr[10], $arr[11], $arr[12], $arr[13]];
	
	switch($import_mode) {
		case "create":
			createDocument($title, $description, $uid, $parent_name, 0, $photo_array, $price, $yt_link);
			break;
		case "update":
			$refresh_photo = true;
			updateDocument($title, $description, $uid, $parent_name, 0, $photo_array, $price, $yt_link, $refresh_photo);
			break;
	}
	
	// if($key > 4) { 
		// break;
	// }
}