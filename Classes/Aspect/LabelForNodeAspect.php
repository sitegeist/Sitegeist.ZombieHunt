<?php

declare(strict_types=1);

namespace Sitegeist\ZombieHunt\Aspect;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Neos\Domain\Service\ContentContext;
use Sitegeist\ZombieHunt\Domain\ZombieDetector;

#[Flow\Aspect]
#[Flow\Scope("singleton")]
class LabelForNodeAspect
{
    protected ZombieDetector $zombieDetector;

    protected string $zombieLabel;
    protected string $zombieToDestroyLabel;

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

    #[Flow\Around("method(Neos\ContentRepository\Domain\Model\Node->getLabel())")]
    public function markZombieNodes(JoinPointInterface $joinPoint): string
    {
        $node = $joinPoint->getProxy();
        $label = $joinPoint->getAdviceChain()->proceed($joinPoint);

        if (
            $node instanceof NodeInterface
            && $node->getContext() instanceof ContentContext
            && $node->getContext()->isInBackend() && $node->getContext()->getCurrentRenderingMode()->isEdit()
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
