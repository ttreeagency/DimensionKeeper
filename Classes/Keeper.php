<?php

namespace Ttree\DimensionKeeper;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContentDimensionCombinator;
use Neos\ContentRepository\Domain\Service\ContextFactory;
use Neos\Flow\Annotations as Flow;
use Psr\Log\LoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
class Keeper
{
    const CONFIGURATION_PATH = 'options.TtreeDimensionKeeper:Properties';

    /**
     * @Flow\Inject(name="Neos.Flow:SystemLogger")
     * @var LoggerInterface
     */
    protected $systemLogger;

    /**
     * @var bool
     * @Flow\InjectConfiguration(path="enabled")
     */
    protected $enabled = true;

    /**
     * @Flow\Inject
     * @var ContextFactory
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var ContentDimensionCombinator
     */
    protected $dimensionCombinator;

    public function sync(NodeInterface $node, string $propertyName, $oldValue, $newValue)
    {
        if ($this->enabled !== true || !$this->managedProperties($node, $propertyName)) {
            return;
        }

        $currentDimensions = $node->getDimensions();
        $this->systemLogger->debug(\vsprintf('Synchronize property %s start in %s', [$propertyName, \json_encode($currentDimensions)]));

        \array_map(function (NodeInterface $nodeVariant) use ($propertyName, $newValue) {
            $this->systemLogger->debug(\vsprintf('Synchronize property %s to node variant %s', [$propertyName, $nodeVariant->getContextPath()]));
            $this->skip(function () use ($nodeVariant, $propertyName, $newValue) {
                $nodeVariant->setProperty($propertyName, $newValue);
            });
        }, $this->getOtherNodeVariants($node, $currentDimensions));

    }

    public function skip(\Closure $closure)
    {
        $previousState = $this->enabled;
        try {
            $this->enabled = false;
            $closure();
        } finally {
            $this->enabled = $previousState;
        }
    }

    protected function managedProperties(NodeInterface $node, $propertyName)
    {
        $configuration = $node->getNodeType()->getConfiguration(self::CONFIGURATION_PATH) ?: [];
        return isset($configuration[$propertyName]) && $configuration[$propertyName] === true;
    }

    private function getOtherNodeVariants(NodeInterface $node, $currentDimensions)
    {
        $nodeVariants = [];

        foreach ($this->dimensionCombinator->getAllAllowedCombinations() as $dimensionCombination) {

            // Remove fallback dimension values
            $dimensions = array_map(fn($dimensionValues) => [reset($dimensionValues)], $dimensionCombination);

            if ($dimensions === $currentDimensions) {
                continue;
            }

            $context = $node->getContext()->getProperties();
            $context['dimensions'] = $dimensions;
            unset($context['targetDimensions']);

            $nodeVariant = $this->contextFactory->create($context)->getNodeByIdentifier((string)$node->getNodeAggregateIdentifier());
            if ($nodeVariant) {
                $nodeVariants[] = $nodeVariant;
            }
        }

        return $nodeVariants;
    }
}
