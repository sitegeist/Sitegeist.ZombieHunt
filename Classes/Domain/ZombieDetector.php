<?php

declare(strict_types=1);

namespace Sitegeist\ZombieHunt\Domain;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Neos\Domain\SubtreeTagging\NeosSubtreeTag;

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

    public function isZombie(Node $node): bool
    {
        if ($node->tags->withoutInherited()->contain(NeosSubtreeTag::disabled()) === false) {
            return false;
        }

        $latestAllowedTimestamp = time() - $this->zombificationPeriod;
        $lastPublicationTimestamp = $node->timestamps->lastModified?->getTimestamp();
        $creationTimestamp = $node->timestamps->created->getTimestamp();

        if (
            ($lastPublicationTimestamp !== null && $lastPublicationTimestamp < $latestAllowedTimestamp)
            || ($lastPublicationTimestamp === null && $creationTimestamp < $latestAllowedTimestamp)
        ) {
            return true;
        }

        return false;
    }

    public function isZombieThatHasToBeDestroyed(Node $node): bool
    {
        if ($node->tags->withoutInherited()->contain(NeosSubtreeTag::disabled()) === false) {
            return false;
        }

        $latestAllowedTimestamp = time() - $this->zombificationPeriod - $this->destructionPeriod;
        $lastPublicationTimestamp = $node->timestamps->lastModified?->getTimestamp();
        $creationTimestamp = $node->timestamps->created->getTimestamp();

        if (
            ($lastPublicationTimestamp !== null && $lastPublicationTimestamp < $latestAllowedTimestamp)
            || ($lastPublicationTimestamp === null && $creationTimestamp < $latestAllowedTimestamp)
        ) {
            return true;
        }

        return false;
    }
}
