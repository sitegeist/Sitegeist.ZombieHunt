<?php

declare(strict_types=1);

namespace Sitegeist\ZombieHunt\Domain;

use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

class RootNodeDetector
{

    private function __construct(
        private ContextFactory $contentContextFactory,
        private ContentDimensionPresetSourceInterface $contentDimensionPresetSource
    ) {
    }

    public function findRootNode(string $siteNodeName, array $dimensionValues): ?Node
    {
        $contextDimensions = [];
        foreach ($this->contentDimensionPresetSource->getAllPresets() as $dimensionId => $presetConfig) {
            $contextDimensions[$dimensionId] = $presetConfig['presets'][$dimensionValues[$dimensionId]]['values'];
        }
        $context = $this->contentContextFactory->create([
            'dimensions' => $contextDimensions,
            'targetDimensions' => $dimensionValues,
        ]);

        $rootNode = $context->getNode('/sites/' . $siteNodeName);

        if (!$rootNode instanceof Node) {
            throw new \DomainException(sprintf('Could not find site node with name %s and dimensionValues %s', $siteNodeName, json_encode($dimensionValues)), 1705939597);
        }

        return $rootNode;
    }
}
