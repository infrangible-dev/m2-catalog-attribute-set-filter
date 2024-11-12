<?php

declare(strict_types=1);

namespace Infrangible\CatalogAttributeSetFilter\Plugin\Catalog\Model\Layer;

use Infrangible\CatalogAttributeSetFilter\Model\Layer\Filter\AttributeSet;
use Infrangible\Core\Helper\Instances;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class FilterList
{
    /** @var Instances */
    protected $instanceHelper;

    private $attributeSetFilter;

    public function __construct(Instances $instanceHelper)
    {
        $this->instanceHelper = $instanceHelper;
    }

    /**
     * @param array|AbstractFilter[] $result
     *
     * @return array|AbstractFilter[]
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterGetFilters(Layer\FilterList $subject, array $result, Layer $layer): array
    {
        if ($this->attributeSetFilter === null) {
            $this->attributeSetFilter = $this->instanceHelper->getInstance(
                AttributeSet::class,
                ['layer' => $layer]
            );
        }

        $result[] = $this->attributeSetFilter;

        return $result;
    }
}
