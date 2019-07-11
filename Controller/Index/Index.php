<?php
/**
* @author Sendinblue plateform <contact@sendinblue.com>
* @copyright  2017-2018 Sendinblue
* URL:  https:www.sendinblue.com
* Do not edit or add to this file if you wish to upgrade Sendinblue Magento plugin to newer
* versions in the future. If you wish to customize Sendinblue magento plugin for your
* needs then we can't provide a technical support.
**/
namespace Sendinblue\Sendinblue\Controller\Index;
 
use Magento\Framework\App\Action\Context;
use Sendinblue\Sendinblue\Model;
class Index extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
    public $_model;
    public function __construct(Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory
        )
    {
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_model = $objectManager->create('\Sendinblue\Sendinblue\Model\SendinblueSib');
    }
 
    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();
        $getValue = $this->getRequest()->getParam('value');
        $userEmail = base64_decode($getValue);
        $this->dubleoptinProcess($userEmail);
        //return $resultPage;
    }

    /**
    * Description: get responce and send confirm subscription mail and redirect in given url
    *
    */
    public function dubleoptinProcess($userEmail)
    {
        $nlStatus = $this->_model->checkNlStatus($userEmail);
        if (!empty($userEmail) && $nlStatus = 1) {
            $apiKey = $this->_model->getDbData('api_key');
            $optinListId = $this->_model->getDbData('optin_list_id');
            $listId = $this->_model->getDbData('selected_list_data');

            $mailin = $this->_model->createObjMailin($apiKey);

            $data = array( "email" => $userEmail,
                    "attributes" => array("DOUBLE_OPT-IN"=>1),
                    "blacklisted" => 0,
                    "listid" => array($listId),
                    "listid_unlink" => array($optinListId),
                    "blacklisted_sms" => 0
                );
            $mailin->createUpdateUser($data);
            $confirmEmail = $this->_model->getDbData('final_confirm_email');
            if ($confirmEmail === 'yes') {
                $finalId = $this->_model->getDbData('final_template_id');
                $this->_model->sendOptinConfirmMailResponce($userEmail, $finalId, $apiKey);
            }
        }
        $doubleoptinRedirect = $this->_model->getDbData('doubleoptin_redirect');
        $optinUrlCheck = $this->_model->getDbData('optin_url_check');
        if ($optinUrlCheck === 'yes' && !empty($doubleoptinRedirect)) {
            header("Location: ".$doubleoptinRedirect);
            ob_flush_end();
        } else {
            $shopName = $_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
            header("Location: ".$shopName);
            ob_flush_end();
        }
    }
    
}
