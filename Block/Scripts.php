<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Block;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Context;
use Magento\Store\Model\StoreManagerInterface;
use CommerceGears\CookieConsent\Api\Data\ScriptInterface;
use CommerceGears\CookieConsent\Api\ScriptRepositoryInterface;
use CommerceGears\CookieConsent\Model\Config;
use CommerceGears\CookieConsent\Model\Script;
use CommerceGears\CookieConsent\Model\ScriptBlocker;

/**
 * Prints the admin-managed third-party scripts for one position (layout argument "position").
 */
class Scripts extends AbstractBlock implements IdentityInterface
{
    public function __construct(
        Context $context,
        private readonly Config $config,
        private readonly ScriptRepositoryInterface $scriptRepository,
        private readonly ScriptBlocker $blocker,
        private readonly StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        return [Script::CACHE_TAG];
    }

    protected function _toHtml(): string
    {
        if (!$this->config->isEnabled()) {
            return '';
        }
        $position = $this->getData('position') === ScriptInterface::POSITION_HEAD
            ? ScriptInterface::POSITION_HEAD
            : ScriptInterface::POSITION_BODY_END;
        $storeId = (int)$this->storeManager->getStore()->getId();
        $html = '';
        foreach ($this->scriptRepository->getActiveForStore($storeId, $position) as $script) {
            $html .= "\n<!-- sw-consent: " . $this->escapeHtml(str_replace('--', '-', $script->getName())) . ' -->'
                . "\n" . $this->blocker->block($script->getContent(), $script->getCategory()) . "\n";
        }
        return $html;
    }
}
