<?php

declare(strict_types=1);

namespace Sitegeist\ZombieHunt\Domain;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

class RootNodeDetector
{
    use CreateContentContextTrait;

    /**
     * @param array<string, array<string>> $dimensionValues
     */
    public function findRootNode(string $siteNodeName, array $dimensionValues = []): ?NodeInterface
    {
        $context = $this->createContentContext('live', $dimensionValues);
        $rootNode = $context->getNode('/sites/' . $siteNodeName);

        if (!$rootNode instanceof NodeInterface) {
            return null;
        }

        return $rootNode;
    }
}
