<?php
/**
 * GiaPhuGroup Co., Ltd.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GiaPhuGroup.com license that is
 * available through the world-wide-web at this URL:
 * https://www.giaphugroup.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PHPCuong
 * @package     PHPCuong_LoginAsCustomer
 * @copyright   Copyright (c) 2020-2021 GiaPhuGroup Co., Ltd. All rights reserved. (http://www.giaphugroup.com/)
 * @license     https://www.giaphugroup.com/LICENSE.txt
 */

namespace PHPCuong\LoginAsCustomer\Controller\Login;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    protected $customerSessionFactory;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    protected $cookieMetadataManager;

    /**
     * @var \PHPCuong\LoginAsCustomer\Model\LoginFactory
     */
    protected $loginFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\SessionFactory $customerSessionFactory
     * @param \PHPCuong\LoginAsCustomer\Model\LoginFactory $loginFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        \PHPCuong\LoginAsCustomer\Model\LoginFactory $loginFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerRepository = $customerRepository;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->loginFactory = $loginFactory;
        parent::__construct($context);
    }

    /**
     * Login as a customer
     *
     * @return boolean|void
     */
    public function execute()
    {
        try {
            $customerSession = $this->customerSessionFactory->create();
            /* Logout if logged in */
            if ($lastCustomerId = $customerSession->getId()) {
                $customerSession->logout()->setBeforeAuthUrl(
                    $this->_redirect->getRefererUrl()
                )->setLastCustomerId($lastCustomerId);
                if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                    $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                    $metadata->setPath('/');
                    $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
                }
            }
            $loginKey = $this->getRequest()->getParam('key');
            $customerId = (int)$this->getRequest()->getParam('customer_id');
            // Login key validation
            $loginValidation = $this->loginFactory->create()->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('login_key', $loginKey);
            // Check if the login key is valid from the table named phpcuong_login_as_customer
            if ($loginValidation->getSize() <= 0) {
                $this->messageManager->addError(__('Cannot login as a customer.'));
                $this->_redirect('customer/account/login');
                return true;
            }

            // Starting login as a customer
            $customer = $this->customerRepository->getById($customerId);
            $this->customerSessionFactory->create()->setCustomerDataAsLoggedIn($customer)->regenerateId();
            $customerFullName = $customer->getFirstName().' '.$customer->getLastName();
            $this->messageManager->addSuccess(
                __('You are logged in as customer: %1.', $customerFullName)
            );

            $this->_redirect('phpcuong_loginascustomer/login/success');
            return true;
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('customer/account/login');
            return true;
        }
    }

    /**
     * Retrieve cookie manager
     *
     * @deprecated 100.1.0
     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated 100.1.0
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }
}
