<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Active / Inactive as a Magento grid severity badge.
 */
class StatusBadge extends Column
{
    /**
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        $field = $this->getData('name');
        foreach ($dataSource['data']['items'] ?? [] as $i => $item) {
            $active = (int)($item[$field] ?? 0) === 1;
            $dataSource['data']['items'][$i][$field] = sprintf(
                '<span class="%s"><span>%s</span></span>',
                $active ? 'grid-severity-notice' : 'grid-severity-minor',
                $this->escapeLabel($active ? (string)__('Active') : (string)__('Inactive'))
            );
        }
        return $dataSource;
    }

    private function escapeLabel(string $label): string
    {
        return htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
