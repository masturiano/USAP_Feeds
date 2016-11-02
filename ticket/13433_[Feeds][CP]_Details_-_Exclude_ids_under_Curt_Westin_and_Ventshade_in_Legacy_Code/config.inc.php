<?php
include_once('includes/db.class.php');
include_once('includes/queries.class.php');
include_once('includes/general.inc.php');


#Get Environment Setup
$valid_envs = array('production','staging','development');

$conf = getConfig('configs/config.xml');

$env = getEnvArg();
$mail = getMailArg();

if (in_array($env, $valid_envs)) {
	define("ENVR", $env);
} else {
	define("ENVR", $conf['environment']);	
}

define("DB_HOST", $conf['conn']['host']); 
define("DB_USER", $conf['conn']['user']); 
define("DB_PASS", $conf['conn']['pw']); 
define("DB_DB",   $conf['conn']['db']); 
define("DB_PORT",   $conf['conn']['port']); 

define("INSERT_TABLE_NAME", $conf['insert_table_name']);
define("BUILD_TABLE_NAME", $conf['build_table_name']);

define("GROSS_MARGIN_PERCENTAGE", $conf['gross_margin_percentage']);
define("ENABLE_GROSS_MARGIN_PERCENTAGE", $conf['enable_gross_margin_percentage']);

define("TOP_100K_FILENAME", $conf['top_100k_file_name']);
define("PRICE_MATCH_SKU", $conf['price_match_sku']);
define("TOP_100K_AFF_FILENAME", $conf['top_100k_aff_file_name']);
define("INCLUDED_BRAND", $conf['include_brand']);
define("GOOGLE_TAXONOMY", $conf['google_taxonomy_file_name']);
define("DROP_RECORDS", $conf['drop_records_file_name']);
define("INSERT_LIMIT", $conf['insert_limit']);
define("TITLE_LIMIT", $conf['title_limit']);
define("SALES_FILES", $conf['sales_files']);
define("FEEDDIR", 		getcwd() .'/data/latest/'); 
define("FEEDROLLBACKDIR",               getcwd() .'/data/rollback/');
define("FEEDBACKUPDIR",               getcwd() .'/data/backup/');
define("FEED_NAME",   	'PartsTrain '); 

define("EXCLUDE_BRAND", $conf['exclude_brand']);

if (defined('ENVR') && ENVR=='production') {
	define("QA_MAIL_TO", 'mjmendoza@usautoparts.com'); 
	define("QA_MAIL_CC", "marias@usautoparts.com DGTeamQA@usautoparts.com DGLinux-Support@usautoparts.com agelito@usautoparts.com klabustro@usautoparts.com"); 
} else {
	if ($mail!='') {
		define("QA_MAIL_TO", $mail);
	} else {
		define("QA_MAIL_TO", 	'');
	}
}

$db_queries = new db_queries($conf['source_table_name']);
$db_queries->connect(DB_HOST, DB_USER, DB_PASS, DB_DB);

?>
