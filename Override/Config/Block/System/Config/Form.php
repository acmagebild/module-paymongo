<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */
namespace Magebild\Paymongo\Override\Config\Block\System\Config;

use Magento\Config\Model\Config\Reader\Source\Deployed\SettingChecker;
use Magento\Framework\App\Config\Data\ProcessorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Class Form
 *
 * @package Magebild\Paymongo\Override\Config\Block\System\Config
 */
class Form extends \Magento\Config\Block\System\Config\Form
{
    /**
     * @var SettingChecker
     */
    private $settingChecker;

    /**
     * Form constructor. No changes made
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Config\Model\Config\Factory $configFactory
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param \Magento\Config\Block\System\Config\Form\Fieldset\Factory $fieldsetFactory
     * @param \Magento\Config\Block\System\Config\Form\Field\Factory $fieldFactory
     * @param array $data
     * @param SettingChecker|null $settingChecker
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Factory $configFactory,
        \Magento\Config\Model\Config\Structure $configStructure,
        \Magento\Config\Block\System\Config\Form\Fieldset\Factory $fieldsetFactory,
        \Magento\Config\Block\System\Config\Form\Field\Factory $fieldFactory,
        array $data = [],
        SettingChecker $settingChecker = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $configFactory,
            $configStructure,
            $fieldsetFactory,
            $fieldFactory,
            $data,
            $settingChecker
        );
        $this->settingChecker = $settingChecker ?: ObjectManager::getInstance()->get(SettingChecker::class);
    }

    /**
     * Override function targeting paymongo_section field
     *
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     * @param \Magento\Config\Model\Config\Structure\Element\Group $group
     * @param \Magento\Config\Model\Config\Structure\Element\Section $section
     * @param string $fieldPrefix
     * @param string $labelPrefix
     * @return $this|Form
     */
    public function initFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        \Magento\Config\Model\Config\Structure\Element\Group $group,
        \Magento\Config\Model\Config\Structure\Element\Section $section,
        $fieldPrefix = '',
        $labelPrefix = ''
    ) {
        if (!$this->_configDataObject) {
            $this->_initObjects();
        }

        // Extends for config data
        $extraConfigGroups = [];

        /** @var $element \Magento\Config\Model\Config\Structure\Element\Field */
        foreach ($group->getChildren() as $element) {
            if ($element instanceof \Magento\Config\Model\Config\Structure\Element\Group) {
                $this->_initGroup($element, $section, $fieldset);
            } else {
                $path = $element->getConfigPath() ?: $element->getPath($fieldPrefix);
                if (!empty($fieldPrefix)) {
                    $segments = explode('/', $path);
                    $fieldSegment = end($segments);
                    array_splice($segments, count($segments) - 1, 1, $fieldPrefix . $fieldSegment);
                    $newPath = implode('/', $segments);
                    $path = $newPath;
                }

                if ($element->getSectionId() != $section->getId()) {
                    $groupPath = $element->getGroupPath();

                    if (!isset($extraConfigGroups[$groupPath])) {
                        $this->_configData = $this->_configDataObject->extendConfig(
                            $groupPath,
                            false,
                            $this->_configData
                        );
                        $extraConfigGroups[$groupPath] = true;
                    }
                }
                $this->_initElement($element, $fieldset, $path, $fieldPrefix, $labelPrefix);
            }
        }
        return $this;
    }

    /**
     * Override to target paymongo_section only
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Field $field
     * @param string $path
     * @return mixed|string|null
     */
    private function getFieldData(\Magento\Config\Model\Config\Structure\Element\Field $field, $path)
    {
        $data = $this->getAppConfigDataValue($path);

        $placeholderValue = $this->settingChecker->getPlaceholderValue(
            $path,
            $this->getScope(),
            $this->getStringScopeCode()
        );

        if ($placeholderValue) {
            $data = $placeholderValue;
        }

        if ($data === null) {
            //Additional logic
            if (strpos($path, 'paymongo_section') === false) {
                $path = $field->getConfigPath() !== null ? $field->getConfigPath() : $path;
            }
            $data = $this->getConfigValue($path);
            if ($field->hasBackendModel()) {
                $backendModel = $field->getBackendModel();
                // Backend models which implement ProcessorInterface are processed by ScopeConfigInterface
                if (!$backendModel instanceof ProcessorInterface) {
                    if (array_key_exists($path, $this->_configData)) {
                        $data = $this->_configData[$path];
                    }

                    $backendModel->setPath($path)
                        ->setValue($data)
                        ->setWebsite($this->getWebsiteCode())
                        ->setStore($this->getStoreCode())
                        ->afterLoad();
                    $data = $backendModel->getValue();
                }
            }
        }

        return $data;
    }

    /**
     * Initialize form element. No changes made
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Field $field
     * @param \Magento\Framework\Data\Form\Element\Fieldset $fieldset
     * @param string $path
     * @param string $fieldPrefix
     * @param string $labelPrefix
     * @return void
     */
    protected function _initElement(
        \Magento\Config\Model\Config\Structure\Element\Field $field,
        \Magento\Framework\Data\Form\Element\Fieldset $fieldset,
        $path,
        $fieldPrefix = '',
        $labelPrefix = ''
    ) {
        $inherit = !array_key_exists($path, $this->_configData);
        $data = $this->getFieldData($field, $path);

        $fieldRendererClass = $field->getFrontendModel();
        if ($fieldRendererClass) {
            $fieldRenderer = $this->_layout->getBlockSingleton($fieldRendererClass);
        } else {
            $fieldRenderer = $this->_fieldRenderer;
        }

        $fieldRenderer->setForm($this);
        $fieldRenderer->setConfigData($this->_configData);

        $elementName = $this->_generateElementName($field->getPath(), $fieldPrefix);
        $elementId = $this->_generateElementId($field->getPath($fieldPrefix));

        $dependencies = $field->getDependencies($fieldPrefix, $this->getStoreCode());
        $this->_populateDependenciesBlock($dependencies, $elementId, $elementName);

        $sharedClass = $this->_getSharedCssClass($field);
        $requiresClass = $this->_getRequiresCssClass($field, $fieldPrefix);
        $isReadOnly = $this->isReadOnly($field, $path);

        $formField = $fieldset->addField(
            $elementId,
            $field->getType(),
            [
                'name' => $elementName,
                'label' => $field->getLabel($labelPrefix),
                'comment' => $field->getComment($data),
                'tooltip' => $field->getTooltip(),
                'hint' => $field->getHint(),
                'value' => $data,
                'inherit' => $inherit,
                'class' => $field->getFrontendClass() . $sharedClass . $requiresClass,
                'field_config' => $field->getData(),
                'scope' => $this->getScope(),
                'scope_id' => $this->getScopeId(),
                'scope_label' => $this->getScopeLabel($field),
                'can_use_default_value' => $this->canUseDefaultValue($field->showInDefault()),
                'can_use_website_value' => $this->canUseWebsiteValue($field->showInWebsite()),
                'can_restore_to_default' => $this->isCanRestoreToDefault($field->canRestore()),
                'disabled' => $isReadOnly,
                'is_disable_inheritance' => $isReadOnly
            ]
        );
        $field->populateInput($formField);

        if ($field->hasValidation()) {
            $formField->addClass($field->getValidation());
        }
        if ($field->getType() == 'multiselect') {
            $formField->setCanBeEmpty($field->canBeEmpty());
        }
        if ($field->hasOptions()) {
            $formField->setValues($field->getOptions());
        }
        $formField->setRenderer($fieldRenderer);
    }

    /**
     * No changes
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Field $field
     * @param string $path
     * @return bool
     */
    private function isReadOnly(\Magento\Config\Model\Config\Structure\Element\Field $field, $path)
    {
        $isReadOnly = $this->settingChecker->isReadOnly(
            $path,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
        if (!$isReadOnly) {
            $isReadOnly = $this->getElementVisibility()->isDisabled($field->getPath())
                ?: $this->settingChecker->isReadOnly($path, $this->getScope(), $this->getStringScopeCode());
        }
        return $isReadOnly;
    }
}
