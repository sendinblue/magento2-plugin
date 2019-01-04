<?php
/**
 * Mail Transport
 */
namespace Sendinblue\Sendinblue\Model;

class Transport extends \Zend_Mail_Transport_Smtp implements \Magento\Framework\Mail\TransportInterface
{
    /**
     * @var \Magento\Framework\Mail\MessageInterface
     */
    protected $_message;
    public $scopeConfig;
    /**
     * @param MessageInterface $message
     * @param null $parameters
     * @throws \InvalidArgumentException
     */
    public function __construct(\Magento\Framework\Mail\MessageInterface $message,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
        )
    {
        if (!$message instanceof \Zend_Mail) {
                throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }

        $smtpHost = '';
        $smtpConf = array();
        $relaySib = $scopeConfig->getValue('sendinblue/relay_data_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($relaySib == 'enabled') {
            $smtpHost = $scopeConfig->getValue('sendinblue/smtp_host', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $smtpAuthentication = $scopeConfig->getValue('sendinblue/smtp_authentication', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $smtpTls = $scopeConfig->getValue('sendinblue/smtp_tls', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $smtpPort = $scopeConfig->getValue('sendinblue/smtp_port', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $smtpUsername = $scopeConfig->getValue('sendinblue/smtp_username', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $smtpPassword = $scopeConfig->getValue('sendinblue/smtp_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $smtpConf = [
                'auth' => $smtpAuthentication,
                'tls' => $smtpTls, 
                'port' => $smtpPort,
                'username' => $smtpUsername,
                'password' => $smtpPassword
            ];

            
        }
        parent::__construct($smtpHost, $smtpConf);
        $this->_message = $message;  
    
    }
 
    /**
     * Send a mail using this transport
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        try {
            parent::send($this->_message);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\MailException(new \Magento\Framework\Phrase($e->getMessage()), $e);
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
