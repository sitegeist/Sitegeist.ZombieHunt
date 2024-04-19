<?php
declare(strict_types=1);

namespace Sitegeist\ZombieHunt\Aspect;

use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Sitegeist\ZombieHunt\Domain\ZombieDetector;

#[Flow\Aspect]
#[Flow\Scope("singleton")]
class LabelForNodeAspect
{
    protected ZombieDetector $zombieDetector;

    public function injectZombieDetector(ZombieDetector $zombieDetector) {
        $this->zombieDetector = $zombieDetector;
    }

    #[Flow\Around("method(Neos\ContentRepository\Domain\Model\Node->getLabel())")]
    public function markZombieNodes(JoinPointInterface $joinPoint): string
    {
        $node = $joinPoint->getProxy();
        $label = $joinPoint->getAdviceChain()->proceed($joinPoint);

        if ($node instanceof NodeInterface && $node->getContext()->isInBackend() && $node->getContext()->getCurrentRenderingMode()->isEdit()) {
            if ($this->zombieDetector->isZombie($node)) {
                if ($this->zombieDetector->isZombieThatHasBeDestructed($node)) {
                    $label = '🔥🧟🔥 ' . $label;
                } else {
                    $label = '🧟 ' . $label;
                }
            }
        }

        return $label;
    }
}
