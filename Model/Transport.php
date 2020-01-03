<?php
/**
 * Mail Transport
 */
namespace Sendinblue\Sendinblue\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\EmailMessage;
use Zend\Mail\Message;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\ScopeInterface;
use Zend\Mail\Transport\Sendmail;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail\Transport\Smtp;
use Magento\Framework\App\ObjectManager;

class Transport implements TransportInterface
{
    /** @var MessageInterface */
    protected $_message;

    protected $_scopeConfig;
    protected $_magVersion;
    
    
    /** @var Smtp|\Zend_Mail_Transport_Smtp **/
    protected $transport;

    
    /**
     * @param MessageInterface $message
     * @param null $parameters
     * @throws \InvalidArgumentException
     */
    public function __construct($message = null, ScopeConfigInterface $scopeConfig, $parameters = null ){
        
        $magVersion = $scopeConfig->getValue('sendinblue/mag_version_sib', 'default');

        if (empty($magVersion)) {
            $objectManager = ObjectManager::getInstance();
            $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
            $coreConfigObj = $objectManager->get('Magento\Framework\App\Config\ConfigResource\ConfigInterface');
            $magVersionSib = $productMetadata->getVersion();
            $coreConfigObj->saveConfig('sendinblue/mag_version_sib', $magVersionSib, 'default', 0);
            $magVersion = $scopeConfig->getValue('sendinblue/mag_version_sib', 'default');
        }

        $this->_magVersion = $magVersion;
        $this->_message = $message;
        $this->_scopeConfig = $scopeConfig;

        $relaySib = $scopeConfig->getValue('sendinblue/relay_data_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($relaySib == 'enabled') {
            $smtpHost = "smtp-relay.sendinblue.com";
            $smtpAuthentication = $scopeConfig->getValue('sendinblue/smtp_authentication', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $smtpTls = $scopeConfig->getValue('sendinblue/smtp_tls', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $smtpPort = $scopeConfig->getValue('sendinblue/smtp_port', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $smtpUsername = $scopeConfig->getValue('sendinblue/smtp_username', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $smtpPassword = $scopeConfig->getValue('sendinblue/smtp_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            
            if ($this->_magVersion >= '2.3.3' && $message instanceof EmailMessage) {
                $this->transport = new Smtp( new SmtpOptions([
                    'host' => $smtpHost,
                    'port' => $smtpPort,
                    'connection_class' => $smtpAuthentication,
                    'connection_config' => [
                        'username' => $smtpUsername,
                        'password' => $smtpPassword,
                        'ssl' => $smtpTls,
                    ],
                ]));
            } elseif ($this->_magVersion <= '2.2.0' && $message instanceof \Zend_mail) {
                $this->transport = new \Zend_Mail_Transport_Smtp($smtpHost, [
                    'auth' => $smtpAuthentication,
                    'tls' => $smtpTls, 
                    'port' => $smtpPort,
                    'username' => $smtpUsername,
                    'password' => $smtpPassword
                ]);
            } elseif ($this->_magVersion <= '2.3.2' && $message instanceof MessageInterface) {
                $this->transport = new Smtp( new SmtpOptions([
                    'host' => $smtpHost,
                    'port' => $smtpPort,
                    'connection_class' => $smtpAuthentication,
                    'connection_config' => [
                        'username' => $smtpUsername,
                        'password' => $smtpPassword,
                        'ssl' => $smtpTls,
                    ],
                ]));
            }
        }
    }
 
    /**
     * Send a mail using this transport
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        if ($this->_scopeConfig->isSetFlag('system/smtp/disable',\Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
                return false;
        }

        if(null !== $this->transport) {
            try {
                if ( $this->_magVersion >= '2.3.3' && $this->_message instanceof EmailMessage) {
                    $zend_mail = Message::fromString($this->_message->getRawMessage());
                    $subject = $zend_mail->getSubject();
                    $zend_mail->setSubject(htmlspecialchars_decode((string)$subject, ENT_QUOTES));
                    $this->transport->send($zend_mail);
                } elseif ( $this->_magVersion <= '2.2.0' && $this->_message instanceof \Zend_mail) {
                    $this->transport->send($this->_message);
                } elseif ($this->_magVersion <= '2.3.2' && $this->_message instanceof MessageInterface) {
                    $zend_mail = Message::fromString($this->_message->getRawMessage());
                    $subject = $zend_mail->getSubject();
                    $zend_mail->setSubject(htmlspecialchars_decode((string)$subject, ENT_QUOTES));
                    $this->transport->send($zend_mail);
                }
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\MailException(new \Magento\Framework\Phrase($e->getMessage()), $e);
            }
        } 
    }
    
    /**
     * Get a mail responce using this transport
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function getMessage() {
        return $this->_message;
    }

}
