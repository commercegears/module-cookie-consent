<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Controller\Adminhtml\Script;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;
use Softaware\CookieConsent\Api\ScriptRepositoryInterface;
use Softaware\CookieConsent\Model\ResourceModel\Script\CollectionFactory;

class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Softaware_CookieConsent::scripts';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly ScriptRepositoryInterface $scriptRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = 0;
        foreach ($collection as $script) {
            $this->scriptRepository->delete($script);
            $count++;
        }
        $this->messageManager->addSuccessMessage(__('%1 script(s) deleted.', $count));
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
