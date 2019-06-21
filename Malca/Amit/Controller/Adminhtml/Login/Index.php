<?php

namespace Malca\Amit\Controller\Adminhtml\Login;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Index extends \Magento\Backend\App\Action
{
    protected $resultJsonFactory;
    protected $date;
    protected $configWriter;
    protected $cacheTypeList;
    protected $cacheFrontendPool;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->date = $date;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        parent::__construct($context);
    }
    public function execute()
    {
        $postParams = $this->getRequest()->getParams();
        if (isset($postParams['isLogin'])) {
            $arrLogin = [
                'UserName' => trim($postParams['username']),
                'UserPassword'=> trim($postParams['password']),
                'UserStationCode'=>trim($postParams['stationcode']),
                'SourceTypeId' => 1
            ];
            try {
                $client = new \SoapClient("https://my.malca-amit.us/MyMABookingWebService/MalcaAmitServices.asmx?wsdl");
                $client->__setLocation('https://my.malca-amit.us/MyMABookingWebService/MalcaAmitServices.asmx');
                $client->sendRequest = true;
                $client->printRequest = true;
                $client->formatXML = true;
                $actionHeader = new \SoapHeader('http://tempuri.org/', 'CheckMAEXUserAuthentication', true);
                $client->__setSoapHeaders($actionHeader);

                $params = ['UserAuthentication' => $arrLogin];
                $result = $client->__soapCall('CheckMAEXUserAuthentication', [$params]);
                $result= json_decode(json_encode($result), true);
                $checkuser = $result['CheckMAEXUserAuthenticationResult'];

                if ($checkuser['Code'] == '200') {
                    $this->configWriter->save('malca/amit/username', $postParams['username'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                    $this->configWriter->save('malca/amit/password', $postParams['password'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                    $this->configWriter->save('malca/amit/stationcode', $postParams['stationcode'], ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

                    $this->cacheTypeList->cleanType('config');

                    foreach ($this->cacheFrontendPool as $cacheFrontend) {
                        $cacheFrontend->getBackend()->clean();
                    }
                    $msg = "success";
                    $error = false;
                } else {
                    $msg = $checkuser['Description'];
                    $error = true;
                }
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                $error = true;
            }
        } elseif (isset($postParams['isSignup'])) {
            if (isset($postParams['g-recaptcha-response'])) {
                $captcha=$postParams['g-recaptcha-response'];
            }

            if (!$captcha) {
                $msg = 'Please check the captcha form';
                $error = true;
            } else {
                $url="https://www.google.com/recaptcha/api/siteverify?";
                $url.="secret=6LfjS2UUAAAAANdyVu-WWbh-YuJNXoYnEvxxBNBt&response=".$captcha;
                //@codingStandardsIgnoreStart
                $response=json_decode(file_get_contents($url), true);
                //@codingStandardsIgnoreEnd
                if ($response['success'] == false) {
                    $msg = 'You are spammer !';
                    $error = true;
                } else {
                    if (!empty($postParams)) {
                        $baseUrl = $this->getUrl();
                        $params = [
                            "StoreDetails"=>[
                                "PlatformType"=>'Magento',
                                "StoreId"=>$baseUrl,
                                "StoreName"=>'',
                                "StoreDomain"=>$baseUrl,
                                "StationCode"=>'',
                                "Token"=>$baseUrl,
                                "AppStatus"=>'installed',
                                "AppStatusChangeDate"=>$this->date->gmtDate('Y-m-d'),
                                "AppStatusCreatedDate"=>$this->date->gmtDate('Y-m-d'),
                                "UserCode"=>'',
                                "CompanyName"=>$postParams['companyname'],
                                "CompanyWebsite"=>$postParams['companywebsite'],
                                "ContactPerson"=>$postParams['contactperson'],
                                "CustomerPhone"=>$postParams['phone'],
                                "CustomerEmail"=>$postParams['emailsignup'],
                            ],
                            "ApplicationId"=>969
                        ];
                        try {
                            $wsdl = "https://my.malca-amit.us/mymabookingwebservice/malcaamitservices.asmx?wsdl";
                            $client = new \SoapClient($wsdl);
                            $client->__setLocation('https://my.malca-amit.us/MyMABookingWebService/MalcaAmitServices.asmx');

                            $actionHeader = new \SoapHeader('http://tempuri.org/', 'setMAEXEcommerceStoreDetails', true);
                            $client->__setSoapHeaders($actionHeader);

                            $result = $client->__soapCall('setMAEXEcommerceStoreDetails', [$params]);
                            
                            $msg = 'Success';
                            $error = false;
                        } catch (\Exception $e) {
                            $msg = $e->getMessage();
                            $error = true;
                        }
                    }
                }
            }
        } elseif (isset($postParams['isLogout'])) {
            $this->configWriter->save('malca/amit/username', '', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
            $this->configWriter->save('malca/amit/password', '', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
            $this->configWriter->save('malca/amit/stationcode', '', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
            $this->cacheTypeList->cleanType('config');
            foreach ($this->cacheFrontendPool as $cacheFrontend) {
                $cacheFrontend->getBackend()->clean();
            }

            $msg = "success";
            $error = false;
        }
        $result = $this->resultJsonFactory->create();
        return $result->setData(['error' => $error, 'message' => $msg]);
    }
}
