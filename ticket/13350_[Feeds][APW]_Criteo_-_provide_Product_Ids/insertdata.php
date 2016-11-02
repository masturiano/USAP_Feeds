<?php

/*
 * Feed Generator for autopartswarehouse Froogle and Google Base
*/
error_reporting(E_ALL);
ini_set("memory_limit", "4092M");

include_once('includes/config.inc.php');
$excludes = array();

/* <Get excludes> */
	$sql = "select sku from image where image_status = 1";
	$result = $db_queries->processQuery($sql);
	foreach($result as $row){
		$excludes[] = $row['sku'];
	}

	// $excludes = array('A152140035', 'A152140507', 'A152190020', 'A152190044', 'A152435008'); // This line is for testing
/* </Get excludes> */

/*set to true to enable mysql logging*/
$db_queries->log_queries = false;
$iRecCount 	= $db_queries->getRecCount();
$iLimit 	= 100000;
#$iLimit 	= getLimitArg();

/*$iRecCount 	= 1000;
$iLimit 	= 1000;*/

$iLoops 	= ceil($iRecCount/$iLimit);
$iQASRec 	= 50; //first 50 line for QA
$iLine 		= 0;
$apwcid 	= 'froogle'; 

/*
 * Table Name
*/

$tableName2 = "master_template";
$tableName = getTableArg();

/*
 * Insert data
*/
genLogs("Start Insert Data");

for ($i=0;$i<$iLoops;$i++) {
	
    genLogs("Inserting Data Batch #...".$i);
	$aProducts = $db_queries->getCriteoProducts($i*$iLimit, $iLimit);	
		
	//print_r($aProducts);
	//exit();	
	if (count($aProducts)>0) {
	    $o     = 0;
	    $rQ1   = 123; 
	    $rQ2   = 180;
	    $oo = 0;
		foreach ($aProducts as $row) {
			if(!in_array($row['part_number'], $excludes)){ //This line is added to exclude images with placeholder
				$image2 = 'http://images.apwcontent.com/is/image/Autos/'.(strtolower($row['part_number'])).'_1?$thumbs200$';

				$image2 = 'http://images.apwcontent.com/is/image/Autos/'.(strtolower($row['part_number'])).'_1?$thumbs200$';

	                        $image3 = 'http://images.apwcontent.com/is/image/Autos/'.(strtolower($row['part_number'])).'_1';

				$gnrtdChars = newChars(7);
		    		$gnrtdChars2 = newChars(7);
	    		
	    	
				$newMPN = $row['display_number'];
				$part	= $row['part'];
				$brand 	= $row['brand'];
				$SKU    = $row['part_number'];
				$make   = $row['make_name'];
				$model  = $row['model_name'];
				$universal = $row['universal'];
				
				
				//echo 'make'.$row['make_name'];
				
	    	            
	      	   // $apwID   = str_replace(' ', '', $row['brand']).$row['part_number'];
		    $apwID   = str_replace(' ', '', $row['brand']).$row['part_number'] . $row['make_name'] . $row['model_name'];
	      	    $apwName = $brand.' '.$part;
				//$saleing = "?003=27846644";
				if ($row['universal']=='1') {
					$apwProductURL = 'http://www.autopartswarehouse.com/sku/Univ/'.encodeWithCase($row['brand']).'/'.
							encodeWithCase($row['part']).'/'.encodeWithCase($row['part_number']).'.html';								
					
				} else {				
					
					$yrange = ($row['min_year']==$row['max_year']) ? "{$row['min_year']}" : "{$row['min_year']}{$row['max_year']}";
					
					$apwProductURL = 'http://www.autopartswarehouse.com/sku/'.encodeWithCase($row['brand']).'/'.encodeWithCase($row['part']).'/'.
							encodeWithCase($row['part_number']).'.html';								
					$title = ($row['min_year']==$row['max_year']) ? "{$row['min_year']}" : "{$row['min_year']}-{$row['max_year']}";
					$title .= " {$row['make_name']} {$row['model_name']} - {$row['brand']} {$row['part']}";
					if ($row['qualifier_types']!='' && $row['qualifier_values']!='') {
						$quals = genQuals($row['qualifier_types'], $row['qualifier_values'], $row['notes'],$row['part_number']);
						
						if (count($quals)>0) {
							if(isset($quals["series"])) { $title .= " - $quals[series]"; }
							if(isset($quals["location"])) { $title .= " - $quals[location]"; }					
						}
						
						$xtra = makeTitle($row['qualifier_types'], $row['qualifier_values']);
						if(strlen($xtra) > 0) { $title .= " - $xtra"; }
						
					}				
				}
				 
				$price           = $row['price1'];
				$retailPrice	 = $row['listprice1'];
				$categoryID1    =  $row['wpn_cat_name'];
				$categoryID2    =  $row['wpn_scat_name'];
				$productID = $row['product_id'];

				/* <Prepare insert data> */
					$prepare_data = "'$apwID', ";
					$prepare_data .= "'$apwName', ";
					$prepare_data .= "'$apwProductURL', ";
					$prepare_data .= "'$image2', ";
					$prepare_data .= "'$categoryID1', ";
					$prepare_data .= "'TRUE', ";
					$prepare_data .= "'$price', ";
					$prepare_data .= "'$image3', ";
					$prepare_data .= "'$retailPrice', ";
					$prepare_data .= "'$categoryID2', ";
					$prepare_data .= "'$SKU', ";
					$prepare_data .= "'$make', ";
					$prepare_data .= "'$model', ";
					$prepare_data .= "'$brand', ";
					$prepare_data .= "'$part', ";
					$prepare_data .= "'$universal', ";
					$prepare_data .= "'$productID'";

					$insert_data[] = "($prepare_data)";
				/* </Prepare insert data> */
							
				 $o++;
			} //This line is added to exclude images with placeholder
		} 

		/* <Array insert data> */
			if(!empty($insert_data)){
				$tblfields = array(
            		'id',
                    'name',
                    'productURL',
                    'smallImage',
                    'categoryID1',
                    'instock',
                    'price',
                    'bigImage',
                    'retailPrice',
                    'categoryID2',
                    'SKU',
	    			'make_name',
	    			'model_name',
          			'brand',
                    'part',                            
              		'universal',
              		'product_id'
                );
				
				echo $sql = "insert into $tableName(".implode(', ', $tblfields).") values ".implode(', ', $insert_data);
				$db_queries->query($sql);

				$insert_data = array(); // This line is added to limit the array insert data
			}
		/* </Array insert data> */
		
	}else {
	    
	    genLogs("There are no more Data to Insert");
	    genLogs("End Insert Data");	
	    genLogs("Start UNIQUE INDEXING of Duplicates at $tableName Table");	
        $removeDuplicates   = "ALTER IGNORE TABLE feeds.$tableName ADD UNIQUE INDEX dupidx (id)";
        $runRemoveDuplicates= mysql_query($removeDuplicates) or die(genLogs(mysql_error()));        
        genLogs("End UNIQUE INDEXING of Duplicates at $tableName Table");	
        
        
        genLogs("Start Removing Duplicates at $tableName Table");	        
        $removeDuplicates   = "ALTER TABLE feeds.$tableName DROP INDEX dupidx";
        $runRemoveDuplicates= mysql_query($removeDuplicates) or die(genLogs(mysql_error()));        
        genLogs("End Removing Duplicates at $tableName Table");	
        
        genLogs("End Insert Data");	
	    //exit();
	}
	
}

genLogs("Start UNIQUE INDEXING of Duplicates at $tableName Table");	
$removeDuplicates   = "ALTER IGNORE TABLE feeds.$tableName ADD UNIQUE INDEX dupidx (id)";
$runRemoveDuplicates= mysql_query($removeDuplicates) or die(genLogs(mysql_error()));
genLogs("End UNIQUE INDEXING of Duplicates at $tableName Table");	


genLogs("Start Removing Duplicates at $tableName Table");	
$removeDuplicates   = "ALTER TABLE feeds.$tableName DROP INDEX dupidx";
$runRemoveDuplicates= mysql_query($removeDuplicates) or die(genLogs(mysql_error()));
genLogs("End Removing Duplicates at $tableName Table");	
genLogs("End Insert Data");	
genLogs("End Time : " . date("h:i:s A") . "\n");

	
?>
