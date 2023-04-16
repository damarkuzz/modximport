<?
	ini_set('max_execution_time', 0);
	ini_set('memory_limit', '-1');
	
#Функции
function print_pre($value) {
	echo '<pre>';
	print_r($value);
	echo '</pre>';
}

function addParent() {
	
}

function createUnicalAlias($resource, $title) {
	$alias = $resource->cleanAlias($title);
	$modx = new modX();
	$count = count($modx->getCollection('modResource', array('alias' => $alias)));
	
	if ($count > 0) { 
		return createUnicalAlias($resource, $title.rand(0, 10));
	} else {
		return $alias;
	}
}

function uploadPhoto($url, $id, $refresh_photo = false) {
	$modx = new modX();
	$filename = basename($url);
	$path = MODX_BASE_PATH.'img/catalog/'.$id;
	$file_path = MODX_BASE_PATH.'img/catalog/'.$id.'/'.$filename;
	
	if(!file_exists($path)) {
		mkdir($path, 0755, true);
	} 
	if($refresh_photo === true) {
		unlink($path);
	}
	
	$fp = fopen($file_path, "w+");
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_exec($ch);
	$st_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	fclose($fp);
	
	return str_replace(MODX_BASE_PATH, '', $file_path);
}

function getResourceByTvValue($tv_name, $tv_value) {
	$modx = new modX();
	$modx->initialize('web');
	$where = $modx->newQuery('modResource');
	$where->leftJoin('modTemplateVarResource', 'TemplateVarResources');
	$where->leftJoin('modTemplateVar', 'tv', "tv.id=TemplateVarResources.tmplvarid");
	$where->where(array(
		array(
			'tv.name'   => $tv_name, // Имя TV
			'TemplateVarResources.value'    => $tv_value // Значение TV
		)
	));
	$resources = $modx->getObject('modResource', $where);
	return $resources;
}

function getObjectByCriteria($array) {
	$modx = new modX();
	$modx->initialize('web');
	$resource = $modx->getObject('modResource', $array);
	return $resource;
}

function createDocument($title, $description, $uid, $parent_name, $is_published = 0, $photo_array, $price, $yt_link, $refresh_photo = false) {
	
	$parent_resource = getObjectByCriteria(
		[
			'pagetitle' => $parent_name,
			'parent' => 2
		]
	);
	
	
	if(!empty($parent_resource)) {
		print_r($parent_resource->get('pagetitle'));
		$is_resource_exists = getResourceByTvValue('uid', $uid);
		
		if(!empty($is_resource_exists)) {
			echo "Ресурс <b>{$title}</b> уже существует<br>";
			return null;
		} else {
			// Создаем новый ресурс			
			$modx = new modX();
			$modx->initialize('web');
			
			$parent_resource_id = $parent_resource->get('id');
			
			$resource = $modx->newObject('modResource');                        
			$resource->set('template', 5);             // Назначаем ему нужный шаблон
			$resource->set('isfolder', 0);             // Указываем, что это не контейнер   
			$resource->set('published', $is_published);            // Публикация
			$resource->set('hidemenu', 1);            // Неопубликован в меню
			$resource->set('createdon', time());       // Время создания
			$resource->set('pagetitle', $title);        // Заголовок
			$resource->set('alias', createUnicalAlias($resource, $title));   // Псевдоним
			$resource->setContent($description);           // Содержимое
			$resource->set('parent', $parent_resource_id);              // Родительский ресурс
			$resource->set('template', 3);              // Шаблон
			$resource->save();
			
			$docId = $resource->get('id');
			$tvDoc = $modx->getObject('modResource',  $docId);
			
			if ($tvDoc) {
				//UID
				$tvDoc->setTVValue(6, $uid);  // UID
				$tvDoc->setTVValue(5, $price);  // price
				$tvDoc->setTVValue(7, $yt_link);  // youtube link
				
				// //Photos
				// $counter = 0;
				// $photo_arr = [];
				// foreach($photo_array as $photo) {
					// if(empty($photo)) {
						// continue;
					// }
					// $counter++;
					// $uploaded_photo = uploadPhoto($photo, $docId);
					// $tvPhoto = [
						// 'MIGX_id' => $counter,
						// 'image' => $uploaded_photo,
					// ];
					// $photo_arr[] = $tvPhoto;
					
				// }
				// $tvDoc->setTVValue('image_gallery', json_encode($photo_arr));
				$modx->cacheManager->clearCache();
				$tvDoc->save();
			}
			
			echo "Ресурс <b>{$title} <i>{$uid}</i></b> создан!<br>";
		}
	} else {
		echo "Родительского ресурса с заголовком {$parent_name} не существует. Ресурс <b>{$title} <i>{$uid}</i></b> не может быть создан!<br>";
		var_dump($modx);
		echo '111';
		//exit;
	}
}

function updateDocument($title, $description, $uid, $parent_name, $is_published = 0, $photo_array, $price, $yt_link) { 
	$parent_resource = getObjectByCriteria(
		[
			'pagetitle' => $parent_name,
			'parent' => 2
		]
	);
	
	if(!empty($parent_resource)) {
		$is_resource_exists = getResourceByTvValue('uid', $uid);
		if(!empty($is_resource_exists)) {
			$modx = new modX();
			$modx->initialize('web');
			
			$resource = $modx->getObject('modResource', $is_resource_exists->get('id'));
			
			$resource->set('published', $is_published); //публикация
			$resource->set('pagetitle', $title); //заголовок
			$resource->setContent($description); //описание
			//Photos
				$counter = 0;
				$photo_arr = [];
				foreach($photo_array as $photo) {
					if(empty($photo)) {
						continue;
					}
					$counter++;
					$uploaded_photo = uploadPhoto($photo, $docId);
					$tvPhoto = [
						'MIGX_id' => $counter,
						'image' => $uploaded_photo,
					];
					$photo_arr[] = $tvPhoto;
					
				}
				$resource->setTVValue('image_gallery', json_encode($photo_arr));
			//
			$resource->save();
			echo 'Ready!';
		} else {
			echo "Ресурс не обновлен. Ресурса с UID = {$uid} не существует!<br>";
		}
	} else {
		echo "Родительского ресурса с заголовком {$parent_name} не существует<br>";
	}
}