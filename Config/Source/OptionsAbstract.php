<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@totalinternetgroup.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@totalinternetgroup.nl for more information.
 *
 * @copyright   Copyright (c) 2016 Total Internet Group B.V. (http://www.totalinternetgroup.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

namespace TIG\PostNL\Config\Source;

/**
 * Class OptionsAbstract
 *
 * @package TIG\PostNL\Config\Source
 */
abstract class OptionsAbstract
{
    /**
     * Property for the possible product options.
     */
    protected $availableOptions;

    /**
     * Property for filterd product options matched by account type and flags.
     */
    protected $filterdOptions;

    /**
     * Group options by group types
     */
    protected $groupedOptions;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $productOptionsConfig;

    /**
     * @param \TIG\PostNL\Config\Provider\ProductOptions $config
     */
    public function __construct(
        \TIG\PostNL\Config\Provider\ProductOptions $config
    ) {
        $this->productOptionsConfig = $config;
    }

    /**
     * @param bool|Array $flags
     * @param bool       $checkAvailable
     *
     * @return array $availableOptions
     */
    public function getProductoptions($flags = false, $checkAvailable = false)
    {
        if (false !== $checkAvailable) {
            $this->setOptionsBySupportedType();
        }

        if (false !== $flags && is_array($flags)) {
            $this->setFilterdOptions($flags);
        }

        return $this->getOptionArrayUsableForConfiguration();
    }

    /**
     * @param $flags
     */
    public function setFilterdOptions($flags)
    {
        $this->filterdOptions = [];
        // Filter availableOptions on flags
        foreach ($this->availableOptions as $key => $option) {
            $this->setOptionsByFlagFilters($flags, $option, $key);
        }
    }

    /**
     * @param $flags => [
     *               'isAvond' => true,
     *               'isSunday => false,
     *               etc.. ]
     * @param $option
     * @param $productCode
     */
    public function setOptionsByFlagFilters($flags, $option, $productCode)
    {
        $filterFlags = array_filter($flags, function ($value, $key) use ($option) {
            return isset($option[$key]) && $option[$key] == $value;
        }, \Zend\Stdlib\ArrayUtils::ARRAY_FILTER_USE_BOTH);

        if (count($filterFlags) !== 0) {
            $this->filterdOptions[$productCode] = $this->availableOptions[$productCode];
        }
    }

    /**
     * Check by supported configuration options.
     * @return array
     */
    public function setOptionsBySupportedType()
    {
        $supportedTypes = explode(',', $this->productOptionsConfig->getSupportedProductOptions());

        if (empty($supportedTypes)) {
            return $this->availableOptions;
        }

        $supportedOptions = array_filter($supportedTypes, function ($type) {
            return isset($this->availableOptions[$type]);
        });

        $this->availableOptions = array_filter($this->availableOptions, function ($code) use ($supportedOptions) {
            return in_array($code, $supportedOptions);
        }, \Zend\Stdlib\ArrayUtils::ARRAY_FILTER_USE_KEY);

    }

    /**
     * @return array
     */
    public function getOptionArrayUsableForConfiguration()
    {
        $options = [];
        if (count($this->filterdOptions) == 0) {
            return [['value' => 0, 'label' => __('There are no available options')]];
        }

        foreach ($this->filterdOptions as $key => $option) {
            $options[] = ['value' => $option['value'], 'label' => __($option['label'])];
        }

        return $options;
    }

    /**
     * Set Options sorted by group type.
     * @param array $options
     * @param array $groups
     *
     */
    public function setGroupedOptions($options, $groups)
    {
        $optionsSorted = $this->getOptionsArrayForGrouped($options);
        $optionsGroupChecked = array_filter($groups, function ($key) use ($optionsSorted) {
            return array_key_exists($key, $optionsSorted);
        }, \Zend\Stdlib\ArrayUtils::ARRAY_FILTER_USE_KEY);

        foreach ($optionsGroupChecked as $group => $label) {
            $this->groupedOptions[] = [
                'label' => __($label),
                'value' => $optionsSorted[$group]
            ];
        }
    }

    /**
     * @return array
     */
    public function getGroupedOptions()
    {
        return $this->groupedOptions;
    }

    /**
     * This sets the array of options, so it can be used for the grouped configurations list.
     * @param $options
     * @return array
     */
    protected function getOptionsArrayForGrouped($options)
    {
        $optionsChecked = array_filter($options, function ($value) {
            return array_key_exists('group', $value);
        });

        $optionsSorted = [];
        foreach ($optionsChecked as $key => $option) {
            $optionsSorted[$option['group']][] = [
                'value' => $option['value'],
                'label' => __($option['label'])
            ];
        }

        return $optionsSorted;
    }
}
