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
class SibOrderObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Sendinblue\Sendinblue\Model\SendinblueSib');
        $apiKey = $model->getDbData('api_key');
        $trackStatus = $model->getDbData('ord_track_status');
        $dateValue = $model->getDbData('sendin_date_format');
        $orderStatus = $model->getDbData('api_sms_order_status');
        $senderOrder = $model->getDbData('sender_order');
        $senderOrderMessage = $model->getDbData('sender_order_message');
        $order = $observer->getEvent()->getData();
        $orderId = $order['order_ids'][0];
        $orderDatamodel = $objectManager->get('Magento\Sales\Api\Data\OrderInterface')->load($orderId);
        $orderData = $orderDatamodel->getData();
        $email = $orderData['customer_email'];
        $NlStatus = $model->checkNlStatus($email);
        $orderID = $orderData['increment_id'];
        $orderPrice = $orderData['grand_total'];
        $dateAdded = $orderData['created_at'];
        $sibStatus = $model->syncSetting();
        if ($sibStatus == 1) {
            if (!empty($apiKey)) {
                $mailin = $model->createObjMailin($apiKey);
            }

            if (!empty($dateValue) && $dateValue == 'dd-mm-yyyy') {
                $orderDate = date('d-m-Y', strtotime($dateAdded));
            } else {
                $orderDate = date('m-d-Y', strtotime($dateAdded));
            }

            if ($trackStatus == 1 && $NlStatus == 1 && !empty($apiKey)) {
                $blacklistedValue = 0;
                $attrData = [];
                $attrData['ORDER_DATE'] = $orderDate;
                $attrData['ORDER_PRICE'] = $orderPrice;
                $attrData['ORDER_ID'] = $orderID;
                $dataSync = ["email" => $email,
                "attributes" => $attrData,
                "blacklisted" => $blacklistedValue
                ];
                $mailin->createUpdateUser($dataSync);
            }
            if ($orderStatus == 1 && !empty($senderOrder) && !empty($senderOrderMessage)) {
                $custId = $orderData['customer_id'];
                if (!empty($custId)) {
                    $customers = $model->_customers->load($custId);
                    $billingId =  $customers->getDefaultBilling();
                    $billingId = !empty($billingId) ? $billingId : $customers->getDefaultShipping();
                    $address = $objectManager->create('Magento\Customer\Model\Address')->load($billingId);
                }

                $firstname = $address->getFirstname();
                $lastname = $address->getLastname();
                $telephone = !empty($address->getTelephone()) ? $address->getTelephone() : '';
                $countryId = !empty($address->getCountry()) ? $address->getCountry() : '';
                $smsVal = '';
                if (!empty($countryId) && !empty($telephone)) {
                    $countryCode = $model->getCountryCode($countryId);
                    if (!empty($countryCode)) {
                        $smsVal = $model->checkMobileNumber($telephone, $countryCode);
                    }
                }
                $firstName = str_replace('{first_name}', $firstname, $senderOrderMessage);
                $lastName = str_replace('{last_name}', $lastname."\r\n", $firstName);
                $procuctPrice = str_replace('{order_price}', $orderPrice, $lastName);
                $orderDate = str_replace('{order_date}', $orderDate."\r\n", $procuctPrice);
                $msgbody = str_replace('{order_reference}', $orderID, $orderDate);
                $smsData = [];

                if (!empty($smsVal)) {
                    $smsData['to'] = $smsVal;
                    $smsData['from'] = $senderOrder;
                    $smsData['text'] = $msgbody;
                    $smsData['type'] = 'transactional';
                    $model->sendSmsApi($smsData);
                }
            }
        }
    }
}
