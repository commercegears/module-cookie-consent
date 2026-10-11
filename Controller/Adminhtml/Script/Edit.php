<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Controller\Adminhtml\Script;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use CommerceGears\CookieConsent\Api\ScriptRepositoryInterface;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_CookieConsent::scripts';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
        private readonly ScriptRepositoryInterface $scriptRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $id = (int)$this->getRequest()->getParam('script_id');
        $title = __('New Script');
        if ($id) {
            try {
                $title = __('Edit Script "%1"', $this->scriptRepository->getById($id)->getName());
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }
        $page = $this->pageFactory->create();
        $page->setActiveMenu('CommerceGears_CookieConsent::scripts');
        $page->getConfig()->getTitle()->prepend($title);
        return $page;
    }
}
