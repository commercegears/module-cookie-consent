<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ResourceConnection;

/**
 * One-sentence intro above an admin grid, an optional notice when a feature is switched off, and an empty state
 * (explanation + primary action) shown instead of the grid while its table has no rows.
 *
 * Layout arguments: intro, table, listing (layout name of the uiComponent), empty_title, empty_text, button_label,
 * button_route, off_config_path, off_text, settings_section.
 */
class GridIntro extends Template
{
    /** @var string */
    protected $_template = 'Softaware_CookieConsent::grid-intro.phtml';

    private ?bool $empty = null;

    public function __construct(
        Context $context,
        private readonly ResourceConnection $resource,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ((string)$this->getData('listing') !== '' && $this->isEmpty()) {
            // The grid stays in the page (filters, bookmarks) but is hidden by CSS behind the empty state.
            $this->pageConfig->addBodyClass('sw-grid-empty');
        }
        return $this;
    }

    public function isEmpty(): bool
    {
        if ($this->empty === null) {
            $table = (string)$this->getData('table');
            if ($table === '') {
                return $this->empty = false;
            }
            $connection = $this->resource->getConnection();
            $select = $connection->select()
                ->from($this->resource->getTableName($table), [new \Zend_Db_Expr('1')])
                ->limit(1);
            $this->empty = $connection->fetchOne($select) === false;
        }
        return $this->empty;
    }

    public function isSwitchedOff(): bool
    {
        $path = (string)$this->getData('off_config_path');
        return $path !== '' && !$this->_scopeConfig->isSetFlag($path);
    }

    public function getButtonUrl(): string
    {
        $route = (string)$this->getData('button_route');
        return $route === '' ? '' : $this->getUrl($route);
    }

    public function getSettingsUrl(): string
    {
        return $this->getUrl('adminhtml/system_config/edit', ['section' => (string)$this->getData('settings_section')]);
    }
}
