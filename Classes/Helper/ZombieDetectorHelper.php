<?php

declare(strict_types=1);

namespace Sitegeist\ZombieHunt\Helper;

use Sitegeist\ZombieHunt\Domain\ZombieDetector;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;

class ZombieDetectorHelper implements ProtectedContextAwareInterface
{
    protected ZombieDetector $zombieDetector;

    public function injectZombieDetector(ZombieDetector $zombieDetector)
    {
        $this->zombieDetector = $zombieDetector;
    }

    public function isZombie(NodeInterface $node): bool
    {
        return $this->zombieDetector->isZombie($node);
    }

    public function isZombieThatHasToBeDestructed(NodeInterface $node): bool
    {
        return $this->zombieDetector->isZombieThatHasBeDestructed($node);
    }

    public function allowsCallOfMethod($methodName)
    {
        return in_array($methodName, ['isZombie','isZombieThatHasToBeDestructed']);
    }
}
