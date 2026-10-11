<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class StoreViews extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        $names = [];
        foreach ($this->storeManager->getStores() as $store) {
            $names[(int)$store->getId()] = $store->getName();
        }
        $field = $this->getData('name');
        foreach ($dataSource['data']['items'] ?? [] as $i => $item) {
            $ids = array_map('intval', explode(',', (string)($item[$field] ?? '0')));
            $labels = in_array(0, $ids, true)
                ? [(string)__('All Store Views')]
                : array_map(static fn (int $id): string => $names[$id] ?? ('#' . $id), $ids);
            $dataSource['data']['items'][$i][$field] = implode(', ', $labels);
        }
        return $dataSource;
    }
}
