<?php

declare(strict_types=1);

namespace Sitegeist\ZombieHunt\Domain;

use Neos\ContentRepository\Domain\Model\NodeInterface;

class ZombieDetector
{
    protected int $zombificationPeriod;
    protected int $destructionPeriod;


    /**
     * @param array{zombificationPeriod: int, destructionPeriod:int} $settings
     */
    public function injectSettings(array $settings): void
    {
        $this->zombificationPeriod = $settings['zombificationPeriod'];
        $this->destructionPeriod = $settings['destructionPeriod'];
    }

    public function isZombie(NodeInterface $node): bool
    {
        if ($node->isVisible()) {
            return false;
        }

        $latestAllowedTimestamp = time() - $this->zombificationPeriod;

        /** @var \DateTime|null $lastModificationDateTime */
        $lastModificationDateTime = $node->getNodeData()->getLastModificationDateTime();
        $lastModificationTimestamp = $lastModificationDateTime?->getTimestamp();

        /** @var \DateTime|null $lastPublicationDateTime */
        $lastPublicationDateTime = $node->getNodeData()->getLastPublicationDateTime();
        $lastPublicationTimestamp = $lastPublicationDateTime?->getTimestamp();

        if (
            ($lastModificationTimestamp === null || $lastModificationTimestamp < $latestAllowedTimestamp)
            && ($lastPublicationTimestamp === null || $lastPublicationTimestamp < $latestAllowedTimestamp)
        ) {
            return true;
        }

        return false;
    }

    public function isZombieThatHasToBeDestroyed(NodeInterface $node): bool
    {
        if ($node->isVisible()) {
            return false;
        }

        $latestAllowedTimestamp = time() - $this->zombificationPeriod - $this->destructionPeriod;

        /** @var \DateTime|null $lastModificationDateTime */
        $lastModificationDateTime = $node->getNodeData()->getLastModificationDateTime();
        $lastModificationTimestamp = $lastModificationDateTime?->getTimestamp();

        /** @var \DateTime|null $lastPublicationDateTime */
        $lastPublicationDateTime = $node->getNodeData()->getLastPublicationDateTime();
        $lastPublicationTimestamp = $lastPublicationDateTime?->getTimestamp();

        if (
            $node->isVisible() === false
            && ($lastModificationTimestamp === null || $lastModificationTimestamp < $latestAllowedTimestamp)
            && ($lastPublicationTimestamp === null || $lastPublicationTimestamp < $latestAllowedTimestamp)
        ) {
            return true;
        }

        return false;
    }
}
