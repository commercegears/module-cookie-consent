<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Controller\Adminhtml\Script;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;

class NewAction extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_CookieConsent::scripts';

    public function execute(): ResultInterface
    {
        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_FORWARD)->forward('edit');
    }
}
