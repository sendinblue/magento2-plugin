<?php
namespace Sendinblue\Sendinblue\Block\Adminhtml;

use Magento\Backend\Block\Template;
//use Sendinblue\Sendinblue\Helper\ConfigHelper;
//use Sendinblue\Sendinblue\Model\AdminSample;

class SendinblueBlock extends Template
{
    protected $_config;
    protected $_adminSampleModel;

    /**
    * @param Context $context
    * @param array $data
    */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getDataDb()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $dbConfig = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface');
        return $dbConfig;
    }
    public function getFormValue()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $FormKey = $objectManager->get('Magento\Framework\Data\Form\FormKey');
        return $FormKey->getFormKey();
    }
}
