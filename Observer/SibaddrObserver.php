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
class SibaddrObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Sendinblue\Sendinblue\Model\SendinblueSib');
        $updateDataInSib = array();
        $address = $observer->getCustomerAddress();
        $customer = $address->getCustomer();
        $billing = $address->getIsDefaultBilling();
        $shipping = $address->getIsDefaultShipping();
        $status = !empty($billing) ? $billing : $shipping;
        $email= $customer->getEmail();
        $NlStatus = '';
        $NlStatus = $model->checkNlStatus($email);
        $sibStatus = $model->syncSetting();
        if ($status == 1 && $NlStatus == 1 && $sibStatus == 1) {
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
            $model->subscribeByruntime($email, $updateDataInSib);
        }
    }
}
