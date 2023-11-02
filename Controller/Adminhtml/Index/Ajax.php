<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sendinblue\Sendinblue\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;

use Sendinblue\Sendinblue\Model;

class Ajax extends \Magento\Backend\App\Action
{
    /**
     * Post user question
     * @return void
     * @throws \Exception
     */

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_redirect('*/*/');
            return;
        }
        try {
            $model = $this->sibObject();
            if (isset($post['manageSubsVal']) && !empty($post['manageSubsVal'])) {
                $this->ajaxSubscribeConfig();
            }

            if (isset($post['sync_cron_activate']) && !empty($post['sync_cron_activate'])) {
                $this->ajaxSyncContactConfig();
            }

            if (isset($post['ord_track_btn']) && !empty($post['ord_track_btn'])) {
                $this->ajaxOrderStatus();
            }
            if (isset($post['sib_tracking']) && !empty($post['sib_tracking'])) {
                if ($post['sib_track_status'] == 1) {
                    $this->automationEnable();
                } else {
                    $model->updateDbData('sib_track_status', $post['sib_track_status']);
                    $msgVal = __('Your setting has been successfully saved');
                    $this->getResponse()->setHeader('Content-type', 'application/text');
                    $this->getResponse()->setBody($msgVal);
                }
            }
            //SMTP settings enable or disable
            if (isset($post['smtp_post']) && !empty($post['smtp_post'])) {
                if ($post['smtps_tatus'] == 1) {
                    $this->ajaxSmtpStatus();
                } else {
                    $model->resetSmtpDetail();
                    $msgVal = __('Your setting has been successfully saved');
                    $this->getResponse()->setHeader('Content-type', 'application/text');
                    $this->getResponse()->setBody($msgVal);
                }
            }

            //notify email for sms limit is cross.
            if (isset($post['sms_credit_post']) && !empty($post['sms_credit_post'])) {
                $this->ajaxSmsOperations();
            }

            //update order sms status.
            if (isset($post['order_setting_post']) && !empty($post['order_setting_post'])) {
                $this->ajaxSmsOperations();
            }

            //update shipped sms status.
            if (isset($post['shiping_setting_post']) && !empty($post['shiping_setting_post'])) {
                $this->ajaxSmsOperations();
            }

            //update campaign sms status.
            if (isset($post['campaign_setting_post']) && !empty($post['campaign_setting_post'])) {
                $this->ajaxSmsOperations();
            }

            //send test order sms.
            if (isset($post['order_send_post']) && !empty($post['order_send_post'])) {
                $sender = !empty($post['sender']) ? $post['sender'] : '';
                $message = !empty($post['message']) ? $post['message'] : '';
                $number = !empty($post['number']) ? $post['number'] : '';
                if (!empty($sender) && !empty($message) && !empty($number)) {
                    $respVal = $model->sendOrderTestSms($sender, $message, $number);
                    if ($respVal == 'OK') {
                        $msg = __('Message has been sent successfully');
                    } else {
                        $msg = __('Message has not been sent successfully');
                    }
                    $this->getResponse()->setHeader('Content-type', 'application/text');
                    $this->getResponse()->setBody($msg);
                }
            }

            //send test Shipped sms.
            if (isset($post['shipped_send_post']) && !empty($post['shipped_send_post'])) {
                $sender = !empty($post['sender']) ? $post['sender'] : '';
                $message = !empty($post['message']) ? $post['message'] : '';
                $number = !empty($post['number']) ? $post['number'] : '';
                if (!empty($sender) && !empty($message) && !empty($number)) {
                    $respVal = $model->sendShippedTestSms($sender, $message, $number);
                    if ($respVal == 'OK') {
                        $msg = __('Message has been sent successfully');
                    } else {
                        $msg = __('Message has not been sent successfully');
                    }
                    $this->getResponse()->setHeader('Content-type', 'application/text');
                    $this->getResponse()->setBody($msg);
                }
            }

            //send test campaign sms.
            if (isset($post['campaign_test_submit']) && !empty($post['campaign_test_submit'])) {
                $sender = !empty($post['sender']) ? $post['sender'] : '';
                $message = !empty($post['message']) ? $post['message'] : '';
                $number = !empty($post['number']) ? $post['number'] : '';
                if (!empty($sender) && !empty($message) && !empty($number)) {
                    $respVal = $model->sendCampaignTestSms($sender, $message, $number);
                    if ($respVal == 'OK') {
                        $msg = __('Message has been sent successfully');
                    } else {
                        $msg = __('Message has not been sent successfully');
                    }
                    $this->getResponse()->setHeader('Content-type', 'application/text');
                    $this->getResponse()->setBody($msg);
                }
            }

            //Import old order history by csv .
            if (isset($post['order_import_post']) && !empty($post['order_import_post'])) {
                $post = $this->getRequest()->getPostValue();
                if ($post['ord_track_status'] == 1) {
                    $respData = $model->importOrderhistory();
                    if ($respData) {
                        $msgVal = __('Order history has been import successfull');
                        $this->getResponse()->setHeader('Content-type', 'application/text');
                        $this->getResponse()->setBody($msgVal);
                    } else {
                        $msgVal = __('Order history has not been imported successfull');
                        $this->getResponse()->setHeader('Content-type', 'application/text');
                        $this->getResponse()->setBody($msgVal);
                    }
                }
            }

            if (isset($post['submitUpdateImport']) && !empty($post['submitUpdateImport'])) {
                $model = $this->sibObject();
                $listId = $model->getDbData('selected_list_data');
                $model->sendAllMailIDToSendin($listId);
                $importOlduserStatus = $model->getDbData('import_old_user_status');
                if ($importOlduserStatus == 0) {
                    $this->messageManager->addSuccess(__('Old subscribers imported successfully'));
                    $this->_redirect('sendinblue/sib/index');
                    return;
                } else {
                    $this->messageManager->addError(__('Old subscribers not imported successfully, please click on Import Old Subscribers button to import them again'));
                    $this->_redirect('sendinblue/sib/index');
                    return;
                }
            }

            //Subscribe contact list .
            if (isset($post['contact_subs']) && !empty($post['contact_subs'])) {
                $post = $this->getRequest()->getPostValue();
                $this->subsUnsubsContact();
            }
        } catch (\Exception $e) {
            $this->messageManager->addError(
                __('We can\'t process your request right now.')
            );
            $this->_redirect('sendinblue/sib/index');
            return;
        }
    }

    /**
     * Determine if authorized to perform group actions.
     *
     * @return bool
     */
    public function _isAllowed()
    {
        return true;
    }

    public function ajaxSubscribeConfig()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_redirect('sendinblue/sib/index');
            return;
        }
        $model = $this->sibObject();
        $model->updateDbData('subscribe_setting', $post['managesubscribe']);
        $msgVal = __('Sendiblue configuration setting Successfully updated');
        $this->getResponse()->setHeader('Content-type', 'application/text');
        $this->getResponse()->setBody($msgVal);
    }

    public function ajaxSyncContactConfig()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_redirect('sendinblue/sib/index');
            return;
        }
        $model = $this->sibObject();
        $model->updateDbData('sib_contact_sync_list', $post['sib_contact_sync_list']);
        $model->updateDbData('sib_contact_sync_status', $post['sib_contact_sync_status']);

        if( $post['sib_contact_sync_list'] != 0 && $post['sib_contact_sync_status'] != 0 ) {
                $model->sendAllMailIDToSendin($post['sib_contact_sync_list']);
                $importOlduserStatus = $model->getDbData('import_old_user_status');
                if ($importOlduserStatus == 0) {
                    $msgVal = __('Old subscribers imported successfully');
                }
                else if ($importOlduserStatus == 1) {
                    $msgVal = __('Old subscribers not imported successfully, please click on Save button to import them again');
                }
                else {
                    $msgVal = __('Old subscribers are not exists');
                }
        }
        else {
            $msgVal = __('Sendiblue configuration setting Successfully updated');
        }
        $this->getResponse()->setHeader('Content-type', 'application/text');
        $this->getResponse()->setBody($msgVal);
    }

    public function ajaxOrderStatus() {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_redirect('sendinblue/sib/index');
            return;
        }
        $model = $this->sibObject();
        $model->updateDbData('ord_track_status', $post['ord_track_status']);
        $model->updateDbData('order_import_status', $post['ord_track_status']);
        $msgVal = __('Sendiblue configuration setting Successfully updated ');
        if ($post['import_order_data'] == 1) {
            $respData = $model->importOrderhistory();
            if ($respData) {
                $msgVal .= __('Order history has been import successfully');
                $this->getResponse()->setHeader('Content-type', 'application/text');
                $this->getResponse()->setBody($msgVal);
            } else {
                $msgVal .= __('Order history has not been imported successfully');
                $this->getResponse()->setHeader('Content-type', 'application/text');
                $this->getResponse()->setBody($msgVal);
            }
        }
        $this->getResponse()->setHeader('Content-type', 'application/text');
        $this->getResponse()->setBody($msgVal);
    }

    public function automationEnable() {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_redirect('sendinblue/sib/index');
            return;
        }
        $model = $this->sibObject();
        $trackResp = $model->trackingSmtp();
        if (!empty($trackResp['marketingAutomation']) && $trackResp['marketingAutomation']['enabled'] == 1) {
            $model->updateDbData('sib_track_status', $post['sib_track_status']);
            $model->updateDbData('sib_automation_key', $trackResp['marketingAutomation']['key']);
            $model->updateDbData('sib_automation_enable', $trackResp['marketingAutomation']['enabled']);
            $msgVal = __('Sendiblue configuration setting Successfully updated');
            $this->getResponse()->setHeader('Content-type', 'application/text');
            $this->getResponse()->setBody($msgVal);
        } else {
            $model->updateDbData('sib_track_status', 0);
            $msgVal = __("To activate Marketing Automation , please go to your Sendinblue's account or contact us at contact@sendinblue.com");
            $this->getResponse()->setHeader('Content-type', 'application/text');
            $this->getResponse()->setBody($msgVal);
        }
    }

    public function ajaxSmtpStatus()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_redirect('sendinblue/sib/index');
            return;
        }
        $model = $this->sibObject();
        $pass_data = trim($post['smtp_pass']);
        $dataResp = $model->trackingSmtp();
        if (empty($pass_data) || !$dataResp) {
            $model->updateDbData('api_smtp_status', 0);
            $msgVal = __('Your SMTP account is not activated and therefore you can\'t use Sendinblue SMTP. For more informations, please contact our support to: contact@sendinblue.com');
        } else if ($dataResp['relay']['enabled']) {
            $model->updateDbData('api_smtp_status', $post['smtps_tatus']);
            $model->updateDbData('relay_data_status', 'enabled');
            $model->updateDbData('smtp_authentication', 'crammd5');
            $model->updateDbData('smtp_username', $dataResp['relay']['data']['userName']);
            $model->updateDbData('smtp_password', $pass_data);
            $model->updateDbData('smtp_host', $dataResp['relay']['data']['relay']);
            $model->updateDbData('smtp_port', $dataResp['relay']['data']['port']);
            $model->updateDbData('smtp_tls', 'tls');
            $model->updateDbData('smtp_option', 'smtp');
            $msgVal = __('Your setting has been successfully saved');
        } else {
            $msgVal = __('Your SMTP account is not activated and therefore you can\'t use Sendinblue SMTP. For more informations, please contact our support to: contact@sendinblue.com');
        }
        $this->getResponse()->setHeader('Content-type', 'application/text');
        $this->getResponse()->setBody($msgVal);
    }
    //notify sms and update sms status

    public function ajaxSmsOperations()
    {
        $post = $this->getRequest()->getPostValue();

        if (!$post) {
            $this->_redirect('sendinblue/sib/index');
            return;
        }
        $model = $this->sibObject();
        if ($post['type'] == 'order') {
            $model->updateDbData('api_sms_order_status', $post['order_setting']);
        }

        if ($post['type'] == 'shiping') {
            $model->updateDbData('api_sms_shipment_status', $post['shiping_setting']);
        }

        if ($post['type'] == 'campaign') {
            $model->updateDbData('api_sms_campaign_status', $post['campaign_setting']);
        }

        if ($post['type'] == 'sms_credit') {
            $model->updateDbData('api_sms_credit', $post['sms_credit']);
        }
        $msgVal = __('Your setting has been successfully saved');
        $this->getResponse()->setHeader('Content-type', 'application/text');
        $this->getResponse()->setBody($msgVal);
    }

    public function sibObject()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->create('Sendinblue\Sendinblue\Model\SendinblueSib');
    }

    public function viewObject()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager->create('Magento\Backend\Block\Template');
    }

    public function saveTemplateValue()
    {
        $model = $this->sibObject();
        $post = $this->getRequest()->getPostValue();
        $valueTemplateId = !empty($post['template']) ? $post['template'] : '';
        $doubleOptinTempId = empty($post['doubleoptin_template_id']) ? $post['doubleoptin_template_id'] : '';
        $subscribeConfirmType = !empty($post['subscribe_confirm_type']) ? $post['subscribe_confirm_type'] : '';
        $optinRedirectUrlCheck = !empty($post['optin_redirect_url_check']) ? $post['optin_redirect_url_check'] : '';
        $doubleoptinRedirectUrl = !empty($post['doubleoptin_redirect_url']) ? $post['doubleoptin_redirect_url'] : '';
        $finalConfirmEmail = !empty($post['final_confirm_email']) ? $post['final_confirm_email'] : '';
        $finalTempId = !empty($post['template_final']) ? $post['template_final'] : '';
        $shopApiKeyStatus = $model->getDbData('api_key_status');

        $model->updateDbData('doubleoptin_template_id', $doubleOptinTempId);
        $model->updateDbData('template_id', $valueTemplateId);
        $model->updateDbData('optin_url_check', $optinRedirectUrlCheck);
        $model->updateDbData('doubleoptin_redirect', $doubleoptinRedirectUrl);
        $model->updateDbData('final_confirm_email', $finalConfirmEmail);
        if (!empty($finalTempId)) {
            $model->updateDbData('final_template_id', $finalTempId);
        }
        $model->updateSender();
        if (!empty($subscribeConfirmType)) {
            $model->updateDbData('confirm_type', $subscribeConfirmType);
            if ($subscribeConfirmType == 'doubleoptin') {
                $resOptin = $model->checkFolderListDoubleoptin();
                if (!empty($resOptin['optin_id'])) {
                    $model->updateDbData('optin_list_id', $resOptin['optin_id']);
                }

                if ( $resOptin === false && !empty($shopApiKeyStatus) ) {
                    $mailin = $model->createObjSibClient();

                        $data = [];
                        $data = ["name"=> "FORM"];
                        $folderRes = $mailin->createFolder($data);
                        $folderId = $folderRes['data']['id'];

                        $data = [];
                        $data = [
                          "list_name" => 'Temp - DOUBLE OPTIN',
                          "list_parent" => $folderId
                        ]; 
                        $listResp = $mailin->createList($data);
                        $listId = $listResp['data']['id'];
                        $model->updateDbData('optin_list_id', $listId);
                }
            }
        }
        $displayList = $post['display_list'];
        if (!empty($displayList)) {
            if ($model->getDbData('subscribe_setting') == 1) {
                $listValue = implode('|', $displayList);
                $model->updateDbData('selected_list_data', $listValue);
            } else {
                $model->updateDbData('subscribe_setting', 0);
            }
        }
        $this->messageManager->addSuccess(__('Sendiblue configuration setting Successfully updated'));
        $this->_redirect('sendinblue/sib/index');
        return;
    }

    //subscribe contact from contact list

    public function subsUnsubsContact()
    {
        $model = $this->sibObject();
        $connection = $model->createDbConnection();
        $tblNewsletter = $model->tbWithPrefix('newsletter_subscriber');
        $post = $this->getRequest()->getPostValue();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $updateDataInSib = [];
        $email = !empty($post['email']) ? $post['email'] : '';
        $postNewsLetter = !empty($post['newsletter']) ? $post['newsletter'] : '';
        $templateSubscribeStatus = ($postNewsLetter == 0) ? 1 : 3;
 
        if (!empty($email) && $postNewsLetter == 0) {
            $storeId = $model->_storeManagerInterface->getStore()->getId();
            $model->_customers->setWebsiteId($storeId);
            $dataCust = $model->_customers->loadByEmail($email);
            $customer = $dataCust->getData();
            if (isset($customer['entity_id']) > 0) {
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
                    foreach ($street as $streetData){
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
                $subscriberData = $connection->fetchAll('SELECT `store_id` FROM `'.$tblNewsletter.'` WHERE subscriber_email ='."'$email'");

                $updateDataInSib['CLIENT'] = 0;
                $storeId = !empty($subscriberData[0]['store_id']) ? $subscriberData[0]['store_id'] : '';
                if (!empty($storeId)) {
                    $updateDataInSib['STORE_ID'] = $storeId;
                }
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
            }
            //first check and then update and insert
            $newsData = $connection->fetchAll('SELECT * FROM `'.$tblNewsletter.'` WHERE subscriber_email ='."'$email'");

            if (empty($newsData[0]['subscriber_email'])) {
                $newsLetterData = [
                        "store_id" => $customer['store_id'],
                        "customer_id" => $customer['entity_id'],
                        "subscriber_email" => $email,
                        "change_status_at" => date('Y-m-d H:i:s'),
                        "subscriber_status" => 1,
                ];
                $connection->insert($tblNewsletter, $newsLetterData);
            } else {
                $newsLetterData = ['subscriber_status' => $templateSubscribeStatus];
                $condition = ['subscriber_email = ?'=> $email];
                $connection->update($tblNewsletter, $newsLetterData, $condition);
            }
        } else {
            $model->unsubscribeByruntime($email);
            $newsLetterData = ['subscriber_status' => $templateSubscribeStatus];
            $condition = ['subscriber_email = ?'=> $email];
            if (!empty($email)) {
                $connection->update($tblNewsletter, $newsLetterData, $condition);
            }
        }
    }
}

