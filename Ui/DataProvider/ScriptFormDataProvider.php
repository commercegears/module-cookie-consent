<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use CommerceGears\CookieConsent\Model\ResourceModel\Script\CollectionFactory;
use CommerceGears\CookieConsent\Model\Script;

class ScriptFormDataProvider extends AbstractDataProvider
{
    /** @var array<int|string, array<string, mixed>>|null */
    private ?array $loadedData = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }
        $this->loadedData = [];
        /** @var Script $script */
        foreach ($this->collection->getItems() as $script) {
            $data = $script->getData();
            $data['store_ids'] = array_map('strval', $script->getStoreIds());
            $this->loadedData[$script->getScriptId()] = $data;
        }
        if (!$this->loadedData) {
            $this->loadedData[''] = [
                'is_active' => '1',
                'category' => 'analytics',
                'position' => 'body_end',
                'store_ids' => ['0'],
                'sort_order' => '0',
            ];
        }
        return $this->loadedData;
    }
}
