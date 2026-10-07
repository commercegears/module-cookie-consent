<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Controller\Adminhtml\Script;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Softaware_CookieConsent::scripts';

    public function __construct(Context $context, private readonly PageFactory $pageFactory)
    {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Softaware_CookieConsent::scripts');
        $page->getConfig()->getTitle()->prepend(__('Third-party Scripts'));
        return $page;
    }
}
