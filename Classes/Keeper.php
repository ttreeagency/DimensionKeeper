<?php
namespace Ttree\DimensionKeeper;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContentDimensionCombinator;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\SystemLoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class Keeper
{
    const CONFIGURATION_PATH = 'options.TtreeDimensionKeeper:Properties';

    /**
     * @var SystemLoggerInterface
     * @Flow\Inject
     */
    protected $systemLogger;

    /**
     * @var ContentDimensionCombinator
     * @Flow\Inject
     */
    protected $contentDimensionCombinator;

    protected $tracker = [];

    public function sync(NodeInterface $node, string $propertyName, $oldValue, $newValue)
    {
        if (!$this->managedProperties($node, $propertyName)) {
            return;
        }

        $key = md5($node->getContextPath() . $propertyName . \serialize($newValue));
        if (isset($this->tracker[$key]) && $this->tracker[$key] === true) {
            $this->systemLogger->log(\vsprintf('Skip synchronization property %s from node %s', [$propertyName, $node->getContextPath()]), \LOG_DEBUG);
            return;
        }

        $query = new FlowQuery([$node]);
        foreach ($this->contentDimensionCombinator->getAllAllowedCombinations() as $dimensions) {
            $nodeVariant = $this->nodeVariant($query, $dimensions);
            if ($nodeVariant === null) {
                continue;
            }
            $this->systemLogger->log(\vsprintf('Synchronize property %s to node variant %s', [$propertyName, $nodeVariant->getContextPath()]), \LOG_DEBUG);
            $nodeVariant->setProperty($propertyName, $newValue);
        }

        $this->tracker[$key] = true;
    }

    protected function nodeVariant(FlowQuery $query, array $dimensions): ?NodeInterface
    {
        $targetDimensions = array_map(function ($dimensionValues) {
            return array_shift($dimensionValues);
        }, $dimensions);

        return $query->context([
            'dimensions' => $dimensions,
            'targetDimensions' => $targetDimensions
        ])->get(0);
    }

    protected function managedProperties(NodeInterface $node, $propertyName)
    {
        $configuration = $node->getNodeType()->getConfiguration(self::CONFIGURATION_PATH) ?: [];
        return isset($configuration[$propertyName]) && $configuration[$propertyName] === true;
    }
}
