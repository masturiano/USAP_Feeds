<?php

/**
 * @category USAPTool_Modules
 * @package Dcms_Service
 * @copyright 2012
 * @author bteves
 * @version $Id$
 */
class Dcms_Service_Template extends USAP_Service_ServiceAbstract {

    /**
     *
     * @var Zend_Config 
     */
    protected $config;

    /**
     * 
     * @var Base Service Object
     */
    protected $baseService;

    /**
     * Selected Restrictions
     * @var array
     */
    protected $selected = array();

    /**
     * Flag if there are restrictions selected
     * @var boolean
     */
    protected $hasSelectedRestriction = false;

    /**
     * Username
     * @var string
     */
    protected $user;

    /**
     *
     * @param Zend_Config $config
     */
    public function __construct(Zend_Config $config, $baseService) {
        $this->config = $config;
        $this->baseService = $baseService;
        $user = $this->baseService->getRegistryAclUser();
        $this->user = $user['user']->getUserName();
    }

    public function displayTemplateList($httpRequest) {
        $request = $httpRequest->getParams();
        $request['countperpage'] = isset($request['countperpage']) ? $request['countperpage'] : 10;
        $request['page'] = isset($request['page']) ? $request['page'] : 1;
        $config = $this->config->service->toArray();
        $hydraService = $config['sourceobject']['coupon'];
        $hydraService['data'] = array(
            'type' => "template",
            'env' => "working"
        );
        $form = $this->getSearchForm();
        
        if(isset($request['template_name'])){
            $hydraService['data']['query']['mongoregex'] = array('templateName' => $request['template_name']);
            $form->getElement("template_name")->setValue($request['template_name']);
        }else{
            $hydraService['data']['query'] = $this->baseService->queryIfDeleted(array());
        }
        
        
        
         /* * *
         * set query parameters like page, advance query, site selected, count per page
         */
        if ($httpRequest->isPost()) {
            $post = $httpRequest->getPost();
            $form->getElement("template_name")->setValue($post['template_name']);
            $hydraService['data']['query']['mongoregex'] = array('templateName' => $post['template_name']);
            $request['template_name'] = $post['template_name'];
            $request['page'] = 1;
        } 
        
        
        $return['request'] = $request;
        $hydraService['method'] = "read";
        $return['paginator'] = $this->baseService->getPaginator($hydraService, $request);
        $return['form'] = $form;
        return $return;
    }
    
    public function getSearchForm(){
        $form = new Dcms_Form_Template_Search();
        return $form;
    }

    
    public function getTemplateForm() {

        return new Dcms_Form_Template_Addedit();
    }

    # Name: Del Date: Jun 2016
    public function getTemplateFormUpload() {

        return new Dcms_Form_Template_Addeditupload();
    }

    public function processParamId($id) {

        $param['query'] = array("_id" => $id);
        $param['type'] = 'template';
        $result = $this->_hydraConnect($param);
        return $result;
    }

    public function processParamTemplateId($templateId) {

        $param['query'] = array("templateId" => (int)$templateId);
        $param['type'] = 'template';
        $result = $this->_hydraConnect($param);
        return $result;
    }

    public function viewTemplate($request) {

        $id = $request->getParam('id');
        $templateId = $request->getParam('tid');
        if (isset($id) || isset($templateId)) {
            if (isset($id)) {
                $result = $this->processParamId($id);
            } elseif (isset($templateId)) {
                $result = $this->processParamTemplateId($templateId);
            }
            if (!is_string($result)) {
                if ($result['records']['result']['record_count'] == 1) {
                    $data = $result['records']['result']['records'][0];
                    return array(
                        'data' => $data
                    );
                } else {
                    return array(
                        'addmessage' => "Invalid Template Id."
                    );
                }
            } else {
                return array(
                    'addmessage' => $result
                );
            }
        } else {
            return array(
                'addmessage' => "Template Id not found."
            );
        }
    }

    public function editForm($request) {

        $templateForm = $this->getTemplateForm();
        $id = $request->getParam('id');
        if (isset($id)) {

            $result = $this->processParamId($id);

            if (!is_string($result)) {
                if ($result['records']['result']['record_count'] == 1) {
                    $data = $result['records']['result']['records'][0];
                    $templateId = (int) $data['templateId'];
                    if ($request->isPost()) {

                        $posts = $request->getPost();
                        $templateForm->buildForm();

                        if ($templateForm->isValid($posts)) {

                            $templateName = $templateForm->getValue('template_name');
                            $checkParam['type'] = 'template';
                            $checkParam['query'] = $this->baseService->queryIfDeleted(array("templateName" => $templateName, "neid" => $id));
                            $checkResult = $this->_hydraConnect($checkParam);

                            if (isset($checkResult['records']['result'])) {
                                if ($checkResult['records']['result']['record_count'] > 0) {
                                    $repopulatedForm = $this->_populateFormData($request, $templateForm);
                                    return array(
                                        'form' => $repopulatedForm,
                                        'addmessage' => 'Template name already exists.'
                                    );
                                }
                            } else {
                                return array(
                                    'addmessage' => "An error occured while proccessing your request.",
                                    'redirect' => "/dcms/template"
                                );
                            }

                            $param['type'] = 'template';
                            $param['decode'] = true;
//                            if ($data['templateName'] != $templateName) {
//                                $newRecord = $this->_buildRecord($templateForm, true);
//                                $templateId =  $this->baseService->getUniqueKey();
//                                if(is_string($templateId)) {
//                                    $repopulatedForm = $this->_populateFormData($request, $templateForm);
//                                    return array(
//                                        'form'       => $repopulatedForm,
//                                        'addmessage' => $templateId
//                                    ); 
//                                } else {
//                                    $newRecord[$this->baseService->baseEncoding("templateId")] = $templateId;
//                                }                                
//                                $param['query'][] = $newRecord;
//                                $logtype = "newtemplate";
//                                $result = $this->_hydraConnect($param, 'coupon', 'create');
//                            } else {
                            
                                $record = $this->_buildRecord($templateForm, false, true);
                                $record[$this->baseService->baseEncoding("templateId")] = $templateId;
//echo "<pre>",print_r($record);exit;
                                $param['query'][] = array("set" => $record, "where" => array($this->baseService->baseEncoding("_id") => $this->baseService->baseEncoding($id)));
// echo "-----><pre>", print_r($templateForm);exit;
                                $result = $this->_hydraConnect($param, 'coupon', 'update');
                                $liveTemplateSearch = $this->baseService->hydraConnect(array('templateId' => $templateId), "template", 'read', 'live');
                                if(isset($liveTemplateSearch['result']['records']) && isset($liveTemplateSearch['result']['record_count']) && $liveTemplateSearch['result']['record_count'] > 0){
                                    $template_id = $liveTemplateSearch['result']['records'][0]['id'];
                                    unset($param['query'][0]);
                                    $param['query'][] = array("set" => $record, "where" => array($this->baseService->baseEncoding("_id") => $this->baseService->baseEncoding($template_id)));  
                                    $result = $this->_hydraConnect($param, 'coupon', 'update', 'live');
                                }
                                $logtype = "edittemplate";
//                            }
                            $this->log($posts, $logtype);
                            return $this->_buildReply($result, $templateForm, 'update');
                        } else {

                            $repopulatedForm = $this->_populateFormData($request, $templateForm);

                            return array(
                                'form' => $repopulatedForm,
                                'coupon' => $this->baseService->getHydraPaginator(10, 0, "coupon", array('coupon.name' => '1'), array('templateId' => $templateId), "readCouponAndBatch","live"),
                                'templateId' => $templateId
                            );
                        }
                    } else {
                        $templateForm->buildForm($data);
                        return array(
                            'form' => $templateForm,
                            'coupon' => $this->baseService->getHydraPaginator(10, 0, "coupon", array('coupon.name' => '1'), array('templateId' => $templateId), "readCouponAndBatch","live"),
                            'templateId' => $templateId
                        );
                    }
                } else {
                    return array(
                        'addmessage' => "Invalid Template Id",
                        'redirect' => "/dcms/template"
                    );
                }
            } else {
                return array(
                    'addmessage' => $result,
//                    'redirect' => "/dcms/template"
                );
            }
        } else {
            return array(
                'addmessage' => "Template Id not found.",
                'redirect' => "/dcms/template"
            );
        }
    }

    private function _buildReply($result, $templateForm, $hydraTypeCall = 'create') {
        if (isset($result['result']['result'])) {
            return array(
                'addmessage' => ($hydraTypeCall == 'create') ? "Template successfully saved." : "Template successfully updated.",
                'redirect' => "/dcms/template"
            );
        } else {
            
            if(is_string($result)) {
                return array(
                    'addmessage' => $result,
                    'form' => $templateForm
                );
            } elseif (!is_string($result) && isset($result['result']['error']['database_result'])) {
                return array(
                    'addmessage' => $result['result']['error']['database_result'],
                    'form' => $templateForm
                );
            } elseif(isset($result['result']['error']['message'])) { 
                return array(
                    'addmessage' => $result['result']['error']['message'],
                    'redirect' => "/dcms/template"
                );
            }
        }
    }

    public function addForm($request) {

        $templateForm = $this->getTemplateForm();

        if ($request->isPost()) {
            $posts = $request->getPost();
            $templateForm->buildForm();
            if ($templateForm->isValid($posts)) {

                $templateName = $templateForm->getValue('template_name');
                $checkParam['type'] = 'template';
                $checkParam['query'] = array("templateName" => $templateName);
                $checkResult = $this->_hydraConnect($checkParam);

                if (isset($checkResult['records']['result'])) {
                    if ($checkResult['records']['result']['record_count'] > 0) {
                        $repopulatedForm = $this->_populateFormData($request, $templateForm);
                        return array(
                            'form' => $repopulatedForm,
                            'addmessage' => "Template name already exists. If not found on the list, it is deleted."
                        );
                    }
                } else {
                    return array(
                        'addmessage' => "An error occured while proccessing your request.",
                        'redirect' => "/dcms/template"
                    );
                }

                $newRecord = $this->_buildRecord($templateForm, true, false);
                $templateId =  $this->baseService->getUniqueKey();
                if(is_string($templateId)) {
                    $repopulatedForm = $this->_populateFormData($request, $templateForm);
                    return array(
                        'form'       => $repopulatedForm,
                        'addmessage' => $templateId
                    );
                } else {
                    $newRecord[$this->baseService->baseEncoding("templateId")] = $templateId;
                }
                $param['query'][] = $newRecord;
                $param['type'] = 'template';
                $param['decode'] = true;
//		echo "<pre>", print_r($param);exit;
                $result = $this->_hydraConnect($param, 'coupon', 'create');
                $this->log($posts, "newtemplate");

                return $this->_buildReply($result, $templateForm, 'create');
            } else {

                $repopulatedForm = $this->_populateFormData($request, $templateForm);

                return array(
                    'form' => $repopulatedForm
                );
            }
        } else {

            $templateForm->buildForm();
            return array(
                'form' => $templateForm
            );
        }
    }

    # Name: Del Date: Jun 2016
    public function addFormUpload($request) {

        $templateForm = $this->getTemplateFormUpload();

        if ($request->isPost()) {
            $posts = $request->getPost();
            $templateForm->buildForm();

            if ($templateForm->isValid($posts)) {

                $templateName = $templateForm->getValue('template_name');
                $checkParam['type'] = 'template';
                $checkParam['query'] = array("templateName" => $templateName);
                $checkResult = $this->_hydraConnect($checkParam);

                if (isset($checkResult['records']['result'])) {
                    if ($checkResult['records']['result']['record_count'] > 0) {
                        $repopulatedForm = $this->_populateFormData($request, $templateForm);
                        return array(
                            'form' => $repopulatedForm,
                            'addmessage' => "Template name already exists. If not found on the list, it is deleted."
                        );
                    }
                } else {
                    return array(
                        'addmessage' => "An error occured while proccessing your request.",
                        'redirect' => "/dcms/template"
                    );
                }

                /**
                * db.dcms_restrictions_uploading_status.insert({"stat":NumberLong(1),"statName":"uploader"});
                * db.dcms_restrictions_uploading_status.update({"statName":"uploader"},{$set:{"stat":NumberLong(0)}})
                */
                $checkUploadStat['type'] = 'templateuploadstat';
                $checkUploadStat['query'] = array();
                $checkResultUploadStat = $this->_hydraConnect($checkUploadStat);
                if (isset($checkResultUploadStat['records']['result'])) {
                    if ($checkResultUploadStat['records']['result']['record_count'] > 0) {
                        foreach ($checkResultUploadStat['records'] as $key => $value) {
                            foreach ($value as $key2 => $value2) {
                                foreach ($value2 as $key3 => $value3) {
                                    if($value3['stat'] == 0){
                                        /* START - UPDATING THE UPLOADING STATUS TABLE */
                                        $checkUploadStatUpdate['query'][] = array("set" => array("stat" => 1), "where" => array("statName" => "uploader"));
                                        $checkUploadStatUpdate['type'] = 'templateuploadstat';
                                        //$checkUploadStatUpdate['decode'] = true;
                                        $result_uploading = $this->_hydraConnect($checkUploadStatUpdate, 'coupon', 'update');
                                        /* END - UPDATING THE UPLOADING STATUS TABLE */
                                        if($result_uploading){
                                            /* START - INITIALIZE UPLOADING */
                                            if(isset($_FILES['attachment'])){

                                                # Name: Del Date: Jun 2016

                                                // SET THE FOLDER PATH
                                                $import_file = $this->config->template->import->directory;  
                                                //$import_file="/var/www/html/Dcms_ell/template_import_file/";  
                                                // GET THE FILE INFO'S
                                                $file_name = $_FILES['attachment']['name'];
                                                $file_size = $_FILES['attachment']['size'];
                                                $file_tmp = $_FILES['attachment']['tmp_name'];
                                                $file_type = $_FILES['attachment']['type'];
                                                $file_ext = strtolower(end(explode('.',$_FILES['attachment']['name'])));

                                                /* START - DELETE ALL THE FILES INSIDE DIRECTORY FOLDER */
                                                if ($dirhandler = opendir($import_file)) 
                                                {
                                                    while ($file = readdir($dirhandler)) 
                                                    {
                                                        $delete[] = $import_file.$file;
                                                        foreach ( $delete as $file ) {
                                                            unlink( $file );
                                                        }
                                                    }
                                                }
                                                /* END - DELETE ALL THE FILES INSIDE DIRECTORY FOLDER */

                                                if(!empty($file_size)){

                                                    // REQUIRED FILE EXTENSIONS
                                                    $expensions= array("csv");
                                                    // ARRAY ERROR MESSAGE
                                                    $errors= array();

                                                    /* START - VALIDATION FOR FILE EXTENSION */
                                                    if(in_array($file_ext,$expensions)=== false){
                                                        $errors[]="extension not allowed, please choose CSV file.";
                                                    }
                                                    /* END - VALIDATION FOR FILE EXTENSION */

                                                    /* START - VALIDATION FOR FILE SIZE */
                                                    if($file_size > 1073741824){
                                                        $errors[]='File size must be excately 1 GIG';
                                                    }
                                                    /* END - VALIDATION FOR FILE SIZE */

                                                    /* START - VALID FILE UPLOADING */
                                                    if(empty($errors)==true){
                                                        move_uploaded_file($file_tmp,$import_file.$file_name);

                                                        // SET VARIABLE TO CHECK THE DIRECTORY
                                                        $checkEmpty  = (count(glob($import_file.'*')) === 0) ? 'Empty' : 'Not empty';

                                                        /* START - CHECKING THE DIRECTORY  */
                                                        if ($checkEmpty == "Empty")
                                                        {
                                                           echo "No file inside directory!";
                                                        }
                                                        else
                                                        {
                                                            /* START - OPEN THE DIRECTORY */  
                                                            if ($dirhandler = opendir($import_file)) 
                                                            {
                                                                /* START - LOOPING THE FILES */
                                                                while ($file = readdir($dirhandler)) 
                                                                {
                                                                    $file_ext = explode('.',$file);
                                                                    $file_ext = $file_ext[1];
                                                                    /* START - READ THE CSV FILES ONLY */
                                                                    if($file_ext == "csv" || $file_ext == "CSV")
                                                                    {
                                                                        if($file == $file_name){

                                                                            $file_expected = explode('.',$file); 
                                                                            $file_expected = $file_expected[0]; 

                                                                            $file_name_parts = "parts";
                                                                            $file_name_brands = "brands";
                                                                            $file_name_brand_parts = "brand_parts";
                                                                            $file_name_part_brands = "part_brands";
                                                                            $file_name_brand_skus = "brand_skus";

                                                                            $find_string_parts = strpos($file_name_parts,$file_expected);
                                                                            $find_string_brands = strpos($file_name_brands,$file_expected);
                                                                            $find_string_brand_parts = strpos($file_name_brand_parts,$file_expected);
                                                                            $find_string_part_brands = strpos($file_name_part_brands,$file_expected);
                                                                            $find_string_brand_skus = strpos($file_name_brand_skus,$file_expected);

                                                                            // SET VARIABLE FOR CSV FILES
                                                                            $csv_file = $import_file.$file;
                                                                            /* START - OPEN THE CSV FILE CONTENT */
                                                                            if (($handle = fopen($csv_file, "r")) !== FALSE) 
                                                                            {
                                                                                $category_name = $templateForm->getValue('restriction_selector_select');
                                                                                $category_name_site = $templateForm->getValue('restriction_selector_select_site');
                                                                                if($category_name == 'parttemplate'){
                                                                                    if($find_string_parts !== false){
                                                                                        $selected_category_name = 'parts';
                                                                                        /* START - LOOPING THE CSV FILES */
                                                                                        while (($data = fgetcsv($handle, 10000000, ",")) !== FALSE) 
                                                                                        {
                                                                                            $num = count($data);
                                                                                            /* START - LOOPING THE CSV FILE CONTENT */
                                                                                            for ($c=0; $c < $num; $c++) 
                                                                                            {
                                                                                                $col1 = trim($data[0]);
                                                                                            }
                                                                                            $finalUploadParts[] = $this->baseService->baseEncoding($col1);
                                                                                            /* END - LOOPING THE CSV FILE CONTENT */
                                                                                        }
                                                                                        /* END - LOOPING THE CSV FILES */

                                                                                        $upload_record = array(
                                                                                            $this->baseService->baseEncoding($selected_category_name) => $finalUploadParts
                                                                                        );
                                                                                    }
                                                                                    else{
                                                                                        /* START - UPDATING THE UPLOADING STATUS TABLE */
                                                                                        $checkUploadStatUpdate['query'][] = array("set" => array("stat" => 0), "where" => array("statName" => "uploader"));
                                                                                        $checkUploadStatUpdate['type'] = 'templateuploadstat';
                                                                                        //$checkUploadStatUpdate['decode'] = true;
                                                                                        $result_uploading = $this->_hydraConnect($checkUploadStatUpdate, 'coupon', 'update');
                                                                                        /* END - UPDATING THE UPLOADING STATUS TABLE */

                                                                                        $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                                                        return array(
                                                                                            'form' => $repopulatedForm,
                                                                                            'addmessage' => "Invalid filename should be parts.csv",
                                                                                        );
                                                                                    }
                                                                                }
                                                                                else if($category_name == 'brandtemplate'){
                                                                                    if($find_string_brands !== false){
                                                                                        $selected_category_name = 'brands';
                                                                                        /* START - LOOPING THE CSV FILES */
                                                                                        while (($data = fgetcsv($handle, 10000000, ",")) !== FALSE) 
                                                                                        {
                                                                                            $num = count($data);
                                                                                            /* START - LOOPING THE CSV FILE CONTENT */
                                                                                            for ($c=0; $c < $num; $c++) 
                                                                                            {
                                                                                                $col1 = trim($data[0]);
                                                                                            }
                                                                                            $finalUploadBrands[] = $this->baseService->baseEncoding($col1);
                                                                                            /* END - LOOPING THE CSV FILE CONTENT */
                                                                                        }
                                                                                        /* END - LOOPING THE CSV FILES */

                                                                                        $upload_record = array(
                                                                                            $this->baseService->baseEncoding($selected_category_name) => $finalUploadBrands
                                                                                        );
                                                                                    }
                                                                                    else{
                                                                                        /* START - UPDATING THE UPLOADING STATUS TABLE */
                                                                                        $checkUploadStatUpdate['query'][] = array("set" => array("stat" => 0), "where" => array("statName" => "uploader"));
                                                                                        $checkUploadStatUpdate['type'] = 'templateuploadstat';
                                                                                        //$checkUploadStatUpdate['decode'] = true;
                                                                                        $result_uploading = $this->_hydraConnect($checkUploadStatUpdate, 'coupon', 'update');
                                                                                        /* END - UPDATING THE UPLOADING STATUS TABLE */

                                                                                        $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                                                        return array(
                                                                                            'form' => $repopulatedForm,
                                                                                            'addmessage' => "Invalid filename should be brands.csv",
                                                                                        );
                                                                                    }
                                                                                }
                                                                                else if($category_name == 'brandparttemplate'){
                                                                                    if($find_string_brand_parts !== false){
                                                                                        $selected_category_name = 'brandPart';
                                                                                        /* START - LOOPING THE CSV FILES */
                                                                                        $finalUploadBrandParts = array();
                                                                                        while (($data = fgetcsv($handle, 10000000, ",")) !== FALSE) 
                                                                                        {
                                                                                            $num = count($data);
                                                                                            /* START - LOOPING THE CSV FILE CONTENT */
                                                                                            for ($c=0; $c < $num; $c++) 
                                                                                            {
                                                                                                $col1 = trim($data[0]);
                                                                                                $col2 = trim($data[1]);
                                                                                                
                                                                                            }
                                                                                            $finalUploadBrandParts[$this->baseService->baseEncoding($col1)][] = $this->baseService->baseEncoding($col2);
                                                                                            /* END - LOOPING THE CSV FILE CONTENT */
                                                                                        }
                                                                                        /* END - LOOPING THE CSV FILES */
                                                                                        $upload_record = array(
                                                                                            $this->baseService->baseEncoding($selected_category_name) => $finalUploadBrandParts
                                                                                        );
                                                                                    }
                                                                                    else{
                                                                                        /* START - UPDATING THE UPLOADING STATUS TABLE */
                                                                                        $checkUploadStatUpdate['query'][] = array("set" => array("stat" => 0), "where" => array("statName" => "uploader"));
                                                                                        $checkUploadStatUpdate['type'] = 'templateuploadstat';
                                                                                        //$checkUploadStatUpdate['decode'] = true;
                                                                                        $result_uploading = $this->_hydraConnect($checkUploadStatUpdate, 'coupon', 'update');
                                                                                        /* END - UPDATING THE UPLOADING STATUS TABLE */

                                                                                        $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                                                        return array(
                                                                                            'form' => $repopulatedForm,
                                                                                            'addmessage' => "Invalid filename should be brand_parts.csv",
                                                                                        );
                                                                                    }
                                                                                }
                                                                                else if($category_name == 'partbrandtemplate'){
                                                                                    if($find_string_part_brands !== false){
                                                                                        $selected_category_name = 'partBrand';
                                                                                        /* START - LOOPING THE CSV FILES */
                                                                                        $finalUploadPartBrands = array();
                                                                                        while (($data = fgetcsv($handle, 10000000, ",")) !== FALSE) 
                                                                                        {
                                                                                            $num = count($data);
                                                                                            /* START - LOOPING THE CSV FILE CONTENT */
                                                                                            for ($c=0; $c < $num; $c++) 
                                                                                            {
                                                                                                $col1 = trim($data[0]);
                                                                                                $col2 = trim($data[1]);
                                                                                                
                                                                                            }
                                                                                            $finalUploadPartBrands[$this->baseService->baseEncoding($col1)][] = $this->baseService->baseEncoding($col2);
                                                                                            /* END - LOOPING THE CSV FILE CONTENT */
                                                                                        }
                                                                                        /* END - LOOPING THE CSV FILES */
                                                                                        $upload_record = array(
                                                                                            $this->baseService->baseEncoding($selected_category_name) => $finalUploadPartBrands
                                                                                        );
                                                                                    }
                                                                                    else{
                                                                                        /* START - UPDATING THE UPLOADING STATUS TABLE */
                                                                                        $checkUploadStatUpdate['query'][] = array("set" => array("stat" => 0), "where" => array("statName" => "uploader"));
                                                                                        $checkUploadStatUpdate['type'] = 'templateuploadstat';
                                                                                        //$checkUploadStatUpdate['decode'] = true;
                                                                                        $result_uploading = $this->_hydraConnect($checkUploadStatUpdate, 'coupon', 'update');
                                                                                        /* END - UPDATING THE UPLOADING STATUS TABLE */

                                                                                        $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                                                        return array(
                                                                                            'form' => $repopulatedForm,
                                                                                            'addmessage' => "Invalid filename should be part_brands.csv",
                                                                                        );
                                                                                    }
                                                                                }
                                                                                else if($category_name == 'skutemplate'){
                                                                                    if($find_string_brand_skus !== false){
                                                                                        $selected_category_name = 'brandSku';

                                                                                        /* START - LOOPING THE CSV FILES */
                                                                                        $finalUploadSkus = array();
                                                                                        while (($data = fgetcsv($handle, 10000000, ",")) !== FALSE) 
                                                                                        {
                                                                                            $num = count($data);
                                                                                            //$row++;

                                                                                            /* START - LOOPING THE CSV FILE CONTENT */
                                                                                            for ($c=0; $c < $num; $c++) 
                                                                                            {
                                                                                                $col1 = trim($data[0]);
                                                                                                $col2 = trim($data[1]);
                                                                                                
                                                                                            }
                                                                                            $finalUploadSkus[$this->baseService->baseEncoding($category_name_site)][$this->baseService->baseEncoding($col1)][] = $this->baseService->baseEncoding($col2);
                                                                                            /* END - LOOPING THE CSV FILE CONTENT */
                                                                                        }
                                                                                        /* END - LOOPING THE CSV FILES */
                                                                                        $upload_record = array(
                                                                                            $this->baseService->baseEncoding($selected_category_name) => $finalUploadSkus
                                                                                        );
                                                                                    }
                                                                                    else{
                                                                                        /* START - UPDATING THE UPLOADING STATUS TABLE */
                                                                                        $checkUploadStatUpdate['query'][] = array("set" => array("stat" => 0), "where" => array("statName" => "uploader"));
                                                                                        $checkUploadStatUpdate['type'] = 'templateuploadstat';
                                                                                        //$checkUploadStatUpdate['decode'] = true;
                                                                                        $result_uploading = $this->_hydraConnect($checkUploadStatUpdate, 'coupon', 'update');
                                                                                        /* END - UPDATING THE UPLOADING STATUS TABLE */

                                                                                        $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                                                        return array(
                                                                                            'form' => $repopulatedForm,
                                                                                            'addmessage' => "Invalid filename should be brand_skus.csv",
                                                                                        );
                                                                                    }
                                                                                }
                                                                                else{
                                                                                    /* START - UPDATING THE UPLOADING STATUS TABLE */
                                                                                    $checkUploadStatUpdate['query'][] = array("set" => array("stat" => 0), "where" => array("statName" => "uploader"));
                                                                                    $checkUploadStatUpdate['type'] = 'templateuploadstat';
                                                                                    //$checkUploadStatUpdate['decode'] = true;
                                                                                    $result_uploading = $this->_hydraConnect($checkUploadStatUpdate, 'coupon', 'update');
                                                                                    /* END - UPDATING THE UPLOADING STATUS TABLE */

                                                                                    $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                                                    return array(
                                                                                        'form' => $repopulatedForm,
                                                                                        'addmessage' => "Not in the list of categories",
                                                                                    );
                                                                                }

                                                                                $newRecord = $this->_buildRecordUpload($templateForm, true, false);
                                                                                $templateId =  $this->baseService->getUniqueKey();
                                                                                if(is_string($templateId)) {
                                                                                    $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                                                    return array(
                                                                                        'form'       => $repopulatedForm,
                                                                                        'addmessage' => $templateId
                                                                                    );
                                                                                } else {
                                                                                    $newRecord[$this->baseService->baseEncoding("templateId")] = $templateId;
                                                                                }

                                                                                /* START - SAVING THE TEXTFILE CONTENT TO TEMPORARY TABLE */
                                                                                $param['query'][] = array_merge($newRecord,$upload_record);
                                                                                $param['type'] = 'templatetemp';
                                                                                $param['decode'] = true;
                                                                                $result = $this->_hydraConnect($param, 'coupon', 'create');
                                                                                /* END - SAVING THE TEXTFILE CONTENT TO TEMPORARY TABLE */

                                                                                if (isset($result)) {
                                                                                    $checkParam['type'] = 'templatetemp';
                                                                                    $checkParam['query'] = array("templateName" => $templateName);
                                                                                    $checkResult = $this->_hydraConnect($checkParam);
                                                                                    
                                                                                    if (isset($checkResult['records']['result'])) {
                                                                                        if ($checkResult['records']['result']['record_count'] > 0) {
                                                                                            if($category_name == 'parttemplate'){
                                                                                                $finalPartWorking = array();
                                                                                                $finalPartRejected = array();
                                                                                                foreach ($checkResult['records'] as $key => $value) {
                                                                                                    foreach($value['records'] as $key2 => $value2){
                                                                                                        foreach($value2[$selected_category_name] as $key3 => $value3){
                                                                                                            $paramsMysql['post'] = $this->baseService->baseEncoding($value3);
                                                                                                            $paramsMysql['offset'] = '0';
                                                                                                            $paramsMysql['pagesize'] = '30';
                                                                                                            $paramsMysql['issearch'] = null;
                                                                                                            $paramsMysql['type'] = 'mysql';
                                                                                                            $paramsMysql['restriction'] = 'selected_part';
                                                                                                            $selected_part_result = $this->getResults($paramsMysql, 'part_name');
                                                                                                            $result_query = $selected_part_result['records']['record_count'];
                                                                                                            if((int)$result_query != 0){
                                                                                                                $column1 = $value3;
                                                                                                                $column1 = $this->replaceDot($column1);
                                                                                                                $finalPartWorking[] = $this->baseService->baseEncoding($column1);
                                                                                                            }
                                                                                                            else{
                                                                                                                $column_rejected = $value3;
                                                                                                                $finalPartRejected[] = $this->baseService->baseEncoding($column_rejected);
                                                                                                            }   
                                                                                                        }
                                                                                                    }
                                                                                                    $upload_record_working_table = array(
                                                                                                        $this->baseService->baseEncoding($selected_category_name) => $finalPartWorking
                                                                                                    );
                                                                                                    $upload_record_rejected_table = array(
                                                                                                        $this->baseService->baseEncoding($selected_category_name) => $finalPartRejected
                                                                                                    );
                                                                                                }
                                                                                            }
                                                                                            else if($category_name == 'brandtemplate'){
                                                                                                $finalBrandWorking = array();
                                                                                                $finalBrandRejected = array();
                                                                                                foreach ($checkResult['records'] as $key => $value) {
                                                                                                    foreach($value['records'] as $key2 => $value2){
                                                                                                        foreach($value2[$selected_category_name] as $key3 => $value3){
                                                                                                            $paramsMysql['post'] = $this->baseService->baseEncoding($value3);
                                                                                                            $paramsMysql['offset'] = '0';
                                                                                                            $paramsMysql['pagesize'] = '30';
                                                                                                            $paramsMysql['issearch'] = null;
                                                                                                            $paramsMysql['type'] = 'mysql';
                                                                                                            $paramsMysql['restriction'] = 'selected_brand';
                                                                                                            $selected_brand_result = $this->getResults($paramsMysql, 'brand_name');
                                                                                                            $result_query = $selected_brand_result['records']['record_count'];
                                                                                                            if((int)$result_query != 0){
                                                                                                                $column1 = $value3;
                                                                                                                $column1 = $this->replaceDot($column1);
                                                                                                                $finalBrandWorking[] = $this->baseService->baseEncoding($column1);
                                                                                                            }
                                                                                                            else{
                                                                                                                $column_rejected = $value3;
                                                                                                                $finalBrandRejected[] = $this->baseService->baseEncoding($column_rejected);
                                                                                                            }   
                                                                                                        }
                                                                                                    }
                                                                                                    $upload_record_working_table = array(
                                                                                                        $this->baseService->baseEncoding($selected_category_name) => $finalBrandWorking
                                                                                                    );
                                                                                                    $upload_record_rejected_table = array(
                                                                                                        $this->baseService->baseEncoding($selected_category_name) => $finalBrandRejected
                                                                                                    );
                                                                                                }
                                                                                            }
                                                                                            else if($category_name == 'brandparttemplate'){
                                                                                                
                                                                                                $finalBrandPartsWorking = array();
                                                                                                $finalBrandPartsRejected = array();
                                                                                                foreach ($checkResult['records'] as $key => $value) {
                                                                                                    foreach($value['records'] as $key2 => $value2){
                                                                                                        foreach($value2[$selected_category_name] as $key3 => $value3){
                                                                                                            $column1 = $key3;
                                                                                                            $paramsMysql['post'] = $this->baseService->baseEncoding($column1);
                                                                                                            $paramsMysql['offset'] = '0';
                                                                                                            $paramsMysql['pagesize'] = '30';
                                                                                                            $paramsMysql['issearch'] = null;
                                                                                                            $paramsMysql['type'] = 'mysql';
                                                                                                            $paramsMysql['restriction'] = 'selected_brand';
                                                                                                            $selected_brand_result = $this->getResults($paramsMysql, 'brand_name');
                                                                                                            $result_query = $selected_brand_result['records']['record_count'];
                                                                                                            if((int)$result_query != 0){
                                                                                                                foreach($value3 as $key4 => $value4){
                                                                                                                    $column1 = $key3;
                                                                                                                    $column2 =  $value4;
                                                                                                                    $paramsMysql2['post'] = $this->baseService->baseEncoding($column1);
                                                                                                                    $paramsMysql2['offset'] = '0';
                                                                                                                    $paramsMysql2['pagesize'] = '30';
                                                                                                                    $paramsMysql2['issearch'] = null;
                                                                                                                    $paramsMysql2['type'] = 'mysql';
                                                                                                                    $paramsMysql2['restriction'] = 'selected_brand_part';
                                                                                                                    $paramsMysql2['postchild'] = $column2;
                                                                                                                    $selected_part_result = $this->getResults($paramsMysql2, 'part_name');
                                                                                                                    $result_query2 = $selected_part_result['records']['record_count'];
                                                                                                                    if((int)$result_query2 != 0){
                                                                                                                        $column1 = $this->replaceDot($column1);
                                                                                                                        $finalBrandPartsWorking[$this->baseService->baseEncoding($column1)][] = $this->baseService->baseEncoding($column2);
                                                                                                                    }
                                                                                                                    else{
                                                                                                                        $column1 = $key3;
                                                                                                                        $finalBrandPartsRejected[$this->baseService->baseEncoding($column1)][] = $this->baseService->baseEncoding($column2);
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                            else{
                                                                                                                foreach($value3 as $key4 => $value4){
                                                                                                                    $column2 =  $value4;
                                                                                                                    $finalBrandPartsRejected[$this->baseService->baseEncoding($column1)][] = $this->baseService->baseEncoding($column2);
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                    $upload_record_working_table = array(
                                                                                                        $this->baseService->baseEncoding($selected_category_name) => $finalBrandPartsWorking
                                                                                                    );
                                                                                                    $upload_record_rejected_table = array(
                                                                                                        $this->baseService->baseEncoding($selected_category_name) => $finalBrandPartsRejected
                                                                                                    );
                                                                                                }
                                                                                            }
                                                                                            else if($category_name == 'partbrandtemplate'){
                                                                                                $finalPartBrandsWorking = array();
                                                                                                $finalPartBrandsRejected = array();
                                                                                                foreach ($checkResult['records'] as $key => $value) {
                                                                                                    foreach($value['records'] as $key2 => $value2){
                                                                                                        foreach($value2[$selected_category_name] as $key3 => $value3){
                                                                                                            $column1 = $key3;
                                                                                                            $paramsMysql['post'] = $this->baseService->baseEncoding($column1);
                                                                                                            $paramsMysql['offset'] = '0';
                                                                                                            $paramsMysql['pagesize'] = '30';
                                                                                                            $paramsMysql['issearch'] = null;
                                                                                                            $paramsMysql['type'] = 'mysql';
                                                                                                            $paramsMysql['restriction'] = 'selected_part';
                                                                                                            $selected_brand_result = $this->getResults($paramsMysql, 'part_name');
                                                                                                            $result_query = $selected_brand_result['records']['record_count'];
                                                                                                            if((int)$result_query != 0){
                                                                                                                foreach($value3 as $key4 => $value4){
                                                                                                                    $column1 = $key3;
                                                                                                                    $column2 =  $value4;
                                                                                                                    $paramsMysql2['post'] = $this->baseService->baseEncoding($column1);
                                                                                                                    $paramsMysql2['offset'] = '0';
                                                                                                                    $paramsMysql2['pagesize'] = '30';
                                                                                                                    $paramsMysql2['issearch'] = null;
                                                                                                                    $paramsMysql2['type'] = 'mysql';
                                                                                                                    $paramsMysql2['restriction'] = 'selected_part_brand';
                                                                                                                    $paramsMysql2['postchild'] = $column2;
                                                                                                                    $selected_part_result = $this->getResults($paramsMysql2, 'brand_name');
                                                                                                                    $result_query2 = $selected_part_result['records']['record_count'];
                                                                                                                    if((int)$result_query2 != 0){
                                                                                                                        $column1 = $this->replaceDot($column1);
                                                                                                                        $finalPartBrandsWorking[$this->baseService->baseEncoding($column1)][] = $this->baseService->baseEncoding($column2);
                                                                                                                    }
                                                                                                                    else{
                                                                                                                        $column1 = $key3;
                                                                                                                        $finalPartBrandsRejected[$this->baseService->baseEncoding($column1)][] = $this->baseService->baseEncoding($column2);
                                                                                                                    }
                                                                                                                }   
                                                                                                            }
                                                                                                            else{
                                                                                                                foreach($value3 as $key4 => $value4){
                                                                                                                    $column2 =  $value4;
                                                                                                                    $finalPartBrandsRejected[$this->baseService->baseEncoding($column1)][] = $this->baseService->baseEncoding($column2);
                                                                                                                }   
                                                                                                            }
                                                                                                            
                                                                                                        }
                                                                                                    }
                                                                                                    $upload_record_working_table = array(
                                                                                                        $this->baseService->baseEncoding($selected_category_name) => $finalPartBrandsWorking
                                                                                                    );
                                                                                                    $upload_record_rejected_table = array(
                                                                                                        $this->baseService->baseEncoding($selected_category_name) => $finalPartBrandsRejected
                                                                                                    );
                                                                                                }
                                                                                            }
                                                                                            else if($category_name == 'skutemplate'){
                                                                                                $finalBrandSkusWorking = array();
                                                                                                $finalBrandSkusRejected = array();
                                                                                                foreach ($checkResult['records'] as $key => $value) {
                                                                                                    foreach($value['records'] as $key2 => $value2){
                                                                                                        foreach($value2[$selected_category_name] as $key3 => $value3){
                                                                                                            $column1 = $key3;
                                                                                                            if($column1 == 'usap'){
                                                                                                                foreach($value3 as $key4 => $value4){
                                                                                                                    $column2 = $key4;
                                                                                                                    $paramsMysql['post'] = $this->baseService->baseEncoding($column2);
                                                                                                                    $paramsMysql['offset'] = '0';
                                                                                                                    $paramsMysql['pagesize'] = '30';
                                                                                                                    $paramsMysql['issearch'] = null;
                                                                                                                    $paramsMysql['type'] = 'mysql';
                                                                                                                    $paramsMysql['restriction'] = 'selected_brand';
                                                                                                                    $selected_brand_result = $this->getResults($paramsMysql, 'brand_name');
                                                                                                                    $result_query = $selected_brand_result['records']['record_count'];
                                                                                                                    if((int)$result_query != 0){
                                                                                                                        foreach($value4 as $key5 => $value5){
                                                                                                                            $column2 = $key4;
                                                                                                                            $column3 = $value5;
                                                                                                                            $paramsMysql2['post'] = $this->baseService->baseEncoding($column2);
                                                                                                                            $paramsMysql2['offset'] = '0';
                                                                                                                            $paramsMysql2['pagesize'] = '30';
                                                                                                                            $paramsMysql2['issearch'] = null;
                                                                                                                            $paramsMysql2['type'] = 'mysql';
                                                                                                                            $paramsMysql2['restriction'] = 'selected_brand_sku_usap';
                                                                                                                            $paramsMysql2['postchild'] = $column3;
                                                                                                                            $selected_part_result = $this->getResults($paramsMysql2, 'brand_name');
                                                                                                                            $result_query2 = $selected_part_result['records']['record_count'];
                                                                                                                            if((int)$result_query2 != 0){
                                                                                                                                $column2 = $this->replaceDot($column2);
                                                                                                                                $finalBrandSkusWorking[$this->baseService->baseEncoding($column1)][$this->baseService->baseEncoding($column2)][] = $this->baseService->baseEncoding($column3);
                                                                                                                            }
                                                                                                                            else{
                                                                                                                                $column2 = $key4;
                                                                                                                                $finalBrandSkusRejected[$this->baseService->baseEncoding($column1)][$this->baseService->baseEncoding($column2)][] = $this->baseService->baseEncoding($column3);
                                                                                                                            }
                                                                                                                        }    
                                                                                                                    }
                                                                                                                    else{
                                                                                                                        foreach($value4 as $key5 => $value5){
                                                                                                                            $column3 = $value5;
                                                                                                                            $finalBrandSkusRejected[$this->baseService->baseEncoding($column1)][$this->baseService->baseEncoding($column2)][] = $this->baseService->baseEncoding($column3);
                                                                                                                        } 
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                            if($column1 == 'jcw'){
                                                                                                                foreach($value3 as $key4 => $value4){
                                                                                                                    $column2 = $key4;
                                                                                                                    $paramsMysql['post'] = $this->baseService->baseEncoding($column2);
                                                                                                                    $paramsMysql['offset'] = '0';
                                                                                                                    $paramsMysql['pagesize'] = '30';
                                                                                                                    $paramsMysql['issearch'] = null;
                                                                                                                    $paramsMysql['type'] = 'mysql';
                                                                                                                    $paramsMysql['restriction'] = 'selected_brand';
                                                                                                                    $selected_brand_result = $this->getResults($paramsMysql, 'brand_name');
                                                                                                                    $result_query = $selected_brand_result['records']['record_count'];
                                                                                                                    if((int)$result_query != 0){
                                                                                                                        foreach($value4 as $key5 => $value5){
                                                                                                                            $column2 = $key4;
                                                                                                                            $column3 = $value5;
                                                                                                                            $paramsMysql2['post'] = $this->baseService->baseEncoding($column2);
                                                                                                                            $paramsMysql2['offset'] = '0';
                                                                                                                            $paramsMysql2['pagesize'] = '30';
                                                                                                                            $paramsMysql2['issearch'] = null;
                                                                                                                            $paramsMysql2['type'] = 'mysql';
                                                                                                                            $paramsMysql2['restriction'] = 'selected_brand_sku_jcw';
                                                                                                                            $paramsMysql2['postchild'] = $column3;
                                                                                                                            $selected_part_result = $this->getResults($paramsMysql2, 'brand_name');
                                                                                                                            $result_query2 = $selected_part_result['records']['record_count'];
                                                                                                                            if((int)$result_query2 != 0){
                                                                                                                                $column2 = $this->replaceDot($column2);
                                                                                                                                $finalBrandSkusWorking[$this->baseService->baseEncoding($column1)][$this->baseService->baseEncoding($column2)][] = $this->baseService->baseEncoding($column3);
                                                                                                                            }
                                                                                                                            else{
                                                                                                                                $column2 = $key4;
                                                                                                                                $finalBrandSkusRejected[$this->baseService->baseEncoding($column1)][$this->baseService->baseEncoding($column2)][] = $this->baseService->baseEncoding($column3);
                                                                                                                            }
                                                                                                                        }    
                                                                                                                    }
                                                                                                                    else{
                                                                                                                        foreach($value4 as $key5 => $value5){
                                                                                                                            $column3 = $value5;
                                                                                                                            $finalBrandSkusRejected[$this->baseService->baseEncoding($column1)][$this->baseService->baseEncoding($column2)][] = $this->baseService->baseEncoding($column3);
                                                                                                                        } 
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                            if($column1 == 'stt'){
                                                                                                                foreach($value3 as $key4 => $value4){
                                                                                                                    $column2 = $key4;
                                                                                                                    $paramsMysql['post'] = $this->baseService->baseEncoding($column2);
                                                                                                                    $paramsMysql['offset'] = '0';
                                                                                                                    $paramsMysql['pagesize'] = '30';
                                                                                                                    $paramsMysql['issearch'] = null;
                                                                                                                    $paramsMysql['type'] = 'mysql';
                                                                                                                    $paramsMysql['restriction'] = 'selected_brand';
                                                                                                                    $selected_brand_result = $this->getResults($paramsMysql, 'brand_name');
                                                                                                                    $result_query = $selected_brand_result['records']['record_count'];
                                                                                                                    if((int)$result_query != 0){
                                                                                                                        foreach($value4 as $key5 => $value5){
                                                                                                                            $column2 = $key4;
                                                                                                                            $column3 = $value5;
                                                                                                                            $paramsMysql2['post'] = $this->baseService->baseEncoding($column2);
                                                                                                                            $paramsMysql2['offset'] = '0';
                                                                                                                            $paramsMysql2['pagesize'] = '30';
                                                                                                                            $paramsMysql2['issearch'] = null;
                                                                                                                            $paramsMysql2['type'] = 'mysql';
                                                                                                                            $paramsMysql2['restriction'] = 'selected_brand_sku_stt';
                                                                                                                            $paramsMysql2['postchild'] = $column3;
                                                                                                                            $selected_part_result = $this->getResults($paramsMysql2, 'brand_name');
                                                                                                                            $result_query2 = $selected_part_result['records']['record_count'];
                                                                                                                            if((int)$result_query2 != 0){
                                                                                                                                $column2 = $this->replaceDot($column2);
                                                                                                                                $finalBrandSkusWorking[$this->baseService->baseEncoding($column1)][$this->baseService->baseEncoding($column2)][] = $this->baseService->baseEncoding($column3);
                                                                                                                            }
                                                                                                                            else{
                                                                                                                                $column2 = $key4;
                                                                                                                                $finalBrandSkusRejected[$this->baseService->baseEncoding($column1)][$this->baseService->baseEncoding($column2)][] = $this->baseService->baseEncoding($column3);
                                                                                                                            }
                                                                                                                        }
                                                                                                                    }
                                                                                                                    else{
                                                                                                                        foreach($value4 as $key5 => $value5){
                                                                                                                            $column3 = $value5;
                                                                                                                            $finalBrandSkusRejected[$this->baseService->baseEncoding($column1)][$this->baseService->baseEncoding($column2)][] = $this->baseService->baseEncoding($column3);
                                                                                                                        } 
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                    $upload_record_working_table = array(
                                                                                                        $this->baseService->baseEncoding($selected_category_name) => $finalBrandSkusWorking
                                                                                                    );
                                                                                                    $upload_record_rejected_table = array(
                                                                                                        $this->baseService->baseEncoding($selected_category_name) => $finalBrandSkusRejected
                                                                                                    );
                                                                                                }
                                                                                            }
                                                                                            else{
                                                                                                $selected_category_name = 'error';  
                                                                                            }
                                                                                        }
                                                                                                    
                                                                                        /* START - SAVING THE TEXTFILE CONTENT TO WORKING TABLE */
                                                                                        $param_working_table['query'][] = array_merge($newRecord,$upload_record_working_table);
                                                                                        $param_working_table['type'] = 'template';
                                                                                        $param_working_table['decode'] = true;
                                                                                        $result_working_table = $this->_hydraConnect($param_working_table, 'coupon', 'create');
                                                                                        $this->log($posts, "newtemplate");
                                                                                        /* END - SAVING THE TEXTFILE CONTENT TO WORKING TABLE */

                                                                                        /* START - SAVING THE TEXTFILE CONTENT TO REJECTED TEMPORARY TABLE */
                                                                                        $param_rejected['query'][] = array_merge($newRecord,$upload_record_rejected_table);
                                                                                        $param_rejected['type'] = 'templaterejectedtemp';
                                                                                        $param_rejected['decode'] = true;
                                                                                        $result = $this->_hydraConnect($param_rejected, 'coupon', 'create');
                                                                                        /* END - SAVING THE TEXTFILE CONTENT TO REJECTED TEMPORARY TABLE */

                                                                                        /* START - DELETE ALL THE FILES INSIDE DIRECTORY FOLDER */
                                                                                        if ($clear_dirhandler = opendir($import_file)) 
                                                                                        {
                                                                                            while ($clear_file = readdir($clear_dirhandler)) 
                                                                                            {
                                                                                                $delete_file[] = $import_file.$clear_file;
                                                                                                foreach ( $delete_file as $file_delete ) {
                                                                                                    unlink( $file_delete );
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                        /* END - DELETE ALL THE FILES INSIDE DIRECTORY FOLDER */

                                                                                        /* START - UPDATING THE UPLOADING STATUS TABLE */
                                                                                        $checkUploadStatUpdate['query'][] = array("set" => array("stat" => 0), "where" => array("statName" => "uploader"));
                                                                                        $checkUploadStatUpdate['type'] = 'templateuploadstat';
                                                                                        //$checkUploadStatUpdate['decode'] = true;
                                                                                        $result_uploading = $this->_hydraConnect($checkUploadStatUpdate, 'coupon', 'update');
                                                                                        /* END - UPDATING THE UPLOADING STATUS TABLE */

                                                                                        /* START - SHOW THE LIST OF TEMPLATE */
                                                                                        return $this->_buildReply($result, $templateForm, 'create');
                                                                                        /* END - SHOW THE LIST OF TEMPLATE */ 
                                                                                    }    
                                                                                    
                                                                                }
                                                                            }
                                                                            /* END - OPEN THE CSV FILE CONTENT */
                                                                        }
                                                                        else{
                                                                            /* START - UPDATING THE UPLOADING STATUS TABLE */
                                                                            $checkUploadStatUpdate['query'][] = array("set" => array("stat" => 0), "where" => array("statName" => "uploader"));
                                                                            $checkUploadStatUpdate['type'] = 'templateuploadstat';
                                                                            //$checkUploadStatUpdate['decode'] = true;
                                                                            $result_uploading = $this->_hydraConnect($checkUploadStatUpdate, 'coupon', 'update');
                                                                            /* END - UPDATING THE UPLOADING STATUS TABLE */

                                                                            $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                                            return array(
                                                                                'form' => $repopulatedForm,
                                                                                'addmessage' => "Invalid file."
                                                                            );
                                                                        }
                                                                    }
                                                                    /* END - READ THE CSV FILES ONLY */
                                                                }
                                                                /* END - LOOPING THE FILES */
                                                            }
                                                            /* END - OPEN THE DIRECTORY */  
                                                        }
                                                        /* START - CHECKING THE DIRECTORY  */
                                                    }
                                                    else{
                                                        print_r($errors);
                                                    }
                                                    /* END - VALID FILE UPLOADING */
                                                }
                                                else{
                                                    /* START - UPDATING THE UPLOADING STATUS TABLE */
                                                    $checkUploadStatUpdate['query'][] = array("set" => array("stat" => 0), "where" => array("statName" => "uploader"));
                                                    $checkUploadStatUpdate['type'] = 'templateuploadstat';
                                                    //$checkUploadStatUpdate['decode'] = true;
                                                    $result_uploading = $this->_hydraConnect($checkUploadStatUpdate, 'coupon', 'update');
                                                    /* END - UPDATING THE UPLOADING STATUS TABLE */

                                                    $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                    return array(
                                                        'form' => $repopulatedForm,
                                                        'addmessage' => "No file selected."
                                                    );
                                                }     
                                            } else {
                                                /* START - UPDATING THE UPLOADING STATUS TABLE */
                                                $checkUploadStatUpdate['query'][] = array("set" => array("stat" => 0), "where" => array("statName" => "uploader"));
                                                $checkUploadStatUpdate['type'] = 'templateuploadstat';
                                                //$checkUploadStatUpdate['decode'] = true;
                                                $result_uploading = $this->_hydraConnect($checkUploadStatUpdate, 'coupon', 'update');
                                                /* END - UPDATING THE UPLOADING STATUS TABLE */

                                                $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                                return array(
                                                    'form' => $repopulatedForm,
                                                    'addmessage' => "No file selected."
                                                );
                                            }
                                            /* END - INITIALIZE UPLOADING */
                                        }
                                        else {
                                            /* START - UPDATING THE UPLOADING STATUS TABLE */
                                            $checkUploadStatUpdate['query'][] = array("set" => array("stat" => 0), "where" => array("statName" => "uploader"));
                                            $checkUploadStatUpdate['type'] = 'templateuploadstat';
                                            //$checkUploadStatUpdate['decode'] = true;
                                            $result_uploading = $this->_hydraConnect($checkUploadStatUpdate, 'coupon', 'update');
                                            /* END - UPDATING THE UPLOADING STATUS TABLE */

                                            $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                            return array(
                                                'form' => $repopulatedForm,
                                                'addmessage' => "Error on query the uploading status table"
                                            );
                                        }
                                    }
                                    else{
                                        $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);
                                        return array(
                                            'form' => $repopulatedForm,
                                            'addmessage' => "There is currently uploading",
                                        );
                                    }
                                }
                            }
                        }    
                    }
                    else{
                        echo "b";
                    }
                }
            } else {
                $repopulatedForm = $this->_populateFormDataUpload($request, $templateForm);

                return array(
                    'form' => $repopulatedForm
                );
            }
        } else {

            $templateForm->buildForm();
            return array(
                'form' => $templateForm
            );
        }
    }

    public function getTemplateRejected($request) {
        $checkParam['type'] = 'templaterejectedtemp';
        $checkParam['query'] = array("templateName" => $request);
        $checkResult = $this->_hydraConnect($checkParam);
        return $checkResult['records']['result'];
    }

    public function deleteTemplate($request) {

        $id = $request->getParam('id');
        if (isset($id)) {
            $param = array();
            $param['query'] = array("_id" => trim($id));
            $param['type'] = 'template';
            $result = $this->_hydraConnect($param, 'coupon', 'read');

            if (isset($result['result']['result']) && count($result['result']['result']['record_count']) == 1) {

                $templateName = $result['result']['result']['records'][0]['templateName'];
                $templateRecord = $result['result']['result']['records'][0];
                $paramTemplateCouponSearch = array();
                $paramTemplateCouponSearch['query'] = array("restrictions" => trim($templateName));
                $paramTemplateCouponSearch['type'] = 'coupon';
                $paramResult = $this->_hydraConnect($paramTemplateCouponSearch, 'coupon', 'read', 'live');
                $paramResultWorking = $this->_hydraConnect($paramTemplateCouponSearch, 'coupon', 'read', 'working');

                if (isset($paramResult['result']['result']) && $paramResult['result']['result']['record_count'] >= 1) {
                    return array(
                        'addmessage' => 'You cannot delete this template. One or more coupons are using this template.',
                        'redirect' => '/dcms/template'
                    );
                } elseif (isset($paramResultWorking['result']['result']) && $paramResultWorking['result']['result']['record_count'] >= 1) {
                    return array(
                        'addmessage' => 'You cannot delete this template. One or more coupons are using this template.',
                        'redirect' => '/dcms/template'
                    );
                } else {
                    $this->_hydraConnect($param, 'coupon', 'delete', 'live');
                    unset($param);
                    $param['type'] = 'template';
//                    $param['decode'] = true;
                    $templateRecord['deleted'] = (bool) true;
                    unset($templateRecord['id']);
//                    $encodedRecord = $this->baseService->arrayRecursiveEncode($templateRecord);
//                    $param['query'][] = array("set" => $encodedRecord, "where" => array($this->baseService->baseEncoding("_id") => $this->baseService->baseEncoding($id)));
                    $param['query'][] = array("set" => $templateRecord, "where" => array("_id" => $id));
                    $templateUpdate = $this->_hydraConnect($param, 'coupon', 'update');

                    if (isset($templateUpdate['result']['result'])) {
                        $this->log(array('template_name' => $templateName), "deletetemplate");
                        return array(
                            'addmessage' => "Template successfully deleted.",
                            'redirect' => "/dcms/template"
                        );
                    } else {
                        if (!is_string($templateUpdate) && isset($templateUpdate['result']['error']['database_result'])) {
                            return array(
                                'addmessage' => "An error occured while deleting the template.",
                                'redirect' => "/dcms/template"
                            );
                        } else {
                            return array(
                                'addmessage' => "An error occured while deleting the template.",
                                'redirect' => "/dcms/template"
                            );
                        }
                    }
                }
            } else {
                return array(
                    'addmessage' => 'Invalid id.',
                    'redirect' => '/dcms/template'
                );
            }
        } else {
            return array(
                'addmessage' => 'Invalid id.',
                'redirect' => '/dcms/template'
            );
        }
    }

    private function _populateFormData($request, $templateForm) {

        $restriction_part_selected = $request->getParam('restriction_part_selected');
        if (count($restriction_part_selected) > 0) {
            foreach ($restriction_part_selected as $selected) {
                $templateForm->getElement('restriction_part_selected')->addMultiOptions(array($selected => $selected));
            }
        }
        $restriction_brand_selected = $request->getParam('restriction_brand_selected');
        if (count($restriction_brand_selected) > 0) {
            foreach ($restriction_brand_selected as $selected) {
                $templateForm->getElement('restriction_brand_selected')->addMultiOptions(array($selected => $selected));
            }
        }
        $restriction_brand_part_selected = $request->getParam('restriction_brand_part_selected');
        if (count($restriction_brand_part_selected) > 0) {
            foreach ($restriction_brand_part_selected as $selected) {
                $templateForm->getElement('restriction_brand_part_selected')->addMultiOptions(array($selected => str_replace('|', ' -- ', $selected)));
            }
        }
        $restriction_part_brand_selected = $request->getParam('restriction_part_brand_selected');
        if (count($restriction_part_brand_selected) > 0) {
            foreach ($restriction_part_brand_selected as $selected) {
                $templateForm->getElement('restriction_part_brand_selected')->addMultiOptions(array($selected => str_replace('|', ' -- ', $selected)));
            }
        }

        $restriction_sku_selected = $request->getParam('restriction_sku_selected');
        if (count($restriction_sku_selected) > 0) {
            foreach ($restriction_sku_selected as $selected) {
                $innerValue = explode('|', $selected);
                $templateForm->getElement('restriction_sku_selected')->addMultiOptions(array($selected => trim(strtoupper($innerValue[1])) . ' -- ' . trim($innerValue[0]) . ' -- ' . trim($innerValue[2])));
            }
        }
        return $templateForm;
    }

    # Name: Del Date: Jun 2016
    private function _populateFormDataUpload($request, $templateForm) {

        $restriction_part_selected = $request->getParam('restriction_part_selected');
        if (count($restriction_part_selected) > 0) {
            foreach ($restriction_part_selected as $selected) {
                $templateForm->getElement('restriction_part_selected')->addMultiOptions(array($selected => $selected));
            }
        }
        $restriction_brand_selected = $request->getParam('restriction_brand_selected');
        if (count($restriction_brand_selected) > 0) {
            foreach ($restriction_brand_selected as $selected) {
                $templateForm->getElement('restriction_brand_selected')->addMultiOptions(array($selected => $selected));
            }
        }
        $restriction_brand_part_selected = $request->getParam('restriction_brand_part_selected');
        if (count($restriction_brand_part_selected) > 0) {
            foreach ($restriction_brand_part_selected as $selected) {
                $templateForm->getElement('restriction_brand_part_selected')->addMultiOptions(array($selected => str_replace('|', ' -- ', $selected)));
            }
        }
        $restriction_part_brand_selected = $request->getParam('restriction_part_brand_selected');
        if (count($restriction_part_brand_selected) > 0) {
            foreach ($restriction_part_brand_selected as $selected) {
                $templateForm->getElement('restriction_part_brand_selected')->addMultiOptions(array($selected => str_replace('|', ' -- ', $selected)));
            }
        }

        $restriction_sku_selected = $request->getParam('restriction_sku_selected');
        if (count($restriction_sku_selected) > 0) {
            foreach ($restriction_sku_selected as $selected) {
                $innerValue = explode('|', $selected);
                $templateForm->getElement('restriction_sku_selected')->addMultiOptions(array($selected => trim(strtoupper($innerValue[1])) . ' -- ' . trim($innerValue[0]) . ' -- ' . trim($innerValue[2])));
            }
        }
        return $templateForm;
    }

    private function _buildRecord($templateForm, $hasCreatedDate = false, $hasModifiedDate = false) {

        $restriction_part_selected = $templateForm->getValue('restriction_part_selected');
        $restriction_brand_part_selected = $templateForm->getValue('restriction_brand_part_selected');
        $restriction_brand_selected = $templateForm->getValue('restriction_brand_selected');
        $restriction_part_brand_selected = $templateForm->getValue('restriction_part_brand_selected');

        $finalResultSkus = $templateForm->getValue('restriction_sku_selected');

        $finalParts = array();
        if (isset($restriction_part_selected) && count($restriction_part_selected) > 0) {
            foreach ($restriction_part_selected as $part) {
                $finalParts[] = $this->baseService->baseEncoding($part);
            }
        }

        $finalBrands = array();
        if (isset($restriction_brand_selected) && count($restriction_brand_selected) > 0) {
            foreach ($restriction_brand_selected as $brand) {
                $finalBrands[] = $this->baseService->baseEncoding($brand);
            }
        }

        $finalBrandParts = array();
        if (isset($restriction_brand_part_selected) && count($restriction_brand_part_selected) > 0) {
            foreach ($restriction_brand_part_selected as $brandpart) {
                $explodedBrandPart = explode('|', $brandpart);
                $brand = trim(str_replace(".", "-dot-", $explodedBrandPart[0]));
                $part = trim($explodedBrandPart[1]);
                $finalBrandParts[$this->baseService->baseEncoding($brand)][] = $this->baseService->baseEncoding($part);
            }
        }

        $finalPartBrands = array();
        if (isset($restriction_part_brand_selected) && count($restriction_part_brand_selected) > 0) {
            foreach ($restriction_part_brand_selected as $partbrand) {
                $explodedPartBrand = explode('|', $partbrand);
                $part = trim($explodedPartBrand[0]);
		$part = str_replace(".", "-dot-", $part);
                $brand = trim($explodedPartBrand[1]);
                $finalPartBrands[$this->baseService->baseEncoding($part)][] = $this->baseService->baseEncoding($brand);
            }
        }

        $finalSkus = array();
        if (isset($finalResultSkus) && count($finalResultSkus) > 0) {
            foreach ($finalResultSkus as $sku) {
                $explodedSku = explode('|', $sku);
                $brandName = trim(str_replace(".","-dot-",$explodedSku[0]));
                $encodedBrand = $this->baseService->baseEncoding($brandName);
                $site = trim(strtolower($explodedSku[1]));
                $sku = trim($explodedSku[2]);
                $encodedSku = $this->baseService->baseEncoding($sku);
                if (count($explodedSku) == 3) {
                    if ($site == 'usap') {
                        $finalSkus[$this->baseService->baseEncoding("usap")][$encodedBrand][] = $encodedSku;
                    } elseif ($site == 'jcw') {
                        $finalSkus[$this->baseService->baseEncoding("jcw")][$encodedBrand][] = $encodedSku;
                    } elseif ($site == 'stt') {
                        $finalSkus[$this->baseService->baseEncoding("stt")][$encodedBrand][] = $encodedSku;
                    }
                }
            }
        }

        $record = array();
        $record = array(
            $this->baseService->baseEncoding("templateName") => $this->baseService->baseEncoding($templateForm->getValue('template_name')),
            $this->baseService->baseEncoding("type") => $this->baseService->baseEncoding($templateForm->getValue('exinradio')),
            $this->baseService->baseEncoding("brands") => $finalBrands,
            $this->baseService->baseEncoding("parts") => $finalParts,
            $this->baseService->baseEncoding("brandPart") => $finalBrandParts,
            $this->baseService->baseEncoding("partBrand") => $finalPartBrands,
            $this->baseService->baseEncoding("brandSku") => $finalSkus,
            $this->baseService->baseEncoding("creator") => $this->baseService->baseEncoding($this->user),
            $this->baseService->baseEncoding("modified") => (bool) true
        );
        ($hasCreatedDate === true) ? $record[$this->baseService->baseEncoding("createdAt")] = time() : null;
        ($hasModifiedDate === true) ? $record[$this->baseService->baseEncoding("updatedAt")] = time() : null;
        return $record;
    }

    # Name: Del Date: Jun 2016
    private function _buildRecordUpload($templateForm, $hasCreatedDate = false, $hasModifiedDate = false) {

        $restriction_part_selected = $templateForm->getValue('restriction_part_selected');
        $restriction_brand_part_selected = $templateForm->getValue('restriction_brand_part_selected');
        $restriction_brand_selected = $templateForm->getValue('restriction_brand_selected');
        $restriction_part_brand_selected = $templateForm->getValue('restriction_part_brand_selected');

        $finalResultSkus = $templateForm->getValue('restriction_sku_selected');

        $finalSkus = array();
        if (isset($finalResultSkus) && count($finalResultSkus) > 0) {
            foreach ($finalResultSkus as $sku) {
                $explodedSku = explode('|', $sku);
                $brandName = trim($explodedSku[0]);
                $encodedBrand = $this->baseService->baseEncoding($brandName);
                $site = trim(strtolower($explodedSku[1]));
                $sku = trim($explodedSku[2]);
                $encodedSku = $this->baseService->baseEncoding($sku);
                if (count($explodedSku) == 3) {
                    if ($site == 'usap') {
                        $finalSkus[$this->baseService->baseEncoding("usap")][$encodedBrand][] = $encodedSku;
                    } elseif ($site == 'jcw') {
                        $finalSkus[$this->baseService->baseEncoding("jcw")][$encodedBrand][] = $encodedSku;
                    } elseif ($site == 'stt') {
                        $finalSkus[$this->baseService->baseEncoding("stt")][$encodedBrand][] = $encodedSku;
                    }
                }
            }
        }

        $record = array();
        $record = array(
            $this->baseService->baseEncoding("templateName") => $this->baseService->baseEncoding($templateForm->getValue('template_name')),
            $this->baseService->baseEncoding("type") => $this->baseService->baseEncoding($templateForm->getValue('exinradio')),
            $this->baseService->baseEncoding("creator") => $this->baseService->baseEncoding($this->user),
            $this->baseService->baseEncoding("modified") => (bool) true
        );
        ($hasCreatedDate === true) ? $record[$this->baseService->baseEncoding("createdAt")] = time() : null;
        ($hasModifiedDate === true) ? $record[$this->baseService->baseEncoding("updatedAt")] = time() : null;
        return $record;
    }

    public function listing($postArray) {

        $isSearch = $this->getRequestValue($postArray, 'search');
        $post = $this->getRequestValue($postArray, 'post');
        $params = array();
        $params = array(
            'post' => $this->baseService->baseEncoding($post),
            'offset' => $this->getRequestValue($postArray, 'offset'),
            'pagesize' => $this->getRequestValue($postArray, 'pagesize'),
            'issearch' => $isSearch,
//            'element'  => $this->getRequestValue($postArray, 'element') 
        );
        (isset($postArray['parentpost'])) ? $params['parentpost'] = $this->baseService->baseEncoding($this->getRequestValue($postArray, 'parentpost')) : '';
        (isset($postArray['selected'])) ? $this->selected = $postArray['selected'] : '';
        return $params;
    }

    public function getRequestValue($arrayPost, $key) {

        if (trim($key) == 'offset' && isset($arrayPost['offset']) && is_array($arrayPost['offset'])) {
            $params = array();
            (isset($arrayPost['offset']['usap']) && trim($arrayPost['offset']['usap']) != "") ? $params['usap'] = $arrayPost['offset']['usap'] : '';
            (isset($arrayPost['offset']['jcw']) && trim($arrayPost['offset']['jcw']) != "") ? $params['jcw'] = $arrayPost['offset']['jcw'] : '';
            (isset($arrayPost['offset']['stt']) && trim($arrayPost['offset']['stt']) != "") ? $params['stt'] = $arrayPost['offset']['stt'] : '';
            return $params;
        } else {
            return (isset($arrayPost[$key]) && trim($arrayPost[$key]) != "") ? $arrayPost[$key] : null;
        }
    }

    public function getResults($params, $fieldColumnIdentifier, $secondDegree = null, $isSkuResult = false) {
        $results = $this->_hydraConnect($params);        
        if (is_string($results)) {
            return $results;
        }
        
        if ((isset($this->selected)) && (count($this->selected) > 0)) {
            $this->hasSelectedRestriction = true;
        }
        $newHaystack = $this->_needleHaystack($results, $fieldColumnIdentifier, $secondDegree, $isSkuResult);
        return $newHaystack;
    }

    private function _needleHaystack($hydraResults, $fieldColumnIdentifier, $secondDegree = null, $isSkuResult = false) {

        if ($this->hasSelectedRestriction) {
            if ($isSkuResult) {
                $siteSkuIsolation = $this->_siteSkuIsolation();
                if (count($siteSkuIsolation) > 0) {
                    if (isset($hydraResults['records']) && count($hydraResults['records']) > 0) {
                        foreach ($hydraResults['records'] as $perSiteRecords => $perSiteValues) {
                            foreach ($perSiteValues as $recordKey => $recordValue) {
                                if (in_array(trim($recordValue['sku']), $siteSkuIsolation[$perSiteRecords])) {
                                    unset($hydraResults['records'][$perSiteRecords][$recordKey]);
                                }
                            }
                        }
                    }
                }
                return array('results' => $hydraResults, 'skussites' => $siteSkuIsolation);
            } else {

                if ($secondDegree != null) {
                    if ($secondDegree == 'brandpart') {
                        foreach ($hydraResults['records'] as $key => $value) {

                            if (in_array(trim($value['brand_name'] . '|' . $value['part_name']), $this->selected)) {
                                unset($hydraResults['records'][$key]);
                            }
                        }
                    }
                    if ($secondDegree == 'partbrand') {
                        foreach ($hydraResults['records'] as $key => $value) {

                            if (in_array(trim($value['part_name'] . '|' . $value['brand_name']), $this->selected)) {
                                unset($hydraResults['records'][$key]);
                            }
                        }
                    }
                } else {
                    foreach ($hydraResults['records'] as $key => $value) {
                        if (in_array(trim($value["{$fieldColumnIdentifier}"]), $this->selected)) {
                            unset($hydraResults['records'][$key]);
                        }
                    }
                    return $hydraResults;
                }
            }
        }
        return $hydraResults;
    }

    private function _siteSkuIsolation() {

        $finalResults = array();
        $usapRestriction = array();
        $jcwRestriction = array();
        $sttRestriction = array();

        foreach ($this->selected as $restriction) {

            $expRestriction = explode('|', $restriction);

            if (count($expRestriction) == 3) {
                $site = trim(strtolower($expRestriction[1]));
                if ($site == 'usap') {
                    $usapRestriction[] = $expRestriction[2];
                } elseif ($site == 'jcw') {
                    $jcwRestriction[] = $expRestriction[2];
                } elseif ($site == 'stt') {
                    $sttRestriction[] = $expRestriction[2];
                }
            }
        }

        ((isset($usapRestriction)) && (count($usapRestriction) > 0)) ? $finalResults['usap'] = $usapRestriction : '';
        ((isset($jcwRestriction)) && (count($jcwRestriction) > 0)) ? $finalResults['jcw'] = $jcwRestriction : '';
        ((isset($sttRestriction)) && (count($sttRestriction) > 0)) ? $finalResults['stt'] = $sttRestriction : '';

        return $finalResults;
    }

    public function _hydraConnect($data = null, $serviceIdentifier = 'coupon', $method = 'read', $env = 'working') {

        $config = $this->config->service->toArray();

        try {
            $data['env'] = $env;
            $serviceObject = $config['sourceobject']["{$serviceIdentifier}"];
            $getResults = Hydra_Helper::loadClass(
                            $serviceObject['url'], $serviceObject['version'], $serviceObject['service'], $method, $data, $serviceObject['httpmethod'], $serviceObject['id'], $serviceObject['format']
            );    
            if (isset($getResults['_payload']['result'])) {
                return array(
                    'record_count' => $getResults['_payload']['result']["{$method}"],
                    'records' => $getResults['_payload']['result']["{$method}"],
                    'result' => $getResults['_payload']['result']["{$method}"]
                );
            }
            return 'An error occured while requesting on the Coupon API.';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /*
     * {
      "_id": ObjectId("504a89dc8a1ee8f36c0009b6"),
      "code": "25WHOMEXZCGT34N",
      "type": "newcoupon",
      "transDetails": "",
      "transData": {
      "1": "10.10.214.91",
      "2": "zsalud.usaptool.dev.usautoparts.com"
      },
      "user": "zsalud",
      "date": {
      "sec": 1347062235,
      "usec": 824000
      },
      "remarks": "",
      "status": "published",
      "couponType": "onetimeuse"
      }
      string| authentication, upload, newtemplate, edittemplate, deletetemplate, etc.>
     */

    public function log($values, $type) {
        $details = array(
            'code' => $values['template_name'],
            'type' => $type,
            'transDetails' => "",
            'transData' =>
            array(
                "1" => $_SERVER['REMOTE_ADDR'],
                "2" => $_SERVER['SERVER_NAME'],
            ),
            'user' => $this->user,
            'date' => time(),
            'log_date' => time(),
            'remarks' => "",
            'status' => (isset($values['publish']) && $values['publish'] == true) ? "published" : "testing",
            'couponType' => "template"
        );
        return $this->baseService->hydraConnect(array($details), "logs", "create", "live");
    }

    public function replaceDot($subject){
        $search = ".";
        $replace = "-dot-";
        $output = str_replace($search, $replace, $subject);    
        return $output;
    }    
}
?>