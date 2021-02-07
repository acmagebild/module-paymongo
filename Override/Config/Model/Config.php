<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */
namespace Magebild\Paymongo\Override\Config\Model;

use Magento\Config\Model\Config\Reader\Source\Deployed\SettingChecker;
use Magento\Config\Model\Config\Structure\Element\Field;
use Magento\Config\Model\Config\Structure\Element\Group;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ScopeResolverPool;
use Magento\Store\Model\ScopeTypeNormalizer;

/**
 * Class Config
 *
 * @package Magebild\Paymongo\Override\Config\Model
 */
class Config extends \Magento\Config\Model\Config
{
    /**
     * @var Config\Reader\Source\Deployed\SettingChecker
     */
    private $settingChecker;

    /**
     * @var ScopeResolverPool
     */
    private $scopeResolverPool;

    /**
     * @var ScopeTypeNormalizer
     */
    private $scopeTypeNormalizer;

    /**
     * @var \Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface
     */
    private $pillPut;

    /**
     * Config constructor. No changes made on this
     *
     * @param \Magento\Framework\App\Config\ReinitableConfigInterface $config
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Config\Model\Config\Structure $configStructure
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Config\Model\Config\Loader $configLoader
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param SettingChecker|null $settingChecker
     * @param array $data
     * @param ScopeResolverPool|null $scopeResolverPool
     * @param ScopeTypeNormalizer|null $scopeTypeNormalizer
     * @param \Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface|null $pillPut
     */
    public function __construct(
        \Magento\Framework\App\Config\ReinitableConfigInterface $config,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Config\Model\Config\Structure $configStructure,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Config\Model\Config\Loader $configLoader,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        SettingChecker $settingChecker = null,
        array $data = [],
        ScopeResolverPool $scopeResolverPool = null,
        ScopeTypeNormalizer $scopeTypeNormalizer = null,
        \Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface $pillPut = null
    ) {
        parent::__construct(
            $config,
            $eventManager,
            $configStructure,
            $transactionFactory,
            $configLoader,
            $configValueFactory,
            $storeManager,
            $settingChecker,
            $data,
            $scopeResolverPool,
            $scopeTypeNormalizer,
            $pillPut
        );
        $this->settingChecker = $settingChecker
            ?? ObjectManager::getInstance()->get(SettingChecker::class);
        $this->scopeResolverPool = $scopeResolverPool
            ?? ObjectManager::getInstance()->get(ScopeResolverPool::class);
        $this->scopeTypeNormalizer = $scopeTypeNormalizer
            ?? ObjectManager::getInstance()->get(ScopeTypeNormalizer::class);
        $this->pillPut = $pillPut ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\MessageQueue\PoisonPill\PoisonPillPutInterface::class);
    }

    /**
     * No changes made.
     *
     * @param string $groupId
     * @param array $groupData
     * @param array $groups
     * @param string $sectionPath
     * @param array $extraOldGroups
     * @param array $oldConfig
     * @param \Magento\Framework\DB\Transaction $saveTransaction
     * @param \Magento\Framework\DB\Transaction $deleteTransaction
     */
    protected function _processGroup(
        $groupId,
        array $groupData,
        array $groups,
        $sectionPath,
        array &$extraOldGroups,
        array &$oldConfig,
        \Magento\Framework\DB\Transaction $saveTransaction,
        \Magento\Framework\DB\Transaction $deleteTransaction
    ) {
        $groupPath = $sectionPath . '/' . $groupId;

        if (isset($groupData['fields'])) {
            /** @var \Magento\Config\Model\Config\Structure\Element\Group $group */
            $group = $this->_configStructure->getElement($groupPath);

            // set value for group field entry by fieldname
            // use extra memory
            $fieldsetData = [];
            foreach ($groupData['fields'] as $fieldId => $fieldData) {
                $fieldsetData[$fieldId] = $fieldData['value'] ?? null;
            }

            foreach ($groupData['fields'] as $fieldId => $fieldData) {
                $isReadOnly = $this->settingChecker->isReadOnly(
                    $groupPath . '/' . $fieldId,
                    $this->getScope(),
                    $this->getScopeCode()
                );

                if ($isReadOnly) {
                    continue;
                }

                $field = $this->getField($sectionPath, $groupId, $fieldId);
                /** @var \Magento\Framework\App\Config\ValueInterface $backendModel */
                $backendModel = $field->hasBackendModel()
                    ? $field->getBackendModel()
                    : $this->_configValueFactory->create();

                if (!isset($fieldData['value'])) {
                    $fieldData['value'] = null;
                }

                if ($field->getType() == 'multiline' && is_array($fieldData['value'])) {
                    $fieldData['value'] = trim(implode(PHP_EOL, $fieldData['value']));
                }

                $data = [
                    'field' => $fieldId,
                    'groups' => $groups,
                    'group_id' => $group->getId(),
                    'scope' => $this->getScope(),
                    'scope_id' => $this->getScopeId(),
                    'scope_code' => $this->getScopeCode(),
                    'field_config' => $field->getData(),
                    'fieldset_data' => $fieldsetData,
                ];
                $backendModel->addData($data);
                $this->_checkSingleStoreMode($field, $backendModel);

                $path = $this->getFieldPath($field, $fieldId, $extraOldGroups, $oldConfig);
                $backendModel->setPath($path)->setValue($fieldData['value']);

                $inherit = !empty($fieldData['inherit']);
                if (isset($oldConfig[$path])) {
                    $backendModel->setConfigId($oldConfig[$path]['config_id']);

                    /**
                     * Delete config data if inherit
                     */
                    if (!$inherit) {
                        $saveTransaction->addObject($backendModel);
                    } else {
                        $deleteTransaction->addObject($backendModel);
                    }
                } elseif (!$inherit) {
                    $backendModel->unsConfigId();
                    $saveTransaction->addObject($backendModel);
                }
            }
        }

        if (isset($groupData['groups'])) {
            foreach ($groupData['groups'] as $subGroupId => $subGroupData) {
                $this->_processGroup(
                    $subGroupId,
                    $subGroupData,
                    $groups,
                    $groupPath,
                    $extraOldGroups,
                    $oldConfig,
                    $saveTransaction,
                    $deleteTransaction
                );
            }
        }
    }

    /**
     * Override method to target paymongo_section only
     *
     * @param Field $field
     * @param string $fieldId
     * @param array $oldConfig
     * @param array $extraOldGroups
     * @return string
     */
    private function getFieldPath(Field $field, string $fieldId, array &$oldConfig, array &$extraOldGroups): string
    {
        $path = $field->getGroupPath() . '/' . $fieldId;

        /**
         * Look for custom defined field path
         */
        $configPath = $field->getConfigPath();
        $groupPath = $field->getGroupPath();

        //Added custom logic here
        if ($configPath && strrpos($configPath, '/') > 0 && strpos($groupPath, 'paymongo_section') === false) {
            // Extend old data with specified section group
            $configGroupPath = substr($configPath, 0, strrpos($configPath, '/'));
            if (!isset($extraOldGroups[$configGroupPath])) {
                $oldConfig = $this->extendConfig($configGroupPath, true, $oldConfig);
                $extraOldGroups[$configGroupPath] = true;
            }
            $path = $configPath;
        }

        return $path;
    }

    /**
     * No changes made.
     *
     * @param string $sectionId
     * @param string $groupId
     * @param string $fieldId
     * @return Field
     */
    private function getField(string $sectionId, string $groupId, string $fieldId): Field
    {
        /** @var \Magento\Config\Model\Config\Structure\Element\Group $group */
        $group = $this->_configStructure->getElement($sectionId . '/' . $groupId);
        $fieldPath = $group->getPath() . '/' . $this->getOriginalFieldId($group, $fieldId);
        $field = $this->_configStructure->getElement($fieldPath);

        return $field;
    }

    /**
     * No changes
     *
     * @param Group $group
     * @param string $fieldId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getOriginalFieldId(Group $group, string $fieldId): string
    {
        if ($group->shouldCloneFields()) {
            $cloneModel = $group->getCloneModel();

            /** @var \Magento\Config\Model\Config\Structure\Element\Field $field */
            foreach ($group->getChildren() as $field) {
                foreach ($cloneModel->getPrefixes() as $prefix) {
                    if ($prefix['field'] . $field->getId() === $fieldId) {
                        $fieldId = $field->getId();
                        break(2);
                    }
                }
            }
        }

        return $fieldId;
    }
}
