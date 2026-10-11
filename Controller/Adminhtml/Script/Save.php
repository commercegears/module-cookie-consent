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
use Magento\Framework\Exception\LocalizedException;
use CommerceGears\CookieConsent\Api\Data\ScriptInterface;
use CommerceGears\CookieConsent\Api\Data\ScriptInterfaceFactory;
use CommerceGears\CookieConsent\Api\ScriptRepositoryInterface;
use CommerceGears\CookieConsent\Model\Config\Source\Categories;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'CommerceGears_CookieConsent::scripts';

    public function __construct(
        Context $context,
        private readonly ScriptRepositoryInterface $scriptRepository,
        private readonly ScriptInterfaceFactory $scriptFactory,
        private readonly Categories $categories
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create();
        $data = (array)$this->getRequest()->getPostValue();
        if (!$data) {
            return $redirect->setPath('*/*/');
        }
        $id = (int)($data['script_id'] ?? 0);
        try {
            $script = $id ? $this->scriptRepository->getById($id) : $this->scriptFactory->create();
            $name = trim((string)($data['name'] ?? ''));
            if ($name === '') {
                throw new LocalizedException(__('Please enter a name.'));
            }
            $category = (string)($data['category'] ?? '');
            if (!in_array($category, array_column($this->categories->toOptionArray(), 'value'), true)) {
                throw new LocalizedException(__('Please choose a cookie category.'));
            }
            $content = (string)($data['content'] ?? '');
            $opened = preg_match_all('#<script\b#i', $content);
            $closed = preg_match_all('#</script\s*>#i', $content);
            if ($opened !== $closed) {
                throw new LocalizedException(__(
                    'The snippet has %1 opening <script> tag(s) but %2 closing </script> tag(s). Please paste the complete snippet.',
                    $opened,
                    $closed
                ));
            }
            $storeIds = $data['store_ids'] ?? [0];
            $storeIds = array_map('intval', is_array($storeIds) ? $storeIds : explode(',', (string)$storeIds));
            if ($storeIds === [] || in_array(0, $storeIds, true)) {
                $storeIds = [0];
            }
            $script->setName($name)
                ->setIsActive((bool)($data['is_active'] ?? false))
                ->setCategory($category)
                ->setPosition(($data['position'] ?? '') === ScriptInterface::POSITION_HEAD
                    ? ScriptInterface::POSITION_HEAD : ScriptInterface::POSITION_BODY_END)
                ->setStoreIds($storeIds)
                ->setSortOrder((int)($data['sort_order'] ?? 0))
                ->setContent($content);
            $this->scriptRepository->save($script);
            $this->messageManager->addSuccessMessage(__('The script has been saved.'));
            if ($this->getRequest()->getParam('back')) {
                return $redirect->setPath('*/*/edit', ['script_id' => $script->getScriptId()]);
            }
            return $redirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the script.'));
        }
        return $id ? $redirect->setPath('*/*/edit', ['script_id' => $id]) : $redirect->setPath('*/*/new');
    }
}
