<?php
namespace Sendinblue\Sendinblue\Helper;

class ConfigHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @param  string param
     */
    public function getConfig($req)
    {
        return $this->scopeConfig->getValue('adminsample/' . $req);
    }
}
