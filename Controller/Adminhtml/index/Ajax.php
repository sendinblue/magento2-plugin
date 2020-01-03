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

            if (isset($post['ord_track_btn']) && !empty($post['ord_track_btn'])) {
                $this->ajaxOrderStatus();
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
                    if ($respData == 0) {
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

            //load contact list .
            if (isset($post['contact_data']) && !empty($post['contact_data'])) {
                $post = $this->getRequest()->getPostValue();
                $this->loadContact();
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

    public function ajaxOrderStatus() {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_redirect('sendinblue/sib/index');
            return;
        }
        $model = $this->sibObject();
        $model->updateDbData('ord_track_status', $post['ord_track_status']);
        $model->updateDbData('order_import_status', $post['ord_track_status']);
        $msgVal = __('Sendiblue configuration setting Successfully updated');
        $this->getResponse()->setHeader('Content-type', 'application/text');
        $this->getResponse()->setBody($msgVal);
    }

    public function ajaxSmtpStatus()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_redirect('sendinblue/sib/index');
            return;
        }
        $model = $this->sibObject();
        $apiKey = $model->getDbData('api_key');
        if (!empty($apiKey)) {
            $dataResp = $model->trackingSmtp();
            if (isset($dataResp['code']) && $dataResp['code'] == 'success') {
                if (isset($dataResp['data']['relay_data']['status']) && $dataResp['data']['relay_data']['status'] == 'enabled') {
                    $model->updateDbData('api_smtp_status', $post['smtps_tatus']);
                    $model->updateDbData('relay_data_status', $dataResp['data']['relay_data']['status']);
                    $model->updateDbData('smtp_authentication', 'crammd5');
                    $model->updateDbData('smtp_username', $dataResp['data']['relay_data']['data']['username']);
                    $model->updateDbData('smtp_password', $dataResp['data']['relay_data']['data']['password']);
                    $model->updateDbData('smtp_host', $dataResp['data']['relay_data']['data']['relay']);
                    $model->updateDbData('smtp_port', $dataResp['data']['relay_data']['data']['port']);
                    $model->updateDbData('smtp_tls', 'tls');
                    $model->updateDbData('smtp_option', 'smtp');
                    $msgVal = __('Your setting has been successfully saved');
                    $this->getResponse()->setHeader('Content-type', 'application/text');
                    $this->getResponse()->setBody($msgVal);
                } else {
                    $model->updateDbData('api_smtp_status', 0);
                    $msgVal = __('Your SMTP account is not activated and therefore you can\'t use Sendinblue SMTP. For more informations, please contact our support to: contact@sendinblue.com');
                    $this->getResponse()->setHeader('Content-type', 'application/text');
                    $this->getResponse()->setBody($msgVal);
                }
            }
        }
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
        $shopApiKey = $model->getDbData('api_key');

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
                $resOptin = $model->checkFolderListDoubleoptin($shopApiKey);
                if (!empty($resOptin['optin_id'])) {
                    $model->updateDbData('optin_list_id', $resOptin['optin_id']);
                }

                if ($resOptin === false) {
                    $mailin = $model->createObjMailin($shopApiKey);
                    if (!empty($shopApiKey)) {
                        $data = [];
                        $data = ["name"=> "FORM"];
                        $folderRes = $mailin->createFolder($data);
                        $folderId = $folderRes['data']['id'];
                    }

                    if (!empty($shopApiKey)) {
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
    public function loadContact()
    {
        $model = $this->sibObject();
        $blockObj = $this->viewObject();
        $post = $this->getRequest()->getPostValue();
        $title1 = __('Unsubscribe the contact');
        $title2 = __('Subscribe the contact');
        $title3 = __('Unsubscribe the sms');
        $title4 = __('Subscribe the sms');
        $first = __('First');
        $last = __('Last');
        $previous = __('Previous');
        $next = __('Next');
        $yes = __('yes');
        $no = __('no');
        $page = (int)$post['page'];
        $currentPage = $page;
        $page--;
        $perPage = 20;
        $previousButton = true;
        $nextButton = true;
        $firstButton = true;
        $lastButton = true;
        $start = $page * $perPage;
        $count = $model->getCustAndNewslCount();
        $noOfPaginations = ceil($count / $perPage);
        if ($currentPage >= 7) {
            $startLoop = $currentPage - 3;
            if ($noOfPaginations > $currentPage + 3) {
                $endLoop = $currentPage + 3;
            } elseif ($currentPage <= $noOfPaginations && $currentPage > $noOfPaginations - 6) {
                $startLoop = $noOfPaginations - 6;
                $endLoop   = $noOfPaginations;
            } else {
                $endLoop = $noOfPaginations;
            }
        } else {
            $startLoop = 1;
            if ($noOfPaginations > 7) {
                $endLoop = 7;
            } else {
                $endLoop = $noOfPaginations;
            }
        }

        $collection = $model->getNewsletterSubscribe($start, $perPage);
        $sendinUserStatus = $model->checkUserSendinStatus($collection);

        $sendinUserResult = isset($sendinUserStatus['data']) ? $sendinUserStatus['data'] : '';
        if (!empty($collection)) {
            $i = 1;
            $message = '';
            foreach ($collection as $subscriber) {
                $email = isset($subscriber['email']) ? $subscriber['email'] : '';
                $phone = isset($subscriber['SMS']) ? $subscriber['SMS'] : '';

                $client = (!empty($subscriber['client']) > 0) ? $yes : $no ;
                $showStatus = '';
                $smsStatus = '';
                if (isset($sendinUserResult[$email])) {
                    $emailBalanceValue = isset($sendinUserResult[$email]['email_bl']) ? $sendinUserResult[$email]['email_bl'] : '';

                    if ($emailBalanceValue === 1 || $sendinUserResult[$email] == null) {
                        $showStatus = 0;
                    }

                    if ($emailBalanceValue === 0) {
                        $showStatus = 1;
                    }

                    $smsBalance = isset($sendinUserResult[$email]['sms_bl']) ? $sendinUserResult[$email]['sms_bl'] : '';
                    $smsExist = isset($sendinUserResult[$email]['sms_exist']) ? $sendinUserResult[$email]['sms_exist'] : '';
                    $subScriberTelephone = isset($subscriber['SMS']) ? $subscriber['SMS'] : '';

                    if ($smsBalance === 1 && $smsExist > 0) {
                        $smsStatus = 0;
                    } else if ($smsBalance === 0 && $smsExist > 0) {
                        $smsStatus = 1;
                    } else if ($smsExist <= 0 && empty($subScriberTelephone)) {
                         $smsStatus = 2;
                    } else if ($smsExist <= 0 && !empty($subScriberTelephone)) {
                        $smsStatus = 3;
                    }
                }

                if ($subscriber['subscriber_status'] == 1) { 
                    $imgMagento = '<img src="'.$blockObj->getViewFileUrl('Sendinblue_Sendinblue::images/enabled.gif').'" >';
                } else {
                    $imgMagento = '<img src="'.$blockObj->getViewFileUrl('Sendinblue_Sendinblue::images/disabled.gif').'" >';
                }

                $smsStatus = $smsStatus >= 0 ? $smsStatus : '';

                if ($smsStatus === 1) {
                    $imgSms = '<img src="'.$blockObj->getViewFileUrl('Sendinblue_Sendinblue::images/enabled.gif').'" id="ajax_contact_status_'.$i.'" title="'.$title3.'" >';
                } else if ($smsStatus === 0) {
                    $imgSms = '<img src="'.$blockObj->getViewFileUrl('Sendinblue_Sendinblue::images/disabled.gif').'" id="ajax_contact_status_'.$i.'" title="'.$title4.'" >';
                } else if ($smsStatus === 2 || $smsStatus === '') {
                    $imgSms = '';
                } else if ($smsStatus === 3) {
                    $imgSms = 'Not synchronized';
                }

                $showStatus = !empty($showStatus) ? $showStatus : '0';

                if ($showStatus == 1) {
                    $imgSendinBlue = '<img src="'.$blockObj->getViewFileUrl('Sendinblue_Sendinblue::images/enabled.gif').'" id="ajax_contact_status_'.$i.'" title="'.$title1.'" >';
                } else {
                    $imgSendinBlue = '<img src="'.$blockObj->getViewFileUrl('Sendinblue_Sendinblue::images/disabled.gif').'" id="ajax_contact_status_'.$i.'" title="'.$title2.'" >';
                }
                $imgMagento = str_replace('_view','Magento/backend', $imgMagento);
                $imgSendinBlue = str_replace('_view','Magento/backend', $imgSendinBlue);
                $imgSms = str_replace('_view','Magento/backend', $imgSms);
                $message .= '<tr  class="even pointer"><td class="a-left">'.$email.'</td><td class="a-left">'.$client.'</td><td class="a-left">'.$phone.'</td><td class="a-left">'.$imgMagento.'</td>
                    <td class="a-left"><a status="'.$showStatus.'" email="'.$email.'" class="ajax_contacts_href" href="javascript:void(0)">
            '.$imgSendinBlue.'</a></td><td class="a-left last">
            '.$imgSms.'</td></tr>';

                $i++;
            }
        }
        $messagePaging = '';
        $messagePaging .= '<tr><td colspan="7"><div class="pagination"><ul class="pull-left">';

        if ($firstButton && $currentPage > 1) {
            $messagePaging .= '<li p="1" class="active">'.$first.'</li>';
        } else if ($firstButton) {
            $messagePaging .= '<li p="1" class="inactive">'.$first.'</li>';
        }

        if ($previousButton && $currentPage > 1) {
            $previousValue = $currentPage - 1;
            $messagePaging .= '<li p="'.$previousValue.'" class="active">'.$previous.'</li>';
        } else if ($previousButton) {
            $messagePaging .= '<li class="inactive">'.$previous.'</li>';
        }

        for ($i = $startLoop; $i <= $endLoop; $i++) {
            if ($currentPage == $i) {
                $messagePaging .= '<li p="'.$i.'" style="color:#fff;background-color:#000000;" class="active">'.$i.'</li>';
            } else {
                $messagePaging .= '<li p="'.$i.'"  class="active">'.$i.'</li>';
            }
        }

        if ($nextButton && $currentPage < $noOfPaginations) {
            $nextValue = $currentPage + 1;
            $messagePaging .= '<li p="'.$nextValue.'" class="active">'.$next.'</li>';
        } else if ($nextButton) {
            $messagePaging .= '<li class="inactive">'.$next.'</li>';
        }

        if ($lastButton && $currentPage < $noOfPaginations) {
            $messagePaging .= '<li p="'.$noOfPaginations.'" class="active">'.$last.'</li>';
        } else if ($lastButton) {
            $messagePaging .= '<li p="'.$noOfPaginations.'" class="inactive">'.$last.'</li>';
        }

        if ($count != 0) {
            $this->getResponse()->setHeader('Content-type', 'application/html');
            $this->getResponse()->setBody($message . $messagePaging).'</td></tr>';
        }
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

