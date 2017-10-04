<?php
namespace Ttree\DimensionKeeper;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\ContentDimensionCombinator;
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

    /**
     * @var array
     */
    protected $tracker = [];

    /**
     * @var bool
     * @Flow\InjectConfiguration(path="enabled")
     */
    protected $enabled = true;

    public function sync(NodeInterface $node, string $propertyName, $oldValue, $newValue)
    {
        if ($this->enabled !== true || !$this->managedProperties($node, $propertyName)) {
            return;
        }

        $originalContextPath = $node->getContextPath();
        $key = md5($originalContextPath . $propertyName . \serialize($newValue));
        if (isset($this->tracker[$key]) && $this->tracker[$key] === true) {
            $this->systemLogger->log(\vsprintf('Skip synchronization property %s from node %s', [$propertyName, $originalContextPath]), \LOG_DEBUG, null, 'Ttree.DimensionKeeper');
            return;
        }

        $cache = [];
        /** @var NodeInterface $nodeVariant */
        foreach ($node->getOtherNodeVariants() as $nodeVariant) {
            if (isset($cache[$nodeVariant->getContextPath()]) && $cache[$nodeVariant->getContextPath()] === true) {
                continue;
            }

            $this->systemLogger->log(\vsprintf('Synchronize property %s to node variant %s', [$propertyName, $nodeVariant->getContextPath()]), \LOG_DEBUG, null, 'Ttree.DimensionKeeper');

            $nodeVariant->setProperty($propertyName, $newValue);
            $cache[$nodeVariant->getContextPath()] = true;
        }

        $this->tracker[$key] = true;
    }

    public function skip(\Closure $closure)
    {
        $previousState = $this->enabled;
        try {
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
}
