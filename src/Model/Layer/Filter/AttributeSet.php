<?php

declare(strict_types=1);

namespace Infrangible\CatalogAttributeSetFilter\Model\Layer\Filter;

use FeWeDev\Base\Arrays;
use Infrangible\Core\Helper\Attribute;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\EntityType;
use Infrangible\Core\Helper\Stores;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\Layer\Filter\Item\DataBuilder;
use Magento\Catalog\Model\Layer\Filter\ItemFactory;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\StripTags;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Select;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class AttributeSet extends AbstractFilter
{
    /** @var Stores */
    protected $storeHelper;

    /** @var Database */
    protected $databaseHelper;

    /** @var EntityType */
    protected $entityTypeHelper;

    /** @var CollectionFactory */
    protected $attributeSetCollectionFactory;

    /** @var Arrays */
    protected $arrays;

    /** @var StripTags */
    protected $tagFilter;

    /** @var Attribute */
    protected $attributeHelper;

    /**
     * @throws LocalizedException
     */
    public function __construct(
        ItemFactory $filterItemFactory,
        StoreManagerInterface $storeManager,
        Layer $layer,
        DataBuilder $itemDataBuilder,
        Stores $storeHelper,
        Database $databaseHelper,
        EntityType $entityTypeHelper,
        CollectionFactory $attributeSetCollectionFactory,
        Arrays $arrays,
        StripTags $tagFilter,
        Attribute $attributeHelper,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $data
        );

        $this->setRequestVar('attribute_set_id');

        $this->storeHelper = $storeHelper;
        $this->databaseHelper = $databaseHelper;
        $this->entityTypeHelper = $entityTypeHelper;
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
        $this->arrays = $arrays;
        $this->tagFilter = $tagFilter;
        $this->attributeHelper = $attributeHelper;
    }

    public function getName(): Phrase
    {
        return __('Attribute Set');
    }

    public function apply(RequestInterface $request): AttributeSet
    {
        if (! $this->storeHelper->getStoreConfig('infrangible_catalogattributesetfilter/general/visible')) {
            $this->setItems([]);

            return $this;
        }

        $attributeSetId = $request->getParam($this->_requestVar);

        if (empty($attributeSetId)) {
            return $this;
        }

        $productCollection = $this->getLayer()->getProductCollection();

        $productCollection->addFieldToFilter(
            'attribute_set_id',
            $attributeSetId
        );

        $productCollection->getSelect()->where(
            'attribute_set_id = ?',
            $attributeSetId
        );

        $attributeSet = $this->attributeHelper->loadAttributeSet((int)$attributeSetId);

        $this->getLayer()->getState()->addFilter(
            $this->_createItem(
                $attributeSet->getAttributeSetName(),
                $attributeSetId
            )
        );

        $this->setItems([]);

        return $this;
    }

    /**
     * @throws LocalizedException
     */
    protected function _getItemsData(): array
    {
        $select = clone $this->getLayer()->getProductCollection()->getSelect();

        $select->reset(Zend_Db_Select::COLUMNS);
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);

        $select->columns(['e.attribute_set_id', 'count' => new \Zend_Db_Expr('COUNT(e.attribute_set_id)')]);
        $select->group('e.attribute_set_id');

        $countResult = $this->databaseHelper->fetchPairs($select);

        $productEntityType = $this->entityTypeHelper->getProductEntityType();

        $productAttributeSetCollection = $this->attributeSetCollectionFactory->create();

        $productAttributeSetCollection->addFieldToFilter(
            'entity_type_id',
            $productEntityType->getId()
        );
        $productAttributeSetCollection->addOrder(
            'attribute_set_name',
            Collection::SORT_ORDER_ASC
        );

        $visible = $this->storeHelper->getStoreConfig('infrangible_catalogattributesetfilter/general/visible');

        /** @var Set $productAttributeSet */
        foreach ($productAttributeSetCollection as $productAttributeSet) {
            $productAttributeSetId = $productAttributeSet->getId();
            $productAttributeSetName = $productAttributeSet->getAttributeSetName();

            $count = $this->arrays->getValue(
                $countResult,
                $productAttributeSetId,
                0
            );

            if ($visible == 2 || $count > 0) {
                $this->itemDataBuilder->addItemData(
                    $this->tagFilter->filter($productAttributeSetName),
                    $productAttributeSetId,
                    $count
                );
            }
        }

        return $this->itemDataBuilder->build();
    }
}
