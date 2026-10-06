<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Block\Adminhtml\Script\Edit;

use Magento\Backend\Block\Widget\Context;

abstract class GenericButton
{
    public function __construct(protected readonly Context $context)
    {
    }

    protected function getScriptId(): int
    {
        return (int)$this->context->getRequest()->getParam('script_id');
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
