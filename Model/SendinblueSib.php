<?php
/**
* @author Sendinblue plateform <contact@sendinblue.com>
* @copyright  2017-2018 Sendinblue
* URL:  https:www.sendinblue.com
* Do not edit or add to this file if you wish to upgrade Sendinblue Magento plugin to newer
* versions in the future. If you wish to customize Sendinblue magento plugin for your
* needs then we can't provide a technical support.
**/
namespace Sendinblue\Sendinblue\Model;

//use Sendinblue\Sendinblue\Api\Data\AdminSampleInterface;
use Sendinblue\Sendinblue\Helper\ConfigHelper;

class SendinblueSib extends \Magento\Framework\Model\AbstractModel
{
    /** @var  ConfigHelper */
    public $_resourceConfig;
    public $_getValueDefault;
    public $_scopeTypeDefault;
    public $_dir;
    public $_customers;
    public $_subscriber;
    public $_resource;
    public $_storeManagerInterface;
    public $_orderCollectionFactory;
    public $apiKey;
    public $_getTb;
    public $_storeId;
    public $_blocktemp;

    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeDefaultType,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Store\Model\Store $defaultStoreId,
        \Magento\Customer\Model\Customer $customers,
        \Magento\Newsletter\Model\ResourceModel\Subscriber $subscriber,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollectionFactory,
        \Magento\Framework\Setup\ModuleDataSetupInterface $getTb,
        \Magento\Framework\View\Element\Template $blocktemp
    )
    {
        $this->_orderCollectionFactory = $salesOrderCollectionFactory;
        $this->_resourceConfig = $resourceConfig;
        $this->_getValueDefault = $scopeDefaultType;
        $this->_scopeTypeDefault = $scopeDefaultType::SCOPE_TYPE_DEFAULT;
        $this->_storeId = $defaultStoreId::DEFAULT_STORE_ID;
        $this->_dir = $dir;
        $this->_customers = $customers;
        $this->_subscriber = $subscriber;
        $this->_resource = $resource;
        $this->_storeManagerInterface = $storeManagerInterface;
        $this->_getTb = $getTb;
        $this->_blocktemp = $blocktemp;
    }
    public function getCurrentUser()
    {
        $objectManager = $this->getObjRunTime();
        $admin = $objectManager->create('\Magento\Backend\Model\Auth\Session');
        return $admin->getUser()->getData();
    }

    /**
    * Description: folder create in Sendinblue after installing plugin in shopify.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: useremail, doubleoptinUrl, storeId ,shoplang
    * @return: smtp details
    * @updated: 09-June-2016
    */
    public function createFolderName($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->createAttributesName();
        $result = $this->checkFolderList();
        if ($result === false) {
            $data = array();
            $mailin = $this->createObjMailin($this->apiKey);
            $data = array( "name"=> "magento" );
            $folderResponce = $mailin->createFolder($data);
            $folderId = isset($folderResponce['data']['id']) ? $folderResponce['data']['id'] : '';
            $existList = '';
        } else {
            $folderId = $result['key'];
            $existList = $result['list_name'];
        }
        //Create the partner's name i.e. Shopify on Sendinblue platform
        $this->partnerMagento();
        // create list in Sendinblue
        $this->createNewList($folderId, $existList);
    }

    /**
     * Method is used to add the partner's name in Sendinblue.
     * In this case its "MAGENTO".
     */
    public function partnerMagento()
    {
        $mailinPartnerParameters = array();
        $mailin = $this->createObjMailin($this->apiKey);
        $mailinPartnerParameters['partner'] = 'MAGENTO';
        $mailin->updateMailinParter($mailinPartnerParameters);
    }

    /**
    * Description: Creates a list by the name "Shopify" on user's Sendinblue account.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: useremail, doubleoptinUrl, storeId ,shoplang
    * @return: smtp details
    * @updated: 12-June-2016
    */
    public function createNewList($response, $existList)
    {
        if ($existList != '') {
            $listName = 'magento_' . date('dmY');
        } else {
            $listName = 'magento';
        }
        
        $data = array();
        $mailin = $this->createObjMailin($this->apiKey);
        $data = array(
          "list_name" => $listName,
          "list_parent" => $response
        ); 
        $listResp = $mailin->createList($data);
        $this->_resourceConfig->saveConfig('sendinblue/selected_list_data', trim($listResp['data']['id']), $this->_scopeTypeDefault, $this->_storeId);
    }


    /**
    * This method is used used for check api status
    */
    public function checkApikey($key)
    {
        if(!empty($key))
        {
            $mailin = $this->createObjMailin($key);
            $keyResponse = $mailin->getAccount();
        }
        return $keyResponse;
    }

    Public function getObjRunTime()
    {
        $valObj =  \Magento\Framework\App\ObjectManager::getInstance();
        return $valObj;
    }

    /**
    * Description: Fetches all the list of the user from the Sendin platform.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: apiKey
    * @return: All folder and list id.
    * @updated: 12-June-2016
    */
    public function getResultListValue($key = false)
    {
        if (!$key) {
            $key = $this->_getValueDefault->getValue('sendinblue/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        if(!empty($key))
        {
            $mailin = $this->createObjMailin($key);
            $data = array( "page" => '',
              "page_limit" => ''
            );
 
            $listResp = $mailin->getLists($data);
            return $listResp;
        } 
    }

    /**
    * create object for access data from Sendinblue threw API call.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: apikey, sibApiUrl
    * @return: object
    * @updated: 2-agust-2016
    */
    public function createObjMailin($apiKey)
    {
        if (!empty($apiKey)) {
            $params['api_key'] = $apiKey;
            return new \Sendinblue\Sendinblue\Model\Mmailin($params);
        }
    }

    /**
    * Description: Method to factory reset the database value.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: key value
    * @return: true.
    * @updated: 22-June-2016
    */
    public function resetDataBaseValue()
    {
        $this->_resourceConfig->saveConfig('sendinblue/ord_track_status', 0, $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/order_import_status', 0, $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/api_smtp_status', 0, $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/first_request', '', $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/api_key', '', $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/selected_list_data', '', $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/confirm_type', '', $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/doubleoptin_redirect', '', $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/optin_url_check', '', $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/final_confirm_email', '', $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/optin_list_id', '', $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/final_template_id', '', $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/api_sms_shipment_status', 0, $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/api_sms_campaign_status', 0, $this->_scopeTypeDefault, $this->_storeId);
        $this->_resourceConfig->saveConfig('sendinblue/api_sms_order_status', 0, $this->_scopeTypeDefault, $this->_storeId);
    }

    /**
    * Description: reate Normal, Transactional, Calculated and Global attributes and their values
    * on Sendinblue platform. This is necessary for the Shopify to add subscriber's details.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: no
    * @return: true
    * @updated: 22-June-2016
    */
    public function createAttributesName()
    {
        $apiKey = !empty($this->apiKey) ? $this->apiKey : $this->getDbData('api_key');
        $valueLanguage = $this->getApiConfigValue();
        $this->updateDbData('sendin_config_lang', trim($valueLanguage->language));
        $this->updateDbData('sendin_date_format', trim($valueLanguage->date_format));
        $noramalAttribute = $this->allAttributesType($valueLanguage->language);        
        $transactionalAttributes = $this->allTransactionalAttributes();
        $calcAttribute = $this->attrCalculated(); 
        $globalAttribute = $this->attrGlobal();

        if ($valueLanguage->language == 'fr') {
            $dataAttr = array('PRENOM'=>'text','NOM'=>'text','CLIENT'=>'number','COMPANY'=>'text','SMS'=>'text','CITY'=>'text','COUNTRY'=>'text','POSTCODE'=>'text','PROVINCE_CODE'=>'text','COUNTRY_CODE'=>'text');
        } else {
            $dataAttr = array('NAME'=>'text','SURNAME'=>'text','CLIENT'=>'number','COMPANY'=>'text','SMS'=>'text','CITY'=>'text','COUNTRY'=>'text','POSTCODE'=>'text','PROVINCE_CODE'=>'text','COUNTRY_CODE'=>'text');
        }

        $mailin = $this->createObjMailin($apiKey);
        $normal = array( "type" => "normal",
        "data" => $noramalAttribute
        );
        $mailin->createAttribute($normal);

        $transactionalAttributes = array('ORDER_ID'=>'id','ORDER_DATE'=>'date','ORDER_PRICE'=>'number');
        $trans = array( "type" => "transactional",
        "data" => $transactionalAttributes
        );
        $mailin->createAttribute($trans);

        $calculatedValue = array( "type" => "calculated",
        "data" => $calcAttribute);
        $mailin->createAttribute($calculatedValue);

        $dataGlobal = array( "type" => "global",
        "data" => $globalAttribute);
        $mailin->createAttribute($dataGlobal);
    }

         /**
     * Fetch attributes and their values
     * on Sendinblue platform. This is necessary for the Prestashop to add subscriber's details.
     */
    public function allAttributesName()
    {
        $userLanguage = $this->_getValueDefault->getValue('sendinblue/sendin_config_lang', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($userLanguage == 'fr') {
            $attributesName = array('PRENOM'=>'firstname', 'NOM'=>'lastname', 'MAGENTO_LANG'=>'created_in','CLIENT'=>'client','SMS'=>'telephone','COMPANY'=>'company','CITY'=>'city','COUNTRY_ID'=>'country_id','POSTCODE'=>'postcode','STREET'=>'street','REGION'=>'region','STORE_ID'=>'store_id');
        }
        else {
            $attributesName = array('NAME'=>'firstname', 'SURNAME'=>'lastname', 'MAGENTO_LANG'=>'created_in','CLIENT'=>'client','SMS'=>'telephone','COMPANY'=>'company','CITY'=>'city','COUNTRY_ID'=>'country_id','POSTCODE'=>'postcode','STREET'=>'street','REGION'=>'region','STORE_ID'=>'store_id');
        }
        return $attributesName;
    }

    /**
    * Fetch attributes name and type
    * on Sendinblue platform. This is necessary for the Magento to add subscriber's details.
    */
    public function allAttributesType($langConfig)
    {
        if ($langConfig == 'fr') {
            $attributesType = array('PRENOM'=>'text', 'NOM'=>'text', 'MAGENTO_LANG'=>'text','CLIENT'=>'number','SMS'=>'text','COMPANY'=>'text','CITY'=>'text','COUNTRY_ID'=>'text','POSTCODE'=>'number','STREET'=>'text','REGION'=>'text','STORE_ID'=>'number');
        }
        else {
            $attributesType = array('NAME'=>'text', 'SURNAME'=>'text', 'MAGENTO_LANG'=>'text','CLIENT'=>'number','SMS'=>'text','COMPANY'=>'text','CITY'=>'text','COUNTRY_ID'=>'text','POSTCODE'=>'number','STREET'=>'text','REGION'=>'text','STORE_ID'=>'number');
        }
        return $attributesType;
    }

    public function attrCalculated() {
        $calcAttr = array('[{ "name":"MAGENTO_LAST_30_DAYS_CA", "value":"SUM[ORDER_PRICE,ORDER_DATE,>,NOW(-30)]" }, {"name":"MAGENTO_ORDER_TOTAL", "value":"COUNT[ORDER_ID]"}, {"name":"MAGENTO_CA_USER", "value":"SUM[ORDER_PRICE]"}]');
        return $calcAttr;
    }

    public function attrGlobal() {
        $globalAttr = array('[{ "name":"MAGENTO_CA_LAST_30DAYS", "value":"SUM[MAGENTO_LAST_30_DAYS_CA]" }, { "name":"MAGENTO_CA_TOTAL", "value":"SUM[ORDER_USER]"}, { "name":"MAGENTO_ORDERS_COUNT", "value":"SUM[MAGENTO_ORDER_TOTAL]"}]');
        return $globalAttr;
    }
    /**
    * Fetch all Transactional Attributes 
    * on Sendinblue platform. This is necessary for the Magento to add subscriber's details.
    */
    public function allTransactionalAttributes()
    {
        $transactionalAttributes = array('ORDER_ID'=>'id', 'ORDER_DATE'=>'date', 'ORDER_PRICE'=>'number');
        return $transactionalAttributes;
    }

    /**
    * Description: API config value from Sendinblue with date format.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: storeID
    * @return: config value from sib
    * @updated: 18-July-2016
    */
    public function getApiConfigValue()
    {
        $result = array();
        $apiKey = !empty($this->apiKey) ? $this->apiKey : $this->getDbData('api_key');
        if(!empty($apiKey)) {
            $mailin = $this->createObjMailin($apiKey);
            $result = $mailin->getPluginConfig();
        }
        return (object)$result['data'];
    }

    /**
    * Description: Fetches all folders and all list within each folder of the user's Sendinblue 
    * account and displays them to the user. 
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: apiKey
    * @return: all sib List ID with name
    * @updated: 12-June-2016
    */
    public function checkFolderList()
    {
        $data = array();
        $apiKey = !empty($this->apiKey) ? $this->apiKey : $this->getDbData('api_key');
        if ($apiKey == '') {
            return false;
        }
        $mailin = $this->createObjMailin($apiKey);
        $dataApi = array( "page" => 1,
          "page_limit" => 50
        );
        $listResp = $mailin->getFolders($dataApi);
        
        //folder id
        $sortArray = array();
        $returnFolderList = false;
        if (!empty($listResp['data']['folders'])) {
            foreach ($listResp['data']['folders'] as $value) {
                if (strtolower($value['name']) == 'magento') {
                    $sortArray['key'] = $value['id'];
                    $sortArray['list_name'] = $value['name'];
                    if (!empty($value['lists'])) {
                        foreach ($value['lists'] as $val) {
                            if (strtolower($val['name']) == 'magento') {
                                $sortArray['folder_name'] = $val['name'];
                            }
                        }
                    }
                }
            }

            if (count($sortArray) > 0) {
                $returnFolderList = $sortArray;
            } else {
                $returnFolderList = false;
            }
        }
        return $returnFolderList;
    }

    /**
    * Description: Method is used to send all the subscribers from Shopify to
    * Sendinblue for adding / updating purpose.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: Sib listId
    * @return: true
    * @updated: 12-June-2016
    */
    public function sendAllMailIDToSendin($listId)
    {
        $emailValue = $this->getSubscribeCustomer();
        $mediaUrl = $this->_storeManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $fileName = $this->getDbData('sendin_csv_file_name');
        $apiKey = !empty($this->apiKey) ? $this->apiKey : $this->getDbData('api_key');
        if ($emailValue > 0 && !empty($apiKey)) {
            $this->updateDbData('import_old_user_status', 1);
            $userDataInformation = array();
            $listIdVal = explode('|', $listId);
            $mailinObj = $this->createObjMailin($apiKey);
            $userDataInformation['key'] = $apiKey;
            $userDataInformation['url'] = $mediaUrl.'sendinblue_csv/'.$fileName.'.csv';
            $userDataInformation['listids'] = $listIdVal; // $list;
            $userDataInformation['notify_url'] = '';
            $responseValue = $mailinObj->importUsers($userDataInformation);
            $this->updateDbData('selected_list_data', trim($listId));
            if (!empty($responseValue['data']['process_id'])) {
                $this->updateDbData('import_old_user_status', 0);
                return 0;
            }
            return 1;
        }        
    }

    /**
    * Description: Fetches all the subscribers of Shopify and adds them to the Sendinblue database.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: get user detail
    * @return: write in csv
    * @updated: 12-June-2016
    */
    public function getSubscribeCustomer()
    {
        $data = array();
        $customerAddressData = array();
        $attributesName = $this->allAttributesName();
        $collection = $this->getCollection();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        foreach ($collection as $customers) {
            $customerData = $customers->getData();
            $email  = $customerData['email'];
            $customerId = $customerData['entity_id'];
            $billingId =  $customers->getDefaultBilling();
            $customerAddress = array();
            if (!empty($billingId)) {
                $address = $objectManager->create('Magento\Customer\Model\Address')->load($billingId);
                $telephone = '';
                $street = $address->getStreet();
                $streetValue = '';
                foreach ($street as $streetData){
                    $streetValue.= $streetData.' ';
                }

                $customerAddress['telephone'] = !empty($address->getTelephone()) ? $address->getTelephone() : '';
                $customerAddress['country_id'] = !empty($address->getCountry()) ? $address->getCountry() : '';
                $customerAddress['company'] = !empty($address->getCompany()) ? $address->getCompany() : '';
                $customerAddress['street'] = !empty($streetValue) ? $streetValue : '';
                $customerAddress['postcode'] = !empty($address->getPostcode()) ? $address->getPostcode() : '';
                $customerAddress['region'] = !empty($address->getRegion()) ? $address->getRegion() : '';
                $customerAddress['city'] = !empty($address->getCity()) ? $address->getCity() : '';
            }
            $customerAddress['client'] = $customerId>0?1:0;
            $customerAddressData[$email] = array_merge($customerData, $customerAddress);
        }

        $newsLetterData = array();
        $count = 0;
        $connection = $this->createDbConnection();
        $tblNewsletter = $this->tbWithPrefix('newsletter_subscriber');
        $resultSubscriber = $connection->fetchAll('SELECT * FROM `'.$tblNewsletter.'` WHERE subscriber_status=1');

        foreach ($resultSubscriber as $subsdata){
            $subscriberEmail = $subsdata['subscriber_email'];
            
            if ( !empty($customerAddressData[$subscriberEmail]) ) {
                $customerAddressData[$subscriberEmail]['email'] = $subscriberEmail;
                $responseByMerge[$count] = $this->mergeMyArray($attributesName, $customerAddressData[$subscriberEmail], $subscriberEmail);
            }
            else {
                $newsLetterData['client'] = $subsdata['customer_id']>0?1:0;
                $responseByMerge[$count] = $this->mergeMyArray($attributesName, $newsLetterData, $subscriberEmail);
                $responseByMerge[$count]['STORE_ID'] = $subsdata['store_id'];
                $storeId = $subsdata['store_id'];
                $stores = $this->_storeManagerInterface->getStores(true, false);
                foreach ($stores as $store){
                    if ($store->getId() == $storeId) {
                        $storeView = $store->getName();
                    }
                }
                $responseByMerge[$count]['MAGENTO_LANG'] = $storeView;
            }
            $count++;                
        }

        if (!is_dir($this->_dir->getPath('media').'/sendinblue_csv')) {
            mkdir($this->_dir->getPath('media').'/sendinblue_csv', 0777, true);
        }
		$fileName = rand();
		$this->updateDbData('sendin_csv_file_name', $fileName);
        $handle = fopen($this->_dir->getPath('media').'/sendinblue_csv/'.$fileName.'.csv', 'w+');
        $keyValue = array_keys($attributesName);
        array_splice($keyValue, 0, 0, 'EMAIL');
        fwrite($handle, implode(';', $keyValue)."\n");

        foreach ($responseByMerge as $newsdata) {
            if(!empty($newsdata['COUNTRY_ID']) && !empty($newsdata['SMS'])) {
                $countryId = $this->getCountryCode($newsdata['COUNTRY_ID']);
                if (!empty($countryId)) {
                    $newsdata['SMS'] = $this->checkMobileNumber($newsdata['SMS'], $countryId);
                }
            }
            $keyValue = $newsdata;
            fwrite($handle, str_replace("\n", "",implode(';', $keyValue))."\n");
        }
        fclose($handle);
        $totalValue = count($responseByMerge);
        return $totalValue;
    }

    public function getCollection()
    {
        //Get customer collection
        return $this->_customers->getCollection();
    }

    public function getCustomer($customerId)
    {
        //Get customer by customerID
        $dataCust = $this->_customers->load($customerId);
        return $dataCust->getData();
    }

    /**
     *  This method is used to compare key and value 
     * return all value in array whose present in array key
    */
    public function mergeMyArray($one, $two, $email = "")
    {
        $emailData = $email ? array('EMAIL'=> $email) : array();
        if (count($one) > 0) {
            foreach($one as $k => $v) {
                $emailData[$k] = isset($two[$v])?str_replace(';',',', $two[$v]):'';
            }
        }
        return $emailData;
    }

    /**
    * Get getCountryCode from sendinblue_country table,
    */
    public function getCountryCode($countryids)
    {
        $connection = $this->createDbConnection();
        $tblCountryIso = $this->tbWithPrefix('sendinblue_country_codes');
        $countryPrefixData = $connection->fetchAll('SELECT * FROM `'.$tblCountryIso.'` WHERE `iso_code` = '."'$countryids'");
        $countryPrefix = !empty($countryPrefixData[0]['country_prefix']) ? $countryPrefixData[0]['country_prefix'] : '';
        return $countryPrefix;
    }
    
    /**
    * Get final sms value after add and check country code,
    */
    public function checkMobileNumber($number, $callPrefix)
    {
        $number = preg_replace('/\s+/', '', $number);
        $charOne = substr($number, 0, 1);
        $charTwo = substr($number, 0, 2);

        if (preg_match('/^'.$callPrefix.'/', $number)) {
            return '00'.$number;
        }

        elseif ($charOne == '0' && $charTwo != '00') {
            if (preg_match('/^0'.$callPrefix.'/', $number)) {
                return '00'.substr($number, 1);
            }
            else {
                return '00'.$callPrefix.substr($number, 1);
            }
        }
        elseif ($charTwo == '00') {
            if (preg_match('/^00'.$callPrefix.'/', $number)) {
                return $number;
            }
            else {
                return '00'.$callPrefix.substr($number, 2);
            }
        }
        elseif ($charOne == '+') {
            if (preg_match('/^\+'.$callPrefix.'/', $number)) {
                return '00'.substr($number, 1);
            }
            else {
                return '00'.$callPrefix.substr($number, 1);
            }
        }
        elseif ($charOne != '0') {
            return '00'.$callPrefix.$number;
        }
    }

    /**
    * Get core config table value data,
    */
    public function getDbData($val)
    {
        return $this->_getValueDefault->getValue('sendinblue/'.$val, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
    * Update core config table value and data,
    */
    public function updateDbData($key, $value)
    {
        $this->_resourceConfig->saveConfig('sendinblue/'.$key, $value, $this->_scopeTypeDefault, $this->_storeId);
    }

    /**
    * Description: Get all temlpate list id by sendinblue.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: useremail, doubleoptinUrl, storeId ,shoplang
    * @return: smtp details
    * @updated: 09-sep-2017
    */
    public function templateDisplay($apiKey = false)
    {
        if (!$apiKey) {
            $apiKey = $this->_getValueDefault->getValue('sendinblue/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        if (!empty($apiKey)) {
            $mailin = $this->createObjMailin($apiKey);
            $data = array( "type"=>"template",
             "status"=>"temp_active",
             "page"=>1,
             "page_limit"=>100
            );
            $tempResult = $mailin->getCampaignsV2($data);
            return $tempResult['data'];
        }
    }

    /**
    * Description: Show  SMS  credit from Sendinblue.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: ApiKey
    * @return: number of sms remaining
    * @updated: 17-sept-2017
    */
    public function getSmsCredit($apiKey = false)
    {
        if (!$apiKey) {
            $apiKey = $this->_getValueDefault->getValue('sendinblue/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        if (!empty($apiKey)) {
            $mailin = $this->createObjMailin($apiKey);
            $dataResp = $mailin->getAccount();
            foreach($dataResp['data'] as $accountVal) {
                if($accountVal['plan_type'] == 'SMS') {
                    return $accountVal['credits'];
                }
            }
        }
    }

    /**
    * Update sender name (from name and from email) from Sendinblue for email service.
    */
    public function updateSender()
    {
        $apiKey = $this->getDbData('api_key');
        if (!empty($apiKey)) {
            $mailin = $this->createObjMailin($apiKey);
            $data = array( "option" => "" );
            $response = $mailin->getSenders($data);
            if($response['code'] == 'success') {
                $senders = array('id' => $response['data']['0']['id'], 'from_name' => $response['data']['0']['from_name'], 'from_email' => $response['data']['0']['from_email']);
                $this->updateDbData('sendin_sender_value', json_encode($senders));
            }
        }
    }

    /**
    * Description: get all folder list and id from SIB and display in Shopify page.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    *
    * @updated by: Author Amar Pandey <amarpandey@sendinblue.com>
    * @param: folder list
    * @return: display dist in shopify
    * @updated: 24-may-2016
    */ 
    public function checkFolderListDoubleoptin($apiKey)
    {
        if ($apiKey == '') {
            return false;
        }
        
        $mailin = $this->createObjMailin($apiKey);
        $dataApi = array( "page" => 1,
          "page_limit" => 50
        );
        $folderResp = array();
        $folderResp = $mailin->getFolders($dataApi);
        
        //folder id
        $sArray = array();
        $returnVal = false;
        if (!empty($folderResp['data']['folders'])) {
            foreach ($folderResp['data']['folders'] as $value) {
                if (strtolower($value['name']) == 'form') {
                    
                    if (!empty($value['lists'])) {
                        foreach ($value['lists'] as $val) {
                            if ($val['name'] == 'Temp - DOUBLE OPTIN') {
                                $sArray['optin_id'] = $val['id'];
                            }
                        }
                    }
                }
            }
            if (count($sArray) > 0) {
                $returnVal = $sArray;
            } else {
                $returnVal = false;
            }
        }
        return $returnVal;
    }

    /**
     * Fetches the SMTP and order tracking details
    */
    public function trackingSmtp()
    {
        $smtpDetails = array();
        $apiKey = $this->getDbData('api_key');
        if (!empty($apiKey)) {
            $mailin = $this->createObjMailin($apiKey);
            $smtpDetails = $mailin->getSmtpDetails();
            return $smtpDetails;
        }
    }
    /**
    * Delete sendiblue SMTP entry
    */
    public function resetSmtpDetail()
    {
        $this->updateDbData('api_smtp_status', 0);
        $this->updateDbData('smtp_authentication', '');
        $this->updateDbData('smtp_username', '');
        $this->updateDbData('smtp_password', '');
        $this->updateDbData('smtp_host', '');
        $this->updateDbData('smtp_port', '');
        $this->updateDbData('smtp_tls', '');
        $this->updateDbData('smtp_option', '');
        $this->updateDbData('relay_data_status', '');
    }

    /**
    * Import old order history
    */
    public function importOrderhistory()
    {
        $apiKey = $this->getDbData('api_key');
        $trackStatus = $this->getDbData('ord_track_status');
        $dateValue = !empty($this->getDbData('sendin_date_format')) ? $this->getDbData('sendin_date_format') : 'mm-dd-yyyy';
        if ($trackStatus == 1 && !empty($apiKey)) {
            if (!is_dir($this->_dir->getPath('media').'/sendinblue_csv')) {
                mkdir($this->_dir->getPath('media').'/sendinblue_csv', 0777, true);
            }

            $fileName = rand();
            $this->updateDbData('sendin_csv_file_name', $fileName);
            $handle = fopen($this->_dir->getPath('media').'/sendinblue_csv/'.$fileName.'.csv', 'w+');
            fwrite($handle, 'EMAIL;ORDER_ID;ORDER_PRICE;ORDER_DATE'.PHP_EOL);
            $collection = $this->getCollection();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            foreach ($collection as $customers) {
                $customerData = $customers->getData();
                $email  = $customerData['email'];
                $customerId = $customerData['entity_id'];
                $connection = $this->createDbConnection();
                $tblNewsletter = $this->tbWithPrefix('newsletter_subscriber');
                $resultSubscriber = $connection->fetchAll('SELECT count(*) as countval FROM `'.$tblNewsletter.'` WHERE subscriber_email ='."'$email'".' AND subscriber_status = 1');
                if (isset($resultSubscriber[0]['countval']) && $resultSubscriber[0]['countval'] > 0) {
                    $order = $objectManager->create('Magento\Sales\Model\Order')->getCollection()->addAttributeToFilter('customer_id', $customerId);
                    foreach ($order as $orderDatamodel) {
                        if(count($orderDatamodel) > 0) {
                            $orderData = $orderDatamodel->getData();
                            $orderID = $orderData['increment_id'];
                            $orderPrice = $orderData['grand_total'];
                            $dateAdded = $orderData['created_at'];
                            if ($dateValue == 'dd-mm-yyyy') {
                                $orderDate = date('d-m-Y', strtotime($dateAdded));
                            }
                            else {
                                $orderDate = date('m-d-Y', strtotime($dateAdded));
                            }
                            $historyData= array();
                            $historyData[] = array($email, $orderID, $orderPrice, $orderDate);
                            foreach ($historyData as $line) {
                                fputcsv($handle, $line, ';');
                            }
                        }    
                    }
                }
            }

            fclose($handle);
            $this->updateDbData('order_import_status', 1);
            $listId = $this->getDbData('selected_list_data');
            $userDataInformation = array();
            $listIdVal = explode('|', $listId);
            $mailinObj = $this->createObjMailin($apiKey);
            $baseUrl = $this->_storeManagerInterface->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $fileName = $this->getDbData('sendin_csv_file_name');
            $userDataInformation['key'] = $apiKey;
            $userDataInformation['url'] = $baseUrl.'sendinblue_csv/'.$fileName.'.csv';
            $userDataInformation['listids'] = $listIdVal; // $list;
            $userDataInformation['notify_url'] = '';
            $responseValue = $mailinObj->importUsers($userDataInformation);
            if (!empty($responseValue['data']['process_id'])) {
                $this->updateDbData('order_import_status', 0);
                return 0;
            }
            return 1;
        }
    }

    /**
    * Test sms for order confirmation.
    */
    public function sendOrderTestSms($sender, $message, $number)
    {
        $charone = substr($number, 0, 1);
        $chartwo = substr($number, 0, 2);
        if ($charone == '0' && $chartwo == '00') { 
            $number = $number;
        }

        $apiKey = $this->getDbData('api_key');
        if(!empty($number) && !empty($apiKey)) {
            $adminData = $this->getCurrentUser();
            $firstname = $adminData['firstname'];
            $lastname = $adminData['lastname'];
            $characters = '1234567890';
            $referenceNumber = '';
            for ($i = 0; $i < 9; $i++) {
                $referenceNumber .= $characters[rand(0, strlen($characters) - 1)];
            }
            $localeCode = $adminData['interface_locale'];
            $orderDateFormat = ($localeCode == 'fr_FR') ? date('d/m/Y') : date('m/d/Y');
            $orderprice = rand(10, 1000);
            $currency = $this->_storeManagerInterface->getStore()->getCurrentCurrencyCode();
            $totalPay = $orderprice.'.00'.' '.$currency;
            $firstName = str_replace('{first_name}', $firstname, $message);
            $lastName = str_replace('{last_name}', $lastname."\r\n", $firstName);
            $procuctPrice = str_replace('{order_price}', $totalPay, $lastName);
            $orderDate = str_replace('{order_date}', $orderDateFormat."\r\n", $procuctPrice);
            $msgbody = str_replace('{order_reference}', $referenceNumber, $orderDate);
            $smsData = array();
            $smsData['to'] = $number;
            $smsData['from'] = !empty($sender) ? $sender : '';
            $smsData['text'] = $msgbody;
            $smsData['type'] = 'transactional';
            $responseValue = $this->sendSmsApi($smsData);
            if ($responseValue == 'success') {
                    return  'OK';
            }
            else {
                return  'KO';
            }
        }
    }

    /**
    * Test sms for order confirmation.
    */
    public function sendShippedTestSms($sender, $message, $number)
    {
        $charone = substr($number, 0, 1);
        $chartwo = substr($number, 0, 2);
        if ($charone == '0' && $chartwo == '00') { 
            $number = $number;
        }

        $apiKey = $this->getDbData('api_key');
        if(!empty($number) && !empty($apiKey)) {
            $adminData = $this->getCurrentUser();
            $firstname = $adminData['firstname'];
            $lastname = $adminData['lastname'];
            $characters = '1234567890';
            $referenceNumber = '';
            for ($i = 0; $i < 9; $i++) {
                $referenceNumber .= $characters[rand(0, strlen($characters) - 1)];
            }
            $localeCode = $adminData['interface_locale'];
            $orderDateFormat = ($localeCode == 'fr_FR') ? date('d/m/Y') : date('m/d/Y');
            $orderprice = rand(10, 1000);
            $currency = $this->_storeManagerInterface->getStore()->getCurrentCurrencyCode();
            $totalPay = $orderprice.'.00'.' '.$currency;
            $firstName = str_replace('{first_name}', $firstname, $message);
            $lastName = str_replace('{last_name}', $lastname."\r\n", $firstName);
            $procuctPrice = str_replace('{order_price}', $totalPay, $lastName);
            $orderDate = str_replace('{order_date}', $orderDateFormat."\r\n", $procuctPrice);
            $msgbody = str_replace('{order_reference}', $referenceNumber, $orderDate);
            $smsData = array();
            $smsData['to'] = $number;
            $smsData['from'] = !empty($sender) ? $sender : '';
            $smsData['text'] = $msgbody;
            $smsData['type'] = 'transactional';
            $responseValue = $this->sendSmsApi($smsData);
            if ($responseValue == 'success') {
                return  'OK';
            }
            else {
                return  'KO';
            }
        }
    }

    /**
    * Test sms for order confirmation.
    */
    public function sendCampaignTestSms($sender, $message, $number)
    {
        $charone = substr($number, 0, 1);
        $chartwo = substr($number, 0, 2);
        if ($charone == '0' && $chartwo == '00') { 
            $number = $number;
        }

        $apiKey = $this->getDbData('api_key');
        if(!empty($number) && !empty($apiKey)) {
            $adminData = $this->getCurrentUser();
            $firstname = $adminData['firstname'];
            $lastname = $adminData['lastname'];
            $firstName = str_replace('{first_name}', $firstname, $message);
            $msgbody = str_replace('{last_name}', $lastname."\r\n", $firstName);
            $smsData = array();
            $smsData['to'] = $number;
            $smsData['from'] = !empty($sender) ? $sender : '';
            $smsData['text'] = $msgbody;
            $smsData['type'] = 'transactional';
            $responseValue = $this->sendSmsApi($smsData);
            if ($responseValue == 'success') {
                return  'OK';
            }
            else {
                return  'KO';
            }
        }
    }

    /**
    * Description: Send SMS from Sendinblue.
    * Get Param sender and msg fields.
    */
    public function sendSmsApi($dataSms)
    {
        $apiKey = $this->getDbData('api_key');
        if (!empty($apiKey)) {
            $mailin = $this->createObjMailin($apiKey);
            $dataFinal = array( "to" => trim($dataSms['to']),
                    "from" => trim($dataSms['from']),
                    "text" => trim($dataSms['text']),
                    "type" => trim($dataSms['type']),
                    "source" => 'api',
                    "plugin" => 'magento2-plugin'
                );
            $dataResp = $mailin->sendSms($dataFinal);

            $remainingSms = !empty($dataResp['data']['remaining_credit']) ? $dataResp['data']['remaining_credit'] : 0;
            $notifyLimit = $this->getDbData('notify_value');
            $emailSendStatus = $this->getDbData('notify_email_send');
            if (!empty($notifyLimit) && $remainingSms <= $notifyLimit && $emailSendStatus == 0) {
                $smtpResult = $this->getDbData('relay_data_status');
                if ($smtpResult == 'enabled') {
                    $this->sendNotifySms($remainingSms);
                    $this->updateDbData('notify_email_send', 1);
                }
            }
            return !empty($dataResp['code']) ? $dataResp['code'] : 'fail';
        }
    }

    /**
    * Description: This method is called when the user sets the Campaign single Choic eCampaign and hits the submit button.
    *
    */
    public function singleChoiceCampaign($post)
    {
        $senderCampaign = $post['sender_campaign'];
        $senderCampaignNumber = $post['singlechoice'];
        $senderCampaignMessage = $post['sender_campaign_message'];

        if (!empty($senderCampaign) && !empty($senderCampaignNumber) && !empty($senderCampaignMessage)) {
            $arr = array();
            $arr['to'] = $senderCampaignNumber;
            $arr['from'] = $senderCampaign;
            $arr['text'] = $senderCampaignMessage;
            $arr['type'] = "transactional";
            $result = $this->sendSmsApi($arr);
            return $result;
        }
        return 'fail';
    }

        /**
    * Description: This method is called when the user sets the Campaign multiple Choic eCampaign and hits subscribed user the submit button.
    *
    */
    public function multipleChoiceSubCampaign($post)
    {
        $senderCampaign = $post['sender_campaign'];
        $senderCampaignMessage = $post['sender_campaign_message'];
        $scheduleMonth =  $post['sib_datetimepicker'];
        $scheduleHour = $post['hour'];
        $scheduleMinute = $post['minute'];

        if ($scheduleHour < 10) {
            $scheduleHour = '0'.$scheduleHour;
        }
        if ($scheduleMinute < 10) {
            $scheduleMinute = '0'.$scheduleMinute;
        }

        $scheduleTime = $scheduleMonth.' '.$scheduleHour.':'.$scheduleMinute.':00';

        $currentTime = date('Y-m-d H:i:s', time() + 300);
        $currenttm = strtotime($currentTime);
        $scheduletm = strtotime($scheduleTime);

        if (!empty($scheduleTime) && ($scheduletm < $currenttm)) {
            return 'failled';
        }
        if (!empty($senderCampaign) && !empty($senderCampaignMessage)) {
            $campName = 'SMS_' . date('Ymd');
            $apiKey = $this->getDbData('api_key');
            $localeLangId = $this->getDbData('sendin_config_lang');
            if ($apiKey == '') {
                return false;
            }
            if (strtolower($localeLangId) == 'fr') {
                $firstName = '{PRENOM}';
                $lastName = '{NOM}';
            } else {
                $firstName = '{NAME}';
                $lastName = '{SURNAME}';
            }

            $fname = str_replace('{first_name}', $firstName, $senderCampaignMessage);
            $content = str_replace('{last_name}', $lastName . "\r\n", $fname);
            $listId =  $this->getDbData('selected_list_data');

            $listValue = explode('|', $listId);

            if (!empty($apiKey)) {
                $mailin = $this->createObjMailin($apiKey);
                $data = array( "name" => $campName,
                "sender" => $senderCampaign,
                "content" => $content,
                "listid" => $listValue,
                "scheduled_date" => $scheduleTime,
                "send_now" => 0
                );
                $campResponce = $mailin->createSmsCampaign($data);
            }
            return $campResponce['code'];
        }
        return 'fail';
    }

    /**
    * Description: This method is called when the user sets the Campaign multiple Choic eCampaign and hits the submit button.
    *
    */
    public function multipleChoiceCampaign($post)
    {
        $senderCampaign = $post['sender_campaign'];
        $senderCampaignMessage = $post['sender_campaign_message'];
        $smsCredit = $this->getSmsCredit();
        if (!empty($senderCampaign) && !empty($senderCampaignMessage) && $smsCredit >= 1) {
            $arr = array();
            $arr['from'] = $senderCampaign;
            
            $customerObj = $this->_customers->getCollection();
            foreach($customerObj as $customerObjdata ){
                $billingId = $customerObjdata->getDefaultBilling();
                $address = $objectManager->create('Magento\Customer\Model\Address')->load($billingId);
                $smsValue = $address->getTelephone();
                $countryId = $address->getCountry();
                $firstName = $address->getFirstname();
                $lastName = $address->getLastname();
                $countryPrefix = $this->getCountryCode($countryId);
                if(!empty($smsValue) && !empty($countryPrefix)) {
                    $mobile = $this->checkMobileNumber($smsValue, $countryPrefix);
                }
                $fname = str_replace('{first_name}', ucfirst($firstName), $senderCampaignMessage);
                $lname = str_replace('{last_name}', $lastName . "\r\n", $fname);
                $arr['text'] = $lname;
                $arr['to'] = $mobile;
                $arr['type'] = "transactional";
                $this->sendSmsApi($arr);
            }
            return 'success';
        }
    }

    /**
    * Description: Send single user in Sendinblue.
    *
    */
    public function subscribeByruntime($userEmail, $updateDataInSib)
    {
        if ($this->syncSetting() != 1) {
            return false;
        }

        $sendinConfirmType = $this->getDbData('confirm_type');
        if ($sendinConfirmType === 'doubleoptin') {
            $listId = $this->getDbData('optin_list_id');
            $updateDataInSib['DOUBLE_OPT-IN'] = 2;
        } else {
            $listId = $this->getDbData('selected_list_data');
        }

        $apiKey = trim($this->getDbData('api_key'));
        if (!empty($apiKey)) {
            $mailin = $this->createObjMailin($apiKey);

            $blacklistedValue = 0;

            $sibListId = explode('|', $listId);
            $data = array( "email" => $userEmail,
            "attributes" => $updateDataInSib,
            "blacklisted" => $blacklistedValue,
            "listid" => $sibListId
            );
            $mailin->createUpdateUser($data);
        }        
    }

    /**
    * Description: Checks whether the Sendinblue API key and the Sendinblue subscription form is enabled
    * and returns the true|false accordingly.
    */
    public function syncSetting()
    {
        $keyStatus = $this->getDbData('api_key_status');
        $subsStatus = $this->getDbData('subscribe_setting');
        if ($keyStatus == 1 && $subsStatus == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
    * Description: Send single user in Sendinblue.
    *
    */
    public function unsubscribeByruntime($userEmail)
    {
        if ($this->syncSetting() != 1) {
            return false;
        }
        
        $apiKey = trim($this->getDbData('api_key'));
        if (!empty($apiKey)) {
            $mailin = $this->createObjMailin($apiKey);

            $data = array( "email" => $userEmail,
                    "blacklisted" => 1);
            $mailin->createUpdateUser($data);
        }        
    }

    /**
    * Description: check newsletter email status.
    *
    */
    public function checkNlStatus($email)
    {
        $connection = $this->createDbConnection();
        $tblNewsletter = $this->tbWithPrefix('newsletter_subscriber');
        $resultSubscriber = $connection->fetchAll('SELECT `subscriber_status` FROM `'.$tblNewsletter.'` WHERE subscriber_email ='."'$email'");

        foreach ($resultSubscriber as $subsdata){
            return $subscriberEmail = !empty($subsdata['subscriber_status']) ? $subsdata['subscriber_status'] : '';
        }
    }

    /**
    * Description: create db connection.
    *
    */
    public function createDbConnection() 
    {
        return $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
    }

    /**
    * Description: send sendinblue configuration mail for testing purpose.
    */
    public function smtpSendMail($userEmail, $title, $tempName, $paramVal)
    {
        $result = [];
        $result['status'] = false;
        $relayData = $this->getDbData('relay_data_status');
        if ($relayData == 'enabled' && !empty($userEmail)) {
            $config = [];
            $host = $this->getDbData('smtp_host');
            $config['tls'] = $this->getDbData('smtp_tls');
            $config['port'] = $this->getDbData('smtp_port');
            $config['auth']     = $this->getDbData('smtp_authentication');
            $config['username'] = $this->getDbData('smtp_username');
            $config['password'] = $this->getDbData('smtp_password');
            
            $transport = new \Zend_Mail_Transport_Smtp($host, $config);

            $mail = new \Zend_Mail();
            $sender = $this->getDbData('sendin_sender_value');
            $dataSender = json_decode($sender);
            $senderName = !empty($dataSender->from_name) ? $dataSender->from_name : 'Sendinblue';
            $senderEmail = !empty($dataSender->from_email) ? $dataSender->from_email : 'contact@sendinblue.com';

            $mail->setFrom($senderEmail, $senderName);

            $mail->addTo($userEmail);
            $mail->setSubject($title);
            $lang = $this->getDbData('sendin_config_lang');

            $path = $this->_blocktemp->getViewFileUrl('Sendinblue_Sendinblue::email_temp/'.strtolower($lang).'/'.$tempName.'.html');
	    $path = str_replace('_view','Magento/backend', $path);
            $bodyContent = file_get_contents($path);
            if (!empty($paramVal)) {
                foreach($paramVal as $key=>$replaceVal) {
                    $bodyContent = str_replace($key, $replaceVal, $bodyContent);
                }
            }
            $mail->setBodyHtml($bodyContent);
            try {
                $mail->send($transport);
                $result['status']  = true;
                $result['content'] = __('Sent successfully! Please check your email box.');
            } catch (\Exception $e) {
                $result['content'] = $e->getMessage();
            }
        } else {
            $result['content'] = __('Test Error');
        }
        return $result;
    }

    public function sendNotifySms($remainingSms)
    {
        $notifyEmail = $this->getDbData('notify_email');
        $tempName = 'sendin_notification';
        $title = __('[Sendinblue] Notification : Credits SMS');
        $siteName = $this->_storeManagerInterface->getStore()->getName();
        $paramVal = array('{present_credit}' => $remainingSms, '{site_name}' => $siteName);
        $this->smtpSendMail($notifyEmail, $title, $tempName, $paramVal);
    }

    /**
    * Send template email by sendinblue for newsletter subscriber user.
    *
    * @author: Amar Pandey <amarpandey@sendinblue.com>
    * @param: storeId ,shoplang
    * @return: smtp details
    * @updated: 09-June-2016
    */
    public function sendWsTemplateMail($to)
    {
        $key = $this->getDbData('api_key');
        $mailin = $this->createObjMailin($key);

        $sendinConfirmType = $this->getDbData('confirm_type');
        if (empty($sendinConfirmType) || $sendinConfirmType == 'nocon') {
            return false;
        }

        if ($sendinConfirmType == 'simplemail') {
            $tempIdValue = $this->getDbData('template_id');
            $templateId = !empty($tempIdValue) ? $tempIdValue : '';
        }

        if ($sendinConfirmType == 'doubleoptin') {
            $pathResp = '';
            $senderEmail = '';

            $path = $this->_storeManagerInterface->getStore()->getBaseUrl().'sendinblue';
            $siteName = $this->_storeManagerInterface->getStore()->getName();
            $pathResp = $path.'?value='.base64_encode($to);

            $title = __('Please confirm your subscription');

            $paramVal = array('{double_optin}' => $pathResp);
            $tempName = 'doubleoptin_temp';
            $sender = $this->getDbData('sendin_sender_value');
            $dataSender = json_decode($sender);
            $senderName = !empty($dataSender->from_name) ? $dataSender->from_name : 'Sendinblue';
            $senderEmail = !empty($dataSender->from_email) ? $dataSender->from_email : 'no-reply@sendinblue.com';

            $doubleOptinTempId = $this->getDbData('doubleoptin_template_id');

            if (intval($doubleOptinTempId) > 0) {
                $data = array(
                    'id' => $doubleOptinTempId
                );
                $response = $mailin->getCampaignV2($data);
                if($response['code'] == 'success') {
                    $htmlContent = $response['data'][0]['html_content'];
                    if (trim($response['data'][0]['subject']) != '') {
                        $subject = trim($response['data'][0]['subject']);
                    }
                    if (($response['data'][0]['from_name'] != '[DEFAULT_FROM_NAME]') &&
                        ($response['data'][0]['from_email'] != '[DEFAULT_FROM_EMAIL]') &&
                        ($response['data'][0]['from_email'] != '')) {
                        $senderName = $response['data'][0]['from_name'];
                        $senderEmail = $response['data'][0]['from_email'];
                    }
                    $transactionalTags = $response['data'][0]['campaign_name'];
                }
            } else {
                return $this->smtpSendMail($to, $title, $tempName, $paramVal);
            }

            $to = array($to => '');
            $from = array($senderEmail, $senderName);
            $searchValue = "({{\s*doubleoptin\s*}})";

            $htmlContent = str_replace('{title}', $subject, $htmlContent);
            $htmlContent = str_replace('https://[DOUBLEOPTIN]', '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('http://[DOUBLEOPTIN]', '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('https://{{doubleoptin}}', '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('http://{{doubleoptin}}', '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('https://{{ doubleoptin }}', '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('http://{{ doubleoptin }}', '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('[DOUBLEOPTIN]', '{subscribe_url}', $htmlContent);
            $htmlContent = preg_replace($searchValue, '{subscribe_url}', $htmlContent);
            $htmlContent = str_replace('{site_name}', $siteName, $htmlContent);
            $htmlContent = str_replace('{unsubscribe_url}', $pathResp, $htmlContent);
            $htmlContent = str_replace('{subscribe_url}', $pathResp, $htmlContent);

            $headers = array("Content-Type"=> "text/html;charset=iso-8859-1", "X-Mailin-tag"=>$transactionalTags );
            $data = array( "to" => $to,
                "cc" => array(),
                "bcc" =>array(),
                "from" => $from,
                "replyto" => array(),
                "subject" => $subject,
                "text" => '',
                "html" => $htmlContent,
                "attachment" => array(),
                "headers" => $headers,
                "inline_image" => array()
            );
            return $mailin->sendEmail($data);
        }

        // should be the campaign id of template created on mailin. Please remember this template should be active than only it will be sent, otherwise it will return error.
        if (!empty($key)) {
            $data = array();
            $mailin = $this->createObjMailin($key);
            $data = array( "id" => $templateId,
              "to" => $to
            );
            $mailin->sendTransactionalTemplate($data);
        }
    }

    /**
    * Description: After Click on Optin link and if admin checked confirmaition e-mail send
    * to user. 
    *
    */
    public function sendOptinConfirmMailResponce($customerEmail, $finalId, $apiKey)
    {
        // should be the campaign id of template created on mailin. Please remember this template should be active than only it will be sent, otherwise it will return error.
        if (!empty($apiKey)) {
            $mailin = $this->createObjMailin($apiKey);
            $data = array( "id" => $finalId,
              "to" => $customerEmail
            );
            $mailin->sendTransactionalTemplate($data);
        }
    }

    /**
     *  This method is count all distinct record in customer and newsletter emails.
     */
    public function getCustAndNewslCount()
    {
        $connection = $this->createDbConnection();
        $tblNewsletter = $this->tbWithPrefix('newsletter_subscriber');
        $tblCustomer = $this->tbWithPrefix('customer_entity');
        $countAllRec = $connection->fetchAll("SELECT COUNT( * ) c
                    FROM (
                    SELECT cu.email
                    FROM ". $tblCustomer ." cu
                    UNION
                    SELECT n.subscriber_email
                    FROM ". $tblNewsletter ." n) x ");
        return !empty($countAllRec['0']['c']) ? $countAllRec['0']['c'] : 0;
    }

    /**
     *  This method is used to fetch all users from the default newsletter table to list
     * them in the Sendinblue magento module.
     */
    public function getNewsletterSubscribe($start, $perPage)
    {
        $connection = $this->createDbConnection();
        $tblNewsletter = $this->tbWithPrefix('newsletter_subscriber');
        $tblCustomer = $this->tbWithPrefix('customer_entity');

        $customerAddressData = array();
        $allData = array();
        $query = "select email from ". $tblCustomer ."
                union
                select subscriber_email from ". $tblNewsletter ." limit $start , $perPage";

        if (count($connection->fetchAll($query)) > 0) {
            foreach ($connection->fetchAll($query) as $emailValue) {
                $email = !empty($emailValue['email']) ? $emailValue['email'] : '';
                $customerAddressData['email'] = $email;
                $customerAddressData['SMS'] = '';

                $storeId = $this->_storeManagerInterface->getStore()->getId();
                $this->_customers->setWebsiteId($storeId);
                $dataCust = $this->_customers->loadByEmail($email);
                $rowData = $dataCust->getData();

                $customerId = !empty($rowData['entity_id']) ? $rowData['entity_id'] : '';
                if (!empty($customerId)) {
                    $billingId = !empty($rowData['default_billing']) ? $rowData['default_billing'] : '';
                    $objectManager = $this->getObjRunTime();
                    $collectionAddress = $objectManager->create('Magento\Customer\Model\Address')->load($billingId);
                    $customerAddress = array();
                    if (!empty($billingId)) {
                        $telephoneNumber = $collectionAddress->getTelephone();
                        $countryID = $collectionAddress->getCountry();
                        if (!empty($telephoneNumber) && !empty($countryID)) {
                            $countryCode = $this->getCountryCode($countryID);
                            $customerAddressData['SMS'] = $this->checkMobileNumber($telephoneNumber, $countryCode);
                        }
                    }
                    $customerAddressData['client'] = 1;
                } else {
                    $customerAddressData['client'] = 0;
                }

                $customerSubscribe = $this->checkNlStatus($email);

                $subsStatus = !empty($customerSubscribe) ? $customerSubscribe : 0;
                if ($subsStatus == 1){
                    $customerAddressData['subscriber_status'] = 1;
                } else {
                    $customerAddressData['subscriber_status'] = 0;
                }
                $allData[] = $customerAddressData;
            }
        }
        return $allData;
    }

    /**
     * This method is used to check the subscriber's newsletter subscription status in Sendinblue
     */
    public function checkUserSendinStatus($result)
    { 
        $userStatus = array();
        foreach ($result as $subscriber) {
            $userStatus[] = $subscriber['email'];
        }
        $allUsers = array('users' => $userStatus );
        $apiKey = $this->getDbData('api_key');
        if (!empty($apiKey)) {
            $mailin = $this->createObjMailin($apiKey);
            $usersBlackListData = $mailin->getUsersBlacklistStatus($allUsers);
        }
        return $usersBlackListData;
    }

    /**
    *  This method is used to fetch total count subscribe users from the default newsletter table to list
    * them in the Sendinblue magento module.
    */
    public function getNewsletterSubscribeCount()
    {
        $connection = $this->createDbConnection();
        $tblNewsletter = $this->tbWithPrefix('newsletter_subscriber');
        $resultSubscriber = $connection->fetchAll('SELECT COUNT(*) as total FROM `'.$tblNewsletter.'` WHERE subscriber_status = 1');

        $subscriberEmail = !empty($resultSubscriber[0]['total']) ? $resultSubscriber[0]['total'] : 0;
        return $subscriberEmail;
    }

    /**
     *  This method is used to fetch total count unsubscribe users from the default newsletter table to list
     * them in the Sendinblue magento module.
    */
    public function getNewsletterUnSubscribeCount()
    {
        $connection = $this->createDbConnection();
        $tblNewsletter = $this->tbWithPrefix('newsletter_subscriber');
        $tblcustomer = $this->tbWithPrefix('customer_entity');
        $countCust = $connection->fetchAll("SELECT COUNT(email) as email FROM $tblcustomer");
        $custAll = !empty($countCust['0']['email']) ? $countCust['0']['email'] : 0;
        $querySecond = $connection->fetchAll("SELECT COUNT( subscriber_email ) as email
                        FROM ". $tblNewsletter ." where subscriber_status = 1 AND customer_id > 0");

        $querythird = $connection->fetchAll("SELECT COUNT( subscriber_email ) as email
                        FROM ". $tblNewsletter ." where subscriber_status != 1 AND customer_id = 0");

        $allsubsUser = !empty($querySecond['0']['email']) ? $querySecond['0']['email'] : 0;
        $UnsNl = !empty($querythird['0']['email']) ? $querythird['0']['email'] : 0;
        return $totalUns =  ($custAll + $UnsNl) - $allsubsUser;
    }


    /**
    * check port 587 open or not, for using Sendinblue smtp service.
    */
    public function checkPortStatus()
    {
        try {
            $relay_port_status = fsockopen(‘smtp-relay.sendinblue.com’, 587);
            if (!$relay_port_status) {
                return 0;
            }
        } catch (\Exception $e) {
           return 1;
        }
    }

    //get Table prefix
    public function tbWithPrefix($tableName)
    {
        return $this->_getTb->getTable($tableName);
    }

//end class
}

