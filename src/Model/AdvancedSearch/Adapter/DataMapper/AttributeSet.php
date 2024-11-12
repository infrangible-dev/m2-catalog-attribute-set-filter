<?php

declare(strict_types=1);

namespace Infrangible\CatalogAttributeSetFilter\Model\AdvancedSearch\Adapter\DataMapper;

use FeWeDev\Base\Arrays;
use Infrangible\Core\Helper\Database;
use Magento\AdvancedSearch\Model\Adapter\DataMapper\AdditionalFieldsProviderInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class AttributeSet implements AdditionalFieldsProviderInterface
{
    /** @var Database */
    protected $databaseHelper;

    /** @var Arrays */
    protected $arrays;

    public function __construct(Database $databaseHelper, Arrays $arrays)
    {
        $this->databaseHelper = $databaseHelper;
        $this->arrays = $arrays;
    }

    public function getFields(array $productIds, $storeId): array
    {
        $query = $this->databaseHelper->select(
            $this->databaseHelper->getTableName('catalog_product_entity'),
            ['entity_id', 'attribute_set_id']
        );

        $query->joinLeft(
            'eav_attribute_set',
            'eav_attribute_set.attribute_set_id = catalog_product_entity.attribute_set_id',
            ['eav_attribute_set.attribute_set_name']
        );

        $query->where(
            'entity_id IN (?)',
            $productIds
        );

        $fields = [];

        $attributeSetList = $this->databaseHelper->fetchAssoc($query);

        foreach ($attributeSetList as $productId => $attributeSetData) {
            $fields[ $productId ] = [
                'attribute_set_id'   => $this->arrays->getValue(
                    $attributeSetData,
                    'attribute_set_id'
                ),
                'attribute_set_name' => $this->arrays->getValue(
                    $attributeSetData,
                    'attribute_set_name'
                )
            ];
        }

        return $fields;
    }
}
