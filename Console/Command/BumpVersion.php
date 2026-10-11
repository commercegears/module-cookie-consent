<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Console\Command;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\PageCache\Model\Cache\Type as PageCacheType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Increases the consent version everywhere it is set, so every visitor is asked again.
 */
class BumpVersion extends Command
{
    private const PATH = 'commercegears_cookieconsent/general/consent_version';

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly WriterInterface $configWriter,
        private readonly TypeListInterface $cacheTypeList,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('commercegears:cookie-consent:bump-version')
            ->setDescription('Increase the cookie consent version so every visitor is asked again');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName('core_config_data'), ['scope', 'scope_id', 'value'])
                ->where('path = ?', self::PATH)
        );
        $hasDefault = false;
        foreach ($rows as $row) {
            $hasDefault = $hasDefault || $row['scope'] === ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        }
        if (!$hasDefault) {
            $rows[] = ['scope' => ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 'scope_id' => 0, 'value' => 1];
        }
        foreach ($rows as $row) {
            $new = max(1, (int)$row['value']) + 1;
            $this->configWriter->save(self::PATH, (string)$new, $row['scope'], (int)$row['scope_id']);
            $output->writeln(sprintf('<info>%s %d: consent version %d -> %d</info>', $row['scope'], $row['scope_id'], (int)$row['value'], $new));
        }
        $this->cacheTypeList->cleanType('config');
        $this->cacheTypeList->cleanType(PageCacheType::TYPE_IDENTIFIER);
        $output->writeln('Configuration and full page cache cleaned.');
        return Command::SUCCESS;
    }
}
