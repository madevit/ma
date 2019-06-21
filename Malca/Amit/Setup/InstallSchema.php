<?php

namespace Malca\Amit\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.0.0') < 0) {
            $installer->getConnection()
                ->addColumn(
                    $installer->getTable('sales_order'), 'ma_tracking',
                    array(
                        'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'nullable'  => true,
                        'length'    => 70,
                        'after'     => null,
                        'comment'   => 'Malca-Amit tracking Number'
                    )
                );
            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order'), 'ma_couriertype',
                array(
                'type'      => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'nullable'  => true,
                'length'    => 70,
                'after'     => null, 
                'comment'   => 'Malca-Amit tracking Company'
                )
            );

            $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
             
            $appState = $objectManager->get('\Magento\Framework\App\State');
            $appState->setAreaCode('frontend');
             
            $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $store = $storeManager->getStore();

            $baseUrl = $store->getBaseUrl();

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $objDate = $objectManager->create('Magento\Framework\Stdlib\DateTime\DateTime');
        
            $params = array(
                "StoreDetails"=>array(
                "PlatformType"=>'Magento2',
                "StoreId"=>$baseUrl,
                "StoreName"=>'',
                "StoreDomain"=>$baseUrl,
                "StationCode"=>'',
                "Token"=>$baseUrl,
                "AppStatus"=>'installed',
                "AppStatusChangeDate"=>$objDate->gmtDate('Y-m-d'),
                "AppStatusCreatedDate"=>$objDate->gmtDate('Y-m-d'),
                "UserCode"=>'',
                "CompanyName"=>'',
                "CompanyWebsite"=>'',
                "ContactPerson"=>'',
                "CustomerPhone"=>'',
                "CustomerEmail"=>'',
                ),
                "ApplicationId"=>969
            );
            try {
                $client = new \SoapClient("https://my.malca-amit.us/mymabookingwebservice/malcaamitservices.asmx?wsdl");
                $client->__setLocation('https://my.malca-amit.us/MyMABookingWebService/MalcaAmitServices.asmx');

                $actionHeader = new \SoapHeader('http://tempuri.org/', 'getMAEXEcommerceStoreDetails', true);
                $client->__setSoapHeaders($actionHeader);

                $result = $client->__soapCall('getMAEXEcommerceStoreDetails', $params);
            } catch (\Exception $e) {
                $e->getMessage();
            }
        }
        $installer->endSetup();
    }
}
