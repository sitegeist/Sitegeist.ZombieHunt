<?php

declare(strict_types=1);

namespace Sitegeist\ZombieHunt\Command;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Cli\CommandController;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;
use Neos\Neos\Domain\Repository\SiteRepository;
use Sitegeist\ZombieHunt\Domain\ZombieDetector;
use Sitegeist\ZombieHunt\Traits\DetectZombieNodeTrait;

class ZombieCommandController extends CommandController
{
    protected ZombieDetector $zombieDetector;
    protected SiteRepository $siteRepository;
    protected ContentRepositoryRegistry $contentRepositoryRegistry;
    protected NodeLabelGeneratorInterface $nodeLabelGenerator;


    protected string $zombieLabel;
    protected string $zombieToDestroyLabel;

    public function injectZombieDetector(ZombieDetector $zombieDetector): void
    {
        $this->zombieDetector = $zombieDetector;
    }

    public function injectSiteRepository(SiteRepository $siteRepository): void
    {
        $this->siteRepository = $siteRepository;
    }

    public function injectContentRepositoryRegistry(ContentRepositoryRegistry $contentRepositoryRegistry): void
    {
        $this->contentRepositoryRegistry = $contentRepositoryRegistry;
    }

    public function injectNodeLabelGenerator(NodeLabelGeneratorInterface $nodeLabelGenerator): void
    {
            $this->nodeLabelGenerator = $nodeLabelGenerator;
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
     * @param string|null $dimensionValues json of the dimension values to use, otherwise default. Example '{"language":"de"}'
     */
    public function detectCommand(?string $siteNode = null, ?string $dimensionValues = null): void
    {
        if ($siteNode === null) {
            /** @var Site[] $sites */
            $sites = $this->siteRepository->findAll();
        } else {
            /** @var Site[] $sites */
            $sites = [$this->siteRepository->findOneByNodeName($siteNode)];
        }

        $feedbackLines = [];
        $zombieCountAcrossAllSites = 0;
        $zombiesDueToDestructionCountAcrossAllSites = 0;

        foreach ($sites as $item) {
            $this->outputLine();
            $this->outputLine(sprintf('Looking for zombie nodes in site <info>%s</info> (%s)', $item->getName(), $item->getNodeName()));
            $this->outputLine();

            $contentRepository = $this->contentRepositoryRegistry->get($item->getConfiguration()->contentRepositoryId);
            $graph = $contentRepository->getContentGraph(WorkspaceName::forLive());
            $dimensionSpacePoint = $dimensionValues ? DimensionSpacePoint::fromArray(json_decode($dimensionValues, true, JSON_THROW_ON_ERROR)) : $item->getConfiguration()->defaultDimensionSpacePoint;
            $subgraph = $graph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::createEmpty());

            $rootNode = $subgraph->findNodeByAbsolutePath(AbsoluteNodePath::fromString('/<Neos.Neos:Sites>/' . $item->getNodeName()->value));
            if (!$rootNode instanceof Node) {
                continue;
            }

            $zombieCount = 0;
            $zombiesDueToDestructionCount = 0;

            foreach ($this->traverseSubtreeAndYieldZombieNodes($subgraph, $rootNode) as $zombieNode) {
                $path = $this->renderNodePath($subgraph, $zombieNode);

                if ($this->zombieDetector->isZombieThatHasToBeDestroyed($zombieNode)) {
                    $this->outputLine(sprintf('- %s %s<info>%s (%s)</info>', $this->zombieToDestroyLabel, $path, $this->nodeLabelGenerator->getLabel($zombieNode), $zombieNode->nodeTypeName->value));
                    $zombiesDueToDestructionCount++;
                } else {
                    $this->outputLine(sprintf('- %s %s<info>%s (%s)</info>', $this->zombieLabel, $path, $this->nodeLabelGenerator->getLabel($zombieNode), $zombieNode->nodeTypeName->value));
                }
                $zombieCount++;
            }

            $feedbackLines[] = sprintf('<info>%s</info> zombie nodes were detected in site <info>%s</info> (%s) detected. <info>%s</info> are due to destruction', $zombieCount, $item->getName(), $item->getNodeName()->value, $zombiesDueToDestructionCount);

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
     * @param bool|null $dryrun output list of nodes to be deleted without actually deleting them
     */
    public function destroyCommand(?string $siteNode = null, ?string $dimensionValues = null, ?bool $dryrun = false): void
    {
        if ($siteNode === null) {
            /** @var Site[] $sites */
            $sites = $this->siteRepository->findAll();
        } else {
            /** @var Site[] $sites */
            $sites = [$this->siteRepository->findOneByNodeName($siteNode)];
        }

        $feedbackLines = [];
        $zombieCountAcrossAllSites = 0;
        $removedZombieCountAcrossAllSites = 0;

        foreach ($sites as $item) {
            $this->outputLine();
            if ($dryrun) {
                $this->outputLine(sprintf('Zombie nodes in site <info>%s</info> (%s) that would be destroyed', $item->getName(), $item->getNodeName()));
            } else {
                $this->outputLine(sprintf('Destroying zombie nodes in site <info>%s</info> (%s)', $item->getName(), $item->getNodeName()));
            }
            $this->outputLine();

            $contentRepository = $this->contentRepositoryRegistry->get($item->getConfiguration()->contentRepositoryId);
            $graph = $contentRepository->getContentGraph(WorkspaceName::forLive());
            $dimensionSpacePoint = $dimensionValues ? DimensionSpacePoint::fromArray(json_decode($dimensionValues, true, JSON_THROW_ON_ERROR)) : $item->getConfiguration()->defaultDimensionSpacePoint;
            $subgraph = $graph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::createEmpty());

            $rootNode = $subgraph->findNodeByAbsolutePath(AbsoluteNodePath::fromString('/<Neos.Neos:Sites>/' . $item->getNodeName()->value));
            if (!$rootNode instanceof Node) {
                continue;
            }

            $zombieCount = 0;
            $removedZombieCount = 0;

            foreach ($this->traverseSubtreeAndYieldZombieNodes($subgraph, $rootNode, true) as $zombieNode) {
                $path = $this->renderNodePath($subgraph, $zombieNode);
                if ($this->zombieDetector->isZombieThatHasToBeDestroyed($zombieNode)) {
                    $this->outputLine(sprintf('- %s %s <info>%s (%s)</info>', $this->zombieToDestroyLabel, $path, $this->nodeLabelGenerator->getLabel($zombieNode), $zombieNode->nodeTypeName->value));
                    if (!$dryrun) {
                        $contentRepository->handle(
                            RemoveNodeAggregate::create(
                                $subgraph->getWorkspaceName(),
                                $zombieNode->aggregateId,
                                $dimensionSpacePoint,
                                NodeVariantSelectionStrategy::STRATEGY_ALL_SPECIALIZATIONS
                            )
                        );
                    }
                    $removedZombieCount++;
                }
                $zombieCount++;
            }

            if ($dryrun) {
                $feedbackLines[] = sprintf('<info>%s</info> zombie nodes of <info>%s</info> would be removed in site <info>%s</info> (%s).', $removedZombieCount, $zombieCount, $item->getName(), $item->getNodeName());
            } else {
                $feedbackLines[] = sprintf('<info>%s</info> zombie nodes of <info>%s</info> were removed in site <info>%s</info> (%s).', $removedZombieCount, $zombieCount, $item->getName(), $item->getNodeName());
            }

            $zombieCountAcrossAllSites += $zombieCount;
            $removedZombieCountAcrossAllSites += $removedZombieCount;
        }

        $this->outputLine();
        $this->output(implode(PHP_EOL, $feedbackLines) . PHP_EOL);
        $this->outputLine();

        if ($dryrun) {
            $this->outputLine(sprintf('Across all sites <info>%s</info> zombie nodes of <info>%s</info> would be removed', $removedZombieCountAcrossAllSites, $zombieCountAcrossAllSites));
        } else {
            $this->outputLine(sprintf('Across all sites <info>%s</info> zombie nodes of <info>%s</info> were removed', $removedZombieCountAcrossAllSites, $zombieCountAcrossAllSites));
        }
    }

    /**
     * @return \Generator<Node>
     */
    private function traverseSubtreeAndYieldZombieNodes(ContentSubgraphInterface $subgraph, Node $node, bool $onlyZombiesToDestroy = false): \Generator
    {
        $children = $subgraph->findChildNodes($node->aggregateId, FindChildNodesFilter::create());
        foreach ($children as $childNode) {
            $match = $onlyZombiesToDestroy
                ? $this->zombieDetector->isZombieThatHasToBeDestroyed($childNode)
                : $this->zombieDetector->isZombie($childNode);
            if ($match) {
                yield $childNode;
            } else {
                yield from $this->traverseSubtreeAndYieldZombieNodes($subgraph, $childNode, $onlyZombiesToDestroy);
            }
        }
    }

    /**
     * @param Node $node
     * @return string
     */
    protected function renderNodePath(ContentSubgraphInterface $subgraph, Node $node): string
    {
        $pathParts = [];
        /** @var Node|null $parent */
        $parent = $subgraph->findParentNode($node->aggregateId);
        while ($parent) {
            $pathParts[] = $this->nodeLabelGenerator->getLabel($parent);
            $parent = $subgraph->findParentNode($parent->aggregateId);
        }
        return implode('/', array_reverse($pathParts));
    }
}
