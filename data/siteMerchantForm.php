

<?php /*tegory USAPTool_Modules
 * @package User_Form
 * @author thawkins
 * @copyright 2011
 * @version $Id$
 */
class Feeds_Form_siteMerchantForm extends USAP_Form
{

    public function init()
    {
        parent::init(); 
            $this->addElement('text', 'Site', array(
                'decorators' => $this->getElementDecorators(),
                'label' => 'Site:',
                'filters' => array(
                    array('StripTags'),
                    array('StringTrim')
                ),
                'validators' => array(
                    array('NotEmpty')
                ),
                'required' => true,
                'value' => isset($this->_data['site']) ? $this->_data['site'] : "",
                'attribs' => array(
                    'size' => 30,
                    'class' => 'uniform'
                )
                    )
            );



            //$this->addRoleSelect();
        


	//$this->addPermissionSelect2('Sites', "Select one or more Sites", false);


        $this->addDisplayGroup(
                array('Site', 'role','addmerchant-submitted'), 'generalgroup', array(
            'disableLoadDefaultDecorators' => true,
            'decorators' => $this->getGroupDecorators(),
            'legend' => "Site Creation",
            'description' =>
            'Create a Site with one or multiple merchants.'
                )
        );

        // $this->addPermissionSelect2('Sites', "Select one or more Sites", false);
	 $this->addPermissionSelect2('Merchant', "Select one or more Sites", false);
        $this->addSubmitButtons('Add');
    }

     public function addPermissionSelect2($control = 'role', $label = "Select Role", $single = true, $readonly = false)
    {
	$mongo = Zend_Registry::get('mongo');
	$merchant = $mongo->selectDB('zFeeds')->selectCollection('merchant')->find(array("status" =>"1" ));
		foreach ($merchant as $doc) {
 		   $contextOptions[(string) $doc["_id"]] = $doc["name"]; 
		  // $contextOptions["2313131"] = (string) $doc["_id"];
		}
        $this->_addSelectControl($contextOptions, $control, $label, $single, $readonly);
	
        //return $options;
    }

}


