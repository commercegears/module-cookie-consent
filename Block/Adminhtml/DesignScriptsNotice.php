<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Softaware\CookieConsent\Model\DesignScriptDetector;

/**
 * Warning about scripts in Content > Design > Configuration that load without consent
 * (shown above the Third-party Scripts grid and in the module settings).
 */
class DesignScriptsNotice extends Template
{
    public function __construct(
        Context $context,
        private readonly DesignScriptDetector $detector,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _toHtml(): string
    {
        try {
            $rows = $this->detector->detect();
        } catch (\Throwable $e) {
            $this->_logger->error('Softaware_CookieConsent: design script detection failed: ' . $e->getMessage());
            return '';
        }
        if ($rows === []) {
            return (bool)$this->getData('show_ok')
                ? '<div class="message message-success success"><div>'
                    . $this->escapeHtml(__('No scripts found in Content > Design > Configuration that load without consent.'))
                    . '</div></div>'
                : '';
        }
        $html = '<div class="message message-warning warning sw-cc-design-scripts"><div>'
            . '<strong>' . $this->escapeHtml(__('These scripts in Content > Design > Configuration load without cookie consent:')) . '</strong>'
            . '<ul style="margin:.5em 0 .5em 1.5em;list-style:disc">';
        foreach ($rows as $row) {
            $html .= '<li>' . $this->escapeHtml($row['source']) . ' (' . $this->escapeHtml(implode(', ', $row['stores'])) . '): '
                . $this->escapeHtml(implode('; ', $row['items'])) . '</li>';
        }
        $settingsUrl = $this->getUrl('adminhtml/system_config/edit', ['section' => 'softaware_cookieconsent']);
        $html .= '</ul>' . $this->escapeHtml(__(
            'Either set "Scripts in Design Configuration" to a cookie category in the Cookie Consent settings (General), '
            . 'move each snippet to Third-party Scripts, or add data-sw-consent="necessary" to a tag that really is necessary.'
        ));
        if (!$this->getData('hide_settings_link')) {
            $html .= ' <a href="' . $this->escapeUrl($settingsUrl) . '">' . $this->escapeHtml(__('Open settings')) . '</a>';
        }
        return $html . '</div></div>';
    }
}
