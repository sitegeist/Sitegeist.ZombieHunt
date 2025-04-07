<?php

declare(strict_types=1);

namespace Sitegeist\ZombieHunt\Helper;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Sitegeist\ZombieHunt\Domain\ZombieDetector;
use Neos\Eel\ProtectedContextAwareInterface;

class ZombieDetectorHelper implements ProtectedContextAwareInterface
{
    protected ZombieDetector $zombieDetector;

    public function injectZombieDetector(ZombieDetector $zombieDetector): void
    {
        $this->zombieDetector = $zombieDetector;
    }

    public function isZombie(Node $node): bool
    {
        return $this->zombieDetector->isZombie($node);
    }

    public function isZombieThatHasToBeDestructed(Node $node): bool
    {
        return $this->zombieDetector->isZombieThatHasToBeDestroyed($node);
    }

    public function allowsCallOfMethod($methodName)
    {
        return in_array($methodName, ['isZombie','isZombieThatHasToBeDestructed']);
    }
}
