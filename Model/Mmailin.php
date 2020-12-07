<?php
namespace Sendinblue\Sendinblue\Model;
class Mmailin
{
    public $api_key;
    public $base_url;
    public function __construct($params)
    {
        if (!function_exists('curl_init')) {
            throw new \Exception('Mailin requires CURL module');
        }
        $this->base_url = "https://api.sendinblue.com/v2.0";
        $this->api_key = $params['api_key'];

    }

    /**
     * Do CURL request with authorization
     */
    private function doRequest($resource, $method, $input)
    {
        $called_url = $this->base_url."/".$resource;
        $ch = curl_init($called_url);
        $auth_header = 'api-key:'.$this->api_key;
        $content_header = "Content-Type:application/json";
        $track_header = "sib-plugin:magento-1.3.5";
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows only over-ride
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($auth_header, $content_header, $track_header));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            return '<div style="background:#d14836; padding:10px; color:#fff; font-weight:600; position:aboslute;width:100%;top:0;">Curl error: '.curl_error($ch).'</div>';
        }
        curl_close($ch);
        return json_decode($data, true);
    }
    
    public function post($resource, $input)
    {
        return $this->doRequest($resource, "POST", $input);
    }

    /*
        Create v3 key.
        No input required
    */
    public function generateApiV3Key()
    {
        return $this->post("/account/generateapiv3key",  json_encode(['name' => 'magento']));
    }
}
