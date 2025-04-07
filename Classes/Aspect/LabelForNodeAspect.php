<?php

declare(strict_types=1);

namespace Sitegeist\ZombieHunt\Aspect;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Sitegeist\ZombieHunt\Domain\ZombieDetector;

#[Flow\Aspect]
#[Flow\Scope("singleton")]
class LabelForNodeAspect
{
    protected ZombieDetector $zombieDetector;
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    protected string $zombieLabel;
    protected string $zombieToDestroyLabel;

    public function injectContentRepositoryRegistry(ContentRepositoryRegistry $contentRepositoryRegistry): void
    {
        $this->contentRepositoryRegistry = $contentRepositoryRegistry;
    }

    public function injectZombieDetector(ZombieDetector $zombieDetector): void
    {
        $this->zombieDetector = $zombieDetector;
    }

    /**
     * @param array{zombieLabel: string, zombieToDestroyLabel: string} $settings
     */
    public function injectSettings(array $settings): void
    {
        $this->zombieLabel = $settings['zombieLabel'];
        $this->zombieToDestroyLabel = $settings['zombieToDestroyLabel'];
    }

    #[Flow\Around("method(Neos\Neos\Domain\NodeLabel\ExpressionBasedNodeLabelGenerator->getLabel())")]
    public function markZombieNodes(JoinPointInterface $joinPoint): string
    {
        /** @var Node $node */
        $node = $joinPoint->getMethodArgument('node');
        $label = $joinPoint->getAdviceChain()->proceed($joinPoint);
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);

        if (
            $node instanceof Node && !$subgraph->getWorkspaceName()->isLive()
        ) {
            if ($this->zombieDetector->isZombie($node)) {
                if ($this->zombieDetector->isZombieThatHasToBeDestroyed($node)) {
                    $label = $this->zombieToDestroyLabel . ' ' . $label;
                } else {
                    $label = $this->zombieLabel . ' ' . $label;
                }
            }
        }

        return $label;
    }
}
