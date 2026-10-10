<?php
/**
 * Copyright © Softaware Commerce. All rights reserved.
 */
declare(strict_types=1);

namespace Softaware\CookieConsent\Model\Csp;

use Magento\Csp\Api\PolicyCollectorInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;
use Softaware\CookieConsent\Api\ScriptRepositoryInterface;
use Softaware\CookieConsent\Model\Config;
use Softaware\CookieConsent\Model\ScriptBlocker;

/**
 * Adds the remote hosts of the active Third-party Scripts of the current store view to the storefront CSP
 * (frontend area only).
 *
 * The scripts block also registers them with Magento's dynamic collector, but that only works while the page is
 * rendered: a page served from the built-in full-page cache gets a freshly built CSP header without them, so a
 * script loaded after consent would be reported (report-only) or refused (restrict mode) there.
 * The active scripts come from the repository's cache, so this costs one cache read per request.
 */
class ScriptHostCollector implements PolicyCollectorInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly ScriptRepositoryInterface $scriptRepository,
        private readonly ScriptBlocker $blocker,
        private readonly StoreManagerInterface $storeManager,
        private readonly State $appState
    ) {
    }

    /**
     * @param \Magento\Csp\Api\Data\PolicyInterface[] $defaultPolicies
     * @return \Magento\Csp\Api\Data\PolicyInterface[]
     */
    public function collect(array $defaultPolicies = []): array
    {
        try {
            if ($this->appState->getAreaCode() !== Area::AREA_FRONTEND || !$this->config->isEnabled()) {
                return $defaultPolicies;
            }
            $hosts = [];
            $storeId = (int)$this->storeManager->getStore()->getId();
            foreach ($this->scriptRepository->getActiveForStore($storeId) as $script) {
                foreach ($this->blocker->hostsFor($script->getContent(), $script->getCategory()) as $directive => $list) {
                    foreach ($list as $host) {
                        $hosts[$directive][$host] = true;
                    }
                }
            }
        } catch (\Throwable) {
            // Never break the response because of the CSP header.
            return $defaultPolicies;
        }
        foreach ($hosts as $directive => $list) {
            $defaultPolicies[] = new FetchPolicy((string)$directive, false, array_keys($list));
        }
        return $defaultPolicies;
    }
}
