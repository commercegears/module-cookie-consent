<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Softaware\CookieConsent\Api\Data\ScriptInterface;

interface ScriptRepositoryInterface
{
    /**
     * @param int $scriptId
     * @return ScriptInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $scriptId): ScriptInterface;

    /**
     * @param ScriptInterface $script
     * @return ScriptInterface
     * @throws CouldNotSaveException
     */
    public function save(ScriptInterface $script): ScriptInterface;

    /**
     * @param ScriptInterface $script
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ScriptInterface $script): bool;

    /**
     * Active scripts for a store view, sorted.
     *
     * @param int $storeId
     * @param string|null $position
     * @return ScriptInterface[]
     */
    public function getActiveForStore(int $storeId, ?string $position = null): array;
}
