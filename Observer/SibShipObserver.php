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

class SibShipObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $model = $objectManager->create('Sendinblue\Sendinblue\Model\SendinblueSib');

        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        $apiKey = $model->getDbData('api_key');
        $dateValue = $model->getDbData('sendin_date_format');
        $orderStatus = $model->getDbData('api_sms_shipment_status');
        $senderOrder = $model->getDbData('sender_shipment');
        $senderOrderMessage = $model->getDbData('sender_shipment_message');
        $orderId = $order->getID();
        $orderDatamodel = $objectManager->get('Magento\Sales\Api\Data\OrderInterface')->load($orderId);
        $orderData = $orderDatamodel->getData();
        $email = $orderData['customer_email'];
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

            if ($orderStatus == 1 && !empty($senderOrder) && !empty($senderOrderMessage)) {
                $custId = $orderData['customer_id'];
                if (!empty($custId)) {
                    $customers = $model->_customers->load($custId);
                    $shoppingId =  $customers->getDefaultShipping();
                    $address = $objectManager->create('Magento\Customer\Model\Address')->load($shoppingId);
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
