<?php

declare(strict_types=1);

namespace Sitegeist\ZombieHunt\Command;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Cli\CommandController;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Sitegeist\ZombieHunt\Domain\RootNodeDetector;
use Sitegeist\ZombieHunt\Domain\ZombieDetector;
use Sitegeist\ZombieHunt\Traits\DetectZombieNodeTrait;

class ZombieCommandController extends CommandController
{
    use CreateContentContextTrait;

    protected ZombieDetector $zombieDetector;
    protected RootNodeDetector $rootNodeDetector;
    protected SiteRepository $siteRepository;

    public function injectZombieDetector(ZombieDetector $zombieDetector)
    {
        $this->zombieDetector = $zombieDetector;
    }

    public function injectRootNodeDetector(RootNodeDetector $rootNodeDetector)
    {
        $this->rootNodeDetector = $rootNodeDetector;
    }

    public function injectSiteRepository(SiteRepository $siteRepository)
    {
        $this->siteRepository = $siteRepository;
    }

    public function detectCommand(?string $site = null, ?string $dimensions = null): void
    {
        if ($site === null) {
            /**
             * @var Site[] $sites
             */
            $sites = $this->siteRepository->findAll();
        } else {
            /**
             * @var Site[] $sites
             */
            $sites = [$this->siteRepository->findOneByNodeName($site)];
        }

        $feedbackLines = '';

        foreach ($sites as $item) {
            $this->outputLine();
            $this->outputLine(sprintf('Looking for zombie nodes in site %s (%s)', $item->getName(), $item->getNodeName()));
            $this->outputLine();

            $rootNode = $this->rootNodeDetector->findRootNode(
                $item->getNodeName(),
                $dimensions ? json_decode($dimensions, true, JSON_THROW_ON_ERROR) : []
            );
            $zombieCount = 0;
            $zombiesDueToDestructionCount = 0;

            foreach ($this->traverseSubtreeAndYieldZombieNodes($rootNode) as $zombieNode) {
                $path = $this->renderNodePath($rootNode, $zombieNode);
                if ($this->zombieDetector->isZombieThatHasBeDestructed($zombieNode)) {
                    $this->outputLine(sprintf('- 🔥🧟🔥 %s (%s)', $zombieNode->getLabel(), $path));
                    $zombiesDueToDestructionCount++;
                } else {
                    $this->outputLine(sprintf('- 🧟 %s (%s)', $zombieNode->getLabel(), $path));
                }
                $zombieCount++;
            }

            $feedbackLines .= PHP_EOL . sprintf('%s zombie nodes were detected in site %s (%s) detected. %s are due to destruction', $zombieCount, $item->getName(), $item->getNodeName(), $zombiesDueToDestructionCount);
        }

        $this->outputLine();
        $this->output($feedbackLines);
        $this->outputLine();
    }

    public function destroyCommand(?string $site = null, ?string $dimensions = null): void
    {
    }

    /**
     * @return \Generator<NodeInterface>
     */
    private function traverseSubtreeAndYieldZombieNodes(NodeInterface $node): \Generator
    {
        if ($node->hasChildNodes()) {
            foreach ($node->getChildNodes() as $childNode) {
                if ($this->zombieDetector->isZombie($childNode)) {
                    yield $childNode;
                } else {
                    foreach ($this->traverseSubtreeAndYieldZombieNodes($childNode) as $zombieNodes) {
                        yield $zombieNodes;
                    }
                }
            }
        }
    }

    /**
     * @param NodeInterface $rootNode
     * @param NodeInterface $zombieNode
     * @return string
     */
    protected function renderNodePath(NodeInterface $rootNode, NodeInterface $zombieNode): string
    {
        $pathParts = [];
        $parent = $zombieNode->getParent();
        while ($parent && $parent->getIdentifier() !== $rootNode->getIdentifier()) {
            $pathParts[] = $parent->getLabel();
            $parent = $parent->getParent();
        }
        $path = implode(' -> ', array_reverse($pathParts));
        return $path;
    }
}
