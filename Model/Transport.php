<?php
/**
 * Mail Transport
 */
namespace Sendinblue\Sendinblue\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\MessageInterface;
use Zend\Mail\Message;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail\Transport\Smtp;

class Transport extends \Zend_Mail_Transport_Smtp implements \Magento\Framework\Mail\TransportInterface
{
    /** @var MessageInterface */
	protected $_message;

	protected $_scopeConfig;
	
    
    /** @var Smtp|\Zend_Mail_Transport_Smtp **/
    protected $transport;

    
    /**
     * @param MessageInterface $message
     * @param null $parameters
     * @throws \InvalidArgumentException
     */
    public function __construct(MessageInterface $message, ScopeConfigInterface $scopeConfig ){
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
			
			if ($message instanceof \Zend_mail) {
				$this->transport = new \Zend_Mail_Transport_Smtp($smtpHost, [
					'auth' => $smtpAuthentication,
					'tls' => $smtpTls, 
					'port' => $smtpPort,
					'username' => $smtpUsername,
					'password' => $smtpPassword
				]);
			} elseif ($message instanceof MessageInterface) {
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
				if ($this->_message instanceof \Zend_mail) {
					$this->transport->send($this->_message);
				} elseif ($this->_message instanceof MessageInterface) {
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
