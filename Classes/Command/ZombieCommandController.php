<?php
declare(strict_types=1);

namespace Sitegeist\ZombieHunt\Command;

use Neos\Flow\Cli\CommandController;
use Neos\Neos\Controller\CreateContentContextTrait;
use Sitegeist\ZombieHunt\Domain\RootNodeDetector;
use Sitegeist\ZombieHunt\Domain\ZombieDetector;
use Sitegeist\ZombieHunt\Traits\DetectZombieNodeTrait;

class ZombieCommandController extends CommandController
{
    use CreateContentContextTrait;

    protected ZombieDetector $zombieDetector;
    protected RootNodeDetector $rootNodeDetector;

    public function injectZombieDetector(ZombieDetector $zombieDetector) {
        $this->zombieDetector = $zombieDetector;
    }

    public function injectRootNodeDetector(RootNodeDetector $rootNodeDetector) {
        $this->rootNodeDetector = $rootNodeDetector;
    }

    public function detectZombies(string $site = null, string $dimensions = null): void
    {
        $rootNode = $this->rootNodeDetector->findRootNode($site);
    }

    public function destroyZombies(string $site = null, string $dimensions = null): void
    {
    }

    /**
     * @return JsonlRecord[]
     */
    private function traverseSubtree(NodeInterface $node): array
    {
        $documents = [];
        if (!$documentNode->getNodeType()->isOfType('Neos.Neos:Shortcut')) {
            $documents[] = $this->transformDocument($documentNode);
        }
        foreach ($documentNode->getChildNodes('Neos.Neos:Document') as $childDocument) {
            $documents = array_merge($documents, $this->traverseSubtree($childDocument));
        }

        return $documents;
    }
}
