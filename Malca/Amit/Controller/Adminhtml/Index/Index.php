<?php

namespace Malca\Amit\Controller\Adminhtml\Index;

class Index extends \Magento\Backend\App\Action
{
    protected $scopeConfig;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute()
    {
        $uname = $this->scopeConfig->getValue('malca/amit/username');

        if ($uname != '') {
            $this->_redirect($this->getUrl("amit/order"));
        }

        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        $this->_view->renderLayout();
    }
}
