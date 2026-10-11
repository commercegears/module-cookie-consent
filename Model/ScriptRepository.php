<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use CommerceGears\CookieConsent\Api\Data\ScriptInterface;
use CommerceGears\CookieConsent\Api\Data\ScriptInterfaceFactory;
use CommerceGears\CookieConsent\Api\ScriptRepositoryInterface;
use CommerceGears\CookieConsent\Model\ResourceModel\Script as ScriptResource;
use CommerceGears\CookieConsent\Model\ResourceModel\Script\CollectionFactory;

class ScriptRepository implements ScriptRepositoryInterface
{
    private const CACHE_KEY = 'sw_cc_scripts_';

    /** @var array<int, ScriptInterface[]> */
    private array $activeByStore = [];

    public function __construct(
        private readonly ScriptResource $resource,
        private readonly ScriptInterfaceFactory $scriptFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly CacheInterface $cache,
        private readonly Json $json
    ) {
    }

    public function getById(int $scriptId): ScriptInterface
    {
        /** @var Script $script */
        $script = $this->scriptFactory->create();
        $this->resource->load($script, $scriptId);
        if (!$script->getScriptId()) {
            throw new NoSuchEntityException(__('The script with id "%1" does not exist.', $scriptId));
        }
        return $script;
    }

    public function save(ScriptInterface $script): ScriptInterface
    {
        try {
            /** @var Script $script */
            $this->resource->save($script);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the script: %1', $e->getMessage()), $e);
        }
        $this->cache->clean([Script::CACHE_TAG]);
        $this->activeByStore = [];
        return $script;
    }

    public function delete(ScriptInterface $script): bool
    {
        try {
            /** @var Script $script */
            $this->resource->delete($script);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the script: %1', $e->getMessage()), $e);
        }
        $this->cache->clean([Script::CACHE_TAG]);
        $this->activeByStore = [];
        return true;
    }

    public function getActiveForStore(int $storeId, ?string $position = null): array
    {
        if (!isset($this->activeByStore[$storeId])) {
            $this->activeByStore[$storeId] = $this->loadActive($storeId);
        }
        if ($position === null) {
            return $this->activeByStore[$storeId];
        }
        return array_values(array_filter(
            $this->activeByStore[$storeId],
            static fn (ScriptInterface $script): bool => $script->getPosition() === $position
        ));
    }

    /**
     * @return ScriptInterface[]
     */
    private function loadActive(int $storeId): array
    {
        $cacheKey = self::CACHE_KEY . $storeId;
        $cached = $this->cache->load($cacheKey);
        if ($cached !== false && $cached !== null) {
            $rows = $this->json->unserialize((string)$cached);
        } else {
            $collection = $this->collectionFactory->create()
                ->addFieldToFilter(ScriptInterface::IS_ACTIVE, 1)
                ->setOrder(ScriptInterface::SORT_ORDER, 'ASC')
                ->setOrder(ScriptInterface::SCRIPT_ID, 'ASC');
            $rows = [];
            foreach ($collection as $item) {
                $storeIds = $item->getStoreIds();
                if (in_array(0, $storeIds, true) || in_array($storeId, $storeIds, true)) {
                    $rows[] = $item->getData();
                }
            }
            $this->cache->save($this->json->serialize($rows), $cacheKey, [Script::CACHE_TAG]);
        }
        $scripts = [];
        foreach ($rows as $row) {
            /** @var Script $script */
            $script = $this->scriptFactory->create();
            $script->setData($row);
            $scripts[] = $script;
        }
        return $scripts;
    }
}
