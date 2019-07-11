<?php
/**
 * @author Sendinblue plateform <contact@sendinblue.com>
 * @copyright  2013-2014 Sendinblue
 * URL:  https:www.sendinblue.com
 * Do not edit or add to this file if you wish to upgrade Sendinblue Magento plugin to newer
 * versions in the future. If you wish to customize Sendinblue magento plugin for your
 * needs then we can't provide a technical support.
 **/
namespace Sendinblue\Sendinblue\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Sendinblue\Sendinblue\Model;

/**
 * Customer Observer Model
 */
class SibObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Sendinblue\Sendinblue\Model\SendinblueSib');
        $customer = $observer->getEvent()->getData('customer');
        $customerId = $customer->getId();
        $email= $customer->getEmail();
        $NlStatus = $model->checkNlStatus($email);
        $apiKey = $model->getDbData('api_key');
        $sibStatus = $model->syncSetting();
        if ($NlStatus == 1 && $sibStatus == 1) {
            $firstName = $customer->getFirstName();
            $lastName = $customer->getLastName();
            $storeView = $customer->getCreatedIn();
            $storeId = $customer->getStoreId();
            $updateDataInSib = [];
            $localeLang = $model->getDbData('sendin_config_lang');
            if (!empty($firstName)) {
                if ($localeLang == 'fr') {
                    $updateDataInSib['PRENOM'] = $firstName;
                } else {
                    $updateDataInSib['NAME'] = $firstName;
                }
            }
            if (!empty($lastName)) {
                if ($localeLang == 'fr') {
                    $updateDataInSib['NOM'] = $lastName;
                } else {
                    $updateDataInSib['SURNAME'] = $lastName;
                }
            }
            if (!empty($firstName)) {
                $updateDataInSib['CLIENT'] = 1;
            } else {
                $updateDataInSib['CLIENT'] = 0;
            }
            if (!empty($storeId)) {
                $updateDataInSib['STORE_ID'] = $storeId;
            }
            if (!empty($storeView)) {
                $updateDataInSib['MAGENTO_LANG'] = $storeView;
            }
            $model->subscribeByruntime($email, $updateDataInSib);
            $model->sendWsTemplateMail($email);
        }
    }
}
