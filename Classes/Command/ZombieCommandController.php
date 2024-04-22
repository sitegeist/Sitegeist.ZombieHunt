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
    protected string $zombieLabel;
    protected string $zombieToDestroyLabel;

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

    /**
     * @param array{zombieLabel: string, zombieToDestroyLabel: string} $settings
     */
    public function injectSettings(array $settings): void
    {
        $this->zombieLabel = $settings['zombieLabel'];
        $this->zombieToDestroyLabel = $settings['zombieToDestroyLabel'];
    }

    /**
     * Detect zombies in the given site. Will return an error code if zombie contents that is due to destruction is detected.
     *
     * @param string|null $siteNode node-name of the site to scan, if not defined all sites are used
     * @param string|null $dimensionValues json of the dimension values to use, otherwise default. Example '{"language":["de"]}'
     */
    public function detectCommand(?string $siteNode = null, ?string $dimensionValues = null): void
    {
        /**
         * @var Site[] $sites
         */
        if ($siteNode === null) {
            $sites = $this->siteRepository->findAll();
        } else {
            $sites = [$this->siteRepository->findOneByNodeName($siteNode)];
        }

        $feedbackLines = [];
        $zombieCountAcrossAllSites = 0;
        $zombiesDueToDestructionCountAcrossAllSites = 0;

        foreach ($sites as $item) {
            $this->outputLine();
            $this->outputLine(sprintf('Looking for zombie nodes in site <info>%s</info> (%s)', $item->getName(), $item->getNodeName()));
            $this->outputLine();

            $rootNode = $this->rootNodeDetector->findRootNode(
                $item->getNodeName(),
                $dimensionValues ? json_decode($dimensionValues, true, JSON_THROW_ON_ERROR) : []
            );
            $zombieCount = 0;
            $zombiesDueToDestructionCount = 0;

            foreach ($this->traverseSubtreeAndYieldZombieNodes($rootNode) as $zombieNode) {
                $path = $this->renderNodePath($rootNode, $zombieNode);
                if ($this->zombieDetector->isZombieThatHasBeDestroyed($zombieNode)) {
                    $this->outputLine(sprintf('- %s <info>%s (%s)</info> %s', $this->zombieToDestroyLabel, $zombieNode->getLabel(), $zombieNode->getNodeType()->getLabel(), $path));
                    $zombiesDueToDestructionCount++;
                } else {
                    $this->outputLine(sprintf('- %s <info>%s (%s)</info> %s', $this->zombieLabel, $zombieNode->getLabel(), $zombieNode->getNodeType()->getLabel(), $path));
                }
                $zombieCount++;
            }

            $feedbackLines[] = sprintf('<info>%s</info> zombie nodes were detected in site <info>%s</info> (%s) detected. <info>%s</info> are due to destruction', $zombieCount, $item->getName(), $item->getNodeName(), $zombiesDueToDestructionCount);

            $zombieCountAcrossAllSites += $zombieCount;
            $zombiesDueToDestructionCountAcrossAllSites += $zombiesDueToDestructionCount;
        }

        $this->outputLine();
        $this->output(implode(PHP_EOL, $feedbackLines) . PHP_EOL);
        $this->outputLine();

        if (count($sites) > 1) {
            $this->outputLine(sprintf('Across all sites <info>%s</info> zombie nodes were detected of which <info>%s</info> are due to destruction', $zombieCountAcrossAllSites, $zombiesDueToDestructionCountAcrossAllSites));
        }

        if ($zombiesDueToDestructionCountAcrossAllSites > 0) {
            $this->quit(1);
        }
    }

    /**
     * Remove zombie contents that are due to destruction
     *
     * @param string|null $siteNode node-name of the site to scan, if not defined all sites are used
     * @param string|null $dimensionValues json of the dimension values to use, otherwise default. Example '{"language":["de"]}'
     */
    public function destroyCommand(?string $siteNode = null, ?string $dimensionValues = null): void
    {
        /**
         * @var Site[] $sites
         */
        if ($siteNode === null) {
            $sites = $this->siteRepository->findAll();
        } else {
            $sites = [$this->siteRepository->findOneByNodeName($siteNode)];
        }

        $feedbackLines = [];
        $zombieCountAcrossAllSites = 0;
        $removedZombieCountAcrossAllSites = 0;

        foreach ($sites as $item) {
            $this->outputLine();
            $this->outputLine(sprintf('Destroying zombie nodes in site <info>%s</info> (%s)', $item->getName(), $item->getNodeName()));
            $this->outputLine();

            $rootNode = $this->rootNodeDetector->findRootNode(
                $item->getNodeName(),
                $dimensionValues ? json_decode($dimensionValues, true, JSON_THROW_ON_ERROR) : []
            );
            $zombieCount = 0;
            $removedZombieCount = 0;

            foreach ($this->traverseSubtreeAndYieldZombieNodes($rootNode) as $zombieNode) {
                $path = $this->renderNodePath($rootNode, $zombieNode);
                if ($this->zombieDetector->isZombieThatHasBeDestroyed($zombieNode)) {
                    $this->outputLine(sprintf('- %s <info>%s (%s)</info> %s', $this->zombieToDestroyLabel, $zombieNode->getLabel(), $zombieNode->getNodeType()->getLabel(), $path));
                    $zombieNode->remove();
                    $removedZombieCount++;
                }
                $zombieCount++;
            }

            $feedbackLines[] = sprintf('<info>%s</info> zombie nodes of <info>%s</info> were removed in site <info>%s</info> (%s).', $removedZombieCount, $zombieCount, $item->getName(), $item->getNodeName());

            $zombieCountAcrossAllSites += $zombieCount;
            $removedZombieCountAcrossAllSites += $removedZombieCount;
        }

        $this->outputLine();
        $this->output(implode(PHP_EOL, $feedbackLines) . PHP_EOL);
        $this->outputLine();

        if (count($sites) > 1) {
            $this->outputLine(sprintf('Across all sites <info>%s</info> zombie nodes of <info>%s</info> were removed', $removedZombieCountAcrossAllSites, $zombieCountAcrossAllSites));
        }
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
                    yield from $this->traverseSubtreeAndYieldZombieNodes($childNode);
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
        return implode(' -> ', array_reverse($pathParts));
    }
}
