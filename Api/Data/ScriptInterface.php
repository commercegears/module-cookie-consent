<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Api\Data;

/**
 * A third-party script/snippet that only runs after the visitor accepts its cookie category.
 */
interface ScriptInterface
{
    public const SCRIPT_ID = 'script_id';
    public const NAME = 'name';
    public const IS_ACTIVE = 'is_active';
    public const CATEGORY = 'category';
    public const POSITION = 'position';
    public const STORE_IDS = 'store_ids';
    public const SORT_ORDER = 'sort_order';
    public const CONTENT = 'content';

    public const POSITION_HEAD = 'head';
    public const POSITION_BODY_END = 'body_end';

    /**
     * @return int|null
     */
    public function getScriptId(): ?int;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * @return bool
     */
    public function isActive(): bool;

    /**
     * @param bool $active
     * @return $this
     */
    public function setIsActive(bool $active): self;

    /**
     * @return string
     */
    public function getCategory(): string;

    /**
     * @param string $category
     * @return $this
     */
    public function setCategory(string $category): self;

    /**
     * @return string
     */
    public function getPosition(): string;

    /**
     * @param string $position
     * @return $this
     */
    public function setPosition(string $position): self;

    /**
     * @return int[]
     */
    public function getStoreIds(): array;

    /**
     * @param int[] $storeIds
     * @return $this
     */
    public function setStoreIds(array $storeIds): self;

    /**
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): self;

    /**
     * @return string
     */
    public function getContent(): string;

    /**
     * @param string $content
     * @return $this
     */
    public function setContent(string $content): self;
}
