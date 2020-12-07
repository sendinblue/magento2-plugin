<?php
namespace Sendinblue\Sendinblue\Model;
class SendinblueSibClient
{
    const API_BASE_URL = 'https://api.sendinblue.com/v3';
    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';
    const HTTP_METHOD_DELETE = 'DELETE';
    const RESPONSE_CODE_OK = 200;
    const RESPONSE_CODE_CREATED = 201;
    const RESPONSE_CODE_ACCEPTED = 202;
    const RESPONSE_CODE_UPDATED = 204;

    private $apiKey;
    private $lastResponseCode;

    /**
     * SibApiClient constructor.
     */
    public function __construct($key = "")
    {
        if( empty($key) ) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $model = $objectManager->create('Sendinblue\Sendinblue\Model\SendinblueSib');
            $this->apiKey = trim($model->_getValueDefault->getValue('sendinblue/api_key_v3', $model->_scopeTypeDefault));            
        }
        else {
            $this->apiKey = trim($key);
        }
    }

    /**
     * @return mixed
     */
    public function getAccount()
    {
        return $this->get('/account');
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getLists($data)
    {
        return $this->get("/contacts/lists",$data);
    }


    /**
     * @param $data
     * @return mixed
     */
    public function getListsInFolder($folder, $data)
    {
        return $this->get("/contacts/folders/".$folder."/lists", $data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function importUsers($data)
    {
        return $this->post("/contacts/import",$data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getAllLists($folder = 0)
    {
        $lists = array("lists" => array(), "count" => 0);
        $offset = 0;
        $limit = 50;
        do {
            if ($folder > 0) {
                $list_data = $this->getListsInFolder($folder, array('limit' => $limit, 'offset' => $offset));
            }
            else {
                $list_data = $this->getLists(array('limit' => $limit, 'offset' => $offset));    
            }
            if ( !isset($list_data["lists"]) ) {
                $list_data = array("lists" => array(), "count" => 0);
            }
            $lists["lists"] = array_merge($lists["lists"], $list_data["lists"]) ;
            $offset += 50;
        }
        while ( count($lists["lists"]) < $list_data["count"] );
        $lists["count"] = $list_data["count"];
        return $lists;
    }

    /*
     * @return mixed
     */
    public function getAttributes()
    {
        return $this->get("/contacts/attributes");
    }

    /**
     * @param $type,$name,$data
     * @return mixed
     */
    public function createAttribute($type, $name, $data)
    {
        return $this->post("/contacts/attributes/".$type."/".$name,$data);
    }

    /**
     * @return mixed
     */
    public function getFolders()
    {
        return $this->get("/contacts/folders");
    }

    public function getFoldersAll()
    {
        $folders = array("folders" => array(), "count" => 0);
        $offset = 0;
        $limit = 50;
        do {
            $folder_data = $this->getFolders(array('limit' => $limit, 'offset' => $offset));
            $folders["folders"] = array_merge($folders["folders"],$folder_data["folders"]) ;
            $offset += 50;
        }
        while ( count($folders["folders"]) < $folder_data["count"] );
        $folders["count"] = $folder_data["count"];
        return $folders;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function createFolder($data)
    {
        return $this->post("/contacts/folders", $data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function createList($data)
    {
        return $this->post("/contacts/lists", $data);
    }

    /**
     * @param $email
     * @return mixed
     */
    public function getUser($email)
    {
        return $this->get("/contacts/". urlencode($email));
    }

    /**
     * @param $data
     * @return mixed
     */
    public function createUser($data)
    {
        return $this->post("/contacts",$data);
    }

    /**
     * @param $email,$data
     * @return mixed
     */
    public function updateUser($email, $data)
    {
        return $this->put("/contacts/".urlencode($email), $data);
    }
  
    public function sendSms($data)
    {
        return $this->post('/transactionalSMS/sms',$data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function setPartner($data)
    {
        return $this->post('/account/partner',$data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function sendTransactionalTemplate($data)
    {
        return $this->post("/smtp/email",$data);
    }

    public function createSmsCampaign($data)
    {
        return $this->post('/smsCampaigns',$data);
    }


    /**
     * @param $data
     * @return mixed
     */
    public function getEmailTemplates($data)
    {
        return $this->get("/smtp/templates",$data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getAllEmailTemplates()
    {
        $templates = array("templates" => array(), "count" => 0);
        $offset = 0;
        $limit = 50;
        do {
            $template_data = $this->getEmailTemplates(array('templateStatus' => 'true', 'limit' => $limit, 'offset' => $offset));
            if ( !isset($template_data["templates"]) ) {
                $template_data = array("templates" => array(), "count" => 0);
            }
            $templates["templates"] = array_merge($templates["templates"],$template_data["templates"]) ;
            $offset += 50;
        }
        while ( count($templates["templates"]) < $template_data["count"] );
        $templates["count"] = count($templates["templates"]);
        return $templates;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getTemplateById($id)
    {
        return $this->get("/smtp/templates/". $id);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function sendEmail($data)
    {
        return $this->post("/smtp/email",$data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getSenders()
    {
        return $this->get("/senders");
    }
  
    /**
     * @param $endpoint
     * @param array $parameters
     * @return mixed
     */
    public function get($endpoint, $parameters = [])
    {
        if ($parameters) {
            $endpoint .= '?' . http_build_query($parameters);
        }
        return $this->makeHttpRequest(self::HTTP_METHOD_GET, $endpoint);
    }

    /**
     * @param $endpoint
     * @param array $data
     * @return mixed
     */
    public function post($endpoint, $data = [])
    {
        return $this->makeHttpRequest(self::HTTP_METHOD_POST, $endpoint, $data);
    }

    /**
     * @param $endpoint
     * @param array $data
     * @return mixed
     */
    public function put($endpoint, $data = [])
    {
        return $this->makeHttpRequest(self::HTTP_METHOD_PUT, $endpoint, $data);
    }

    /**
     * @param $method
     * @param $endpoint
     * @param array $body
     * @return mixed
     */
    private function makeHttpRequest($method, $endpoint, $body = [])
    {
        $url = self::API_BASE_URL . $endpoint;
        $this->lastResponseCode = "";
        $args = [
            'method' => $method,
            'headers' => [
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ],
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => $method,
          CURLOPT_HTTPHEADER => [
            "content-type: application/json",
            "accept: application/json",
            "api-key: ".$this->apiKey
          ],
        ]);

        if( !empty($body) ) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        }
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $this->lastResponseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            return $err;
        }
        
        return json_decode($response, true);
    }

    /**
     * @return int
     */
    public function getLastResponseCode()
    {
        return $this->lastResponseCode;
    }
}