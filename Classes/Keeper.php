<?php
namespace Ttree\DimensionKeeper;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class Keeper
{
    const CONFIGURATION_PATH = 'options.TtreeDimensionKeeper:Properties';

    protected $tracker = [];

    public function sync(NodeInterface $node, string $propertyName, $oldValue, $newValue)
    {
        if (!$this->managedProperties($node, $propertyName)) {
            return;
        }

        $key = md5($node->getIdentifier() . $propertyName . \json_encode($newValue));
        if (isset($this->tracker[$key]) && $this->tracker[$key] === true) {
            return;
        }

        /** @var NodeInterface $nodeVariant */
        foreach ($node->getContext()->getNodeVariantsByIdentifier($node->getIdentifier()) as $nodeVariant) {
            $nodeVariant->setProperty($propertyName, $newValue);
        }
    }

    protected function managedProperties(NodeInterface $node, $propertyName)
    {
        $configuration = $node->getNodeType()->getConfiguration(self::CONFIGURATION_PATH) ?: [];
        return isset($configuration[$propertyName]) && $configuration[$propertyName] === true;
    }
}
