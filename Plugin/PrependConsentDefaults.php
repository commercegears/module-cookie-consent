<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Plugin;

use Magento\Framework\View\Page\Config\Renderer;
use Psr\Log\LoggerInterface;
use CommerceGears\CookieConsent\Model\HeadScript;

/**
 * Puts the consent defaults directly after <meta>/<title>, before any CSS, JS or tag manager snippet.
 */
class PrependConsentDefaults
{
    public function __construct(
        private readonly HeadScript $headScript,
        private readonly LoggerInterface $logger
    ) {
    }

    public function afterRenderHeadContent(Renderer $subject, string $result): string
    {
        try {
            return $result . $this->headScript->render();
        } catch (\Throwable $e) {
            $this->logger->error('CommerceGears_CookieConsent: head script failed: ' . $e->getMessage());
            return $result;
        }
    }
}
