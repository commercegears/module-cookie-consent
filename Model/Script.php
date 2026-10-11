<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use CommerceGears\CookieConsent\Api\Data\ScriptInterface;

class Script extends AbstractModel implements ScriptInterface, IdentityInterface
{
    public const CACHE_TAG = 'sw_cc_script';

    /** @var string */
    protected $_eventPrefix = 'commercegears_cookieconsent_script';

    /** @var string */
    protected $_cacheTag = self::CACHE_TAG;

    protected function _construct(): void
    {
        $this->_init(ResourceModel\Script::class);
    }

    /**
     * @return string[]
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG];
    }

    public function getScriptId(): ?int
    {
        $id = $this->getData(self::SCRIPT_ID);
        return $id === null ? null : (int)$id;
    }

    public function getName(): string
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName(string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    public function isActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $active): self
    {
        return $this->setData(self::IS_ACTIVE, (int)$active);
    }

    public function getCategory(): string
    {
        return (string)$this->getData(self::CATEGORY);
    }

    public function setCategory(string $category): self
    {
        return $this->setData(self::CATEGORY, $category);
    }

    public function getPosition(): string
    {
        return $this->getData(self::POSITION) === self::POSITION_HEAD ? self::POSITION_HEAD : self::POSITION_BODY_END;
    }

    public function setPosition(string $position): self
    {
        return $this->setData(self::POSITION, $position);
    }

    public function getStoreIds(): array
    {
        $value = $this->getData(self::STORE_IDS);
        if (is_array($value)) {
            return array_map('intval', $value);
        }
        return array_map('intval', array_filter(explode(',', (string)$value), 'strlen'));
    }

    public function setStoreIds(array $storeIds): self
    {
        return $this->setData(self::STORE_IDS, implode(',', array_unique(array_map('intval', $storeIds))));
    }

    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    public function setSortOrder(int $sortOrder): self
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getContent(): string
    {
        return (string)$this->getData(self::CONTENT);
    }

    public function setContent(string $content): self
    {
        return $this->setData(self::CONTENT, $content);
    }
}
