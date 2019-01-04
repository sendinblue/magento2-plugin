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
class SibNlObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Sendinblue\Sendinblue\Model\SendinblueSib');
        $updateDataInSib = [];
        $subscriberData = $observer->getEvent()->getSubscriber()->getData();
        $email = $subscriberData['subscriber_email'];
        $NlStatus = $subscriberData['subscriber_status'];
        $sibStatus = $model->syncSetting();
        if ($sibStatus == 1) {
            if (!empty($subscriberData['customer_id']) && $subscriberData['customer_id'] > 0 && $NlStatus == 1) {
                $customer = $model->getCustomer($subscriberData['customer_id']);
                $billingId = !empty($customer['default_billing']) ? $customer['default_billing'] : '';
                $telephone = '';
                $firstName = $customer['firstname'];
                $lastName = $customer['lastname'];
                $storeView = $customer['created_in'];
                $storeId = $customer['store_id'];
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

                $updateDataInSib['CLIENT'] = 1;

                if (!empty($storeId)) {
                    $updateDataInSib['STORE_ID'] = $storeId;
                }
                if (!empty($storeView)) {
                    $updateDataInSib['MAGENTO_LANG'] = $storeView;
                }

                if (!empty($billingId)) {
                    $address = $objectManager->create('Magento\Customer\Model\Address')->load($billingId);
                    $street = $address->getStreet();
                    $streetValue = '';
                    foreach ($street as $streetData) {
                        $streetValue.= $streetData.' ';
                    }

                    $smsValue = !empty($address->getTelephone()) ? $address->getTelephone() : '';

                    $countryId = !empty($address->getCountryId()) ? $address->getCountryId() : '';
                    if (!empty($smsValue) && !empty($countryId)) {
                        $countryCode = $model->getCountryCode($countryId);
                        if (!empty($countryCode)) {
                            $updateDataInSib['SMS'] = $model->checkMobileNumber($smsValue, $countryCode);
                        }
                    }

                    $updateDataInSib['COMPANY'] = !empty($address->getCompany()) ? $address->getCompany() : '';
                    $updateDataInSib['COUNTRY_ID'] = !empty($address->getCountryId()) ? $address->getCountryId() : '';
                    $updateDataInSib['STREET'] = !empty($streetValue) ? $streetValue : '';
                    $updateDataInSib['POSTCODE'] = !empty($address->getPostcode()) ? $address->getPostcode() : '';
                    $updateDataInSib['REGION'] = !empty($address->getRegion()) ? $address->getRegion() : '';
                    $updateDataInSib['CITY'] = !empty($address->getCity()) ? $address->getCity() : '';
                }
                $model->subscribeByruntime($email, $updateDataInSib);
            } else {
                if ($NlStatus == 1) {
                    $updateDataInSib['CLIENT'] = 0;
                    $storeId = $subscriberData['store_id'];
                    if (!empty($storeId)) {
                        $updateDataInSib['STORE_ID'] = $storeId;
                    }
                    $storeId = $subscriberData['store_id'];
                    $stores = $model->_storeManagerInterface->getStores(true, false);
                    foreach ($stores as $store) {
                        if ($store->getId() == $storeId) {
                            $storeView = $store->getName();
                        }
                    }
                    if (!empty($storeView)) {
                        $updateDataInSib['MAGENTO_LANG'] = $storeView;
                    }
                    $model->subscribeByruntime($email, $updateDataInSib);
                    $model->sendWsTemplateMail($email);
                } else {
                    $model->unsubscribeByruntime($email);
                }
            }
        }
    }
}
