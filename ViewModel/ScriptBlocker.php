<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use CommerceGears\CookieConsent\Model\ScriptBlocker as Blocker;

/**
 * Template helper for other modules and themes:
 *   <?= $viewModels->require(\CommerceGears\CookieConsent\ViewModel\ScriptBlocker::class)->block($html, 'marketing') ?>
 */
class ScriptBlocker implements ArgumentInterface
{
    public function __construct(private readonly Blocker $blocker)
    {
    }

    public function block(string $html, string $category): string
    {
        return $this->blocker->block($html, $category);
    }

    public function attributes(string $category): string
    {
        return $this->blocker->attributes($category);
    }

    public function isActive(): bool
    {
        return $this->blocker->isActive();
    }
}
