<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Controller\Adminhtml\Script;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use CommerceGears\CookieConsent\Api\ScriptRepositoryInterface;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_CookieConsent::scripts';

    public function __construct(Context $context, private readonly ScriptRepositoryInterface $scriptRepository)
    {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/');
        $id = (int)$this->getRequest()->getParam('script_id');
        try {
            $this->scriptRepository->delete($this->scriptRepository->getById($id));
            $this->messageManager->addSuccessMessage(__('The script has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $redirect;
    }
}
