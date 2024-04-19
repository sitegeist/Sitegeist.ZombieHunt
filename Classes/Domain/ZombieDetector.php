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

        $latestAllowedTime = time() - $this->zombificationPeriod;
        $lastModification = $node->getNodeData()->getLastModificationDateTime()?->getTimestamp();
        $lastPublication = $node->getNodeData()->getLastPublicationDateTime()?->getTimestamp();

        if (
            ($lastModification === null || $lastModification < $latestAllowedTime)
            && ($lastPublication === null || $lastPublication < $latestAllowedTime)
        ) {
            return true;
        }

        return false;
    }

    public function isZombieThatHasBeDestroyed(NodeInterface $node): bool
    {
        if ($node->isVisible()) {
            return false;
        }

        $latestAllowedTime = time() - $this->zombificationPeriod - $this->destructionPeriod;
        $lastModification = $node->getNodeData()->getLastModificationDateTime()?->getTimestamp();
        $lastPublication = $node->getNodeData()->getLastPublicationDateTime()?->getTimestamp();

        if (
            $node->isVisible() === false
            && ($lastModification === null || $lastModification < $latestAllowedTime)
            && ($lastPublication === null || $lastPublication < $latestAllowedTime)
        ) {
            return true;
        }

        return false;
    }
}
