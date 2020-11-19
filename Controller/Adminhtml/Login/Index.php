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

namespace PHPCuong\LoginAsCustomer\Controller\Adminhtml\Login;

class Index extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'PHPCuong_LoginAsCustomer::login';

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \PHPCuong\LoginAsCustomer\Model\LoginFactory
     */
    protected $loginFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \PHPCuong\LoginAsCustomer\Model\LoginFactory $loginFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \PHPCuong\LoginAsCustomer\Model\LoginFactory $loginFactory
    ) {
        $this->customerRepository = $customerRepository;
        $this->loginFactory = $loginFactory;
        parent::__construct($context);
    }

    /**
     * Login as a customer
     *
     * @return boolean
     */
    public function execute()
    {
        try {
            $customerId = (int)$this->getRequest()->getParam('customer_id');
            $customer = $this->customerRepository->getById($customerId);
            $storeManager = $this->_objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
            $store = $storeManager->getStore((int)$customer->getStoreId());
            $url = $this->_objectManager->get(\Magento\Framework\Url::class)->setScope($store);
            $loginKey = md5(rand(5000, 9999).time());
            $adminUser = $this->_objectManager->get(\Magento\Backend\Model\Auth\Session::class)->getUser();
            // Save the login key into the database named phpcuong_login_as_customer
            $this->loginFactory->create()->setData([
                'customer_id' => $customerId,
                'admin_id' => $adminUser->getId(),
                'login_key' => $loginKey
            ])->save();
            // Redirect to the login url on the storefront
            // This is the URL is used on the frontend
            $redirectUrl = $url->getUrl('phpcuong_loginascustomer/login/index',
                [
                    'key' => $loginKey,
                    '_nosid' => true,
                    'customer_id' => $customerId
                ]
            );
            $this->getResponse()->setRedirect($redirectUrl);
            return true;
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('customer/index');
            return true;
        }
    }
}
