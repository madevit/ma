<?php

namespace Malca\Amit\Controller\Adminhtml\Order;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Index extends \Magento\Backend\App\Action
{
    protected $resultJsonFactory;
    protected $date;
    protected $scopeConfig;
    protected $orderCollectionFactory;
    public $pageSize=20;
    protected $registry;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->date = $date;
        $this->scopeConfig = $scopeConfig;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->registry = $registry;
        parent::__construct($context);
    }
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();

        $uname = $this->scopeConfig->getValue('malca/amit/username');
        if ($uname == '') {
            $this->_redirect($this->getUrl("amit/index"));
        }
        $ordersarr = $this->orderCollectionFactory->create()->addAttributeToSelect('*')
            ->setOrder('created_at', 'desc')
            ->addFieldToFilter('ma_tracking', ['null' => true], 'left')
            ->setPageSize($this->pageSize)
            ->setCurPage('1');
        $orders = [];
        $countrycode =  $this->scopeConfig->getValue('shipping/origin/country_id');
        foreach ($ordersarr as $key => $order) {
            $orders[$key]=$order->getData();
            if ($order->getShippingAddress()) {
                $orderAddress= $order->getShippingAddress()->getData();
            } else {
                $orderAddress= $order->getBillingAddress()->getData();
            }

            if ($countrycode != $orderAddress['country_id']) {
                $orders[$key]['international'] = true;
            }
        }

        $ordersarrtotal = $this->orderCollectionFactory->create()->addAttributeToSelect('*')
            ->addFieldToFilter('ma_tracking', ['null' => true], 'left');
        $total = count($ordersarrtotal)/$this->pageSize;
        if ((int) $total != $total) {
            $total = (int) $total+1;
        }

        $this->registry->register('totalPage', $total);
        $this->registry->register('order_arr', $orders);

        $this->_view->renderLayout();
    }
}
