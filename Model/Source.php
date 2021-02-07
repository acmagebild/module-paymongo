<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */

namespace Magebild\Paymongo\Model;

use Magebild\Paymongo\Gateway\Http\TransferFactory;

/**
 * Class Source
 *
 * @package Magebild\Paymongo\Model
 */
class Source
{
    const GET_SOURCE_URL = 'https://api.paymongo.com/v1/sources';

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory $clientFactory
     */
    protected $clientFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    protected $serializer;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $config
     */
    protected $config;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    protected $encryptor;

    /**
     * Source constructor.
     *
     * @param \Magento\Framework\HTTP\ZendClientFactory $clientFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Magento\Framework\HTTP\ZendClientFactory $clientFactory,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->clientFactory = $clientFactory;
        $this->serializer = $serializer;
        $this->config = $config;
        $this->encryptor = $encryptor;
    }

    /**
     * Get source
     *
     * @param string $sourceId
     * @return array|bool|float|int|mixed|string|null
     * @throws \Zend_Http_Client_Exception
     */
    public function retrieveSource($sourceId)
    {
        $client = $this->clientFactory->create();
        $client->setAuth($this->getAuthPassword(), $this->getAuthPassword());
        $client->setMethod(\Zend_Http_Client::GET);
        $client->setParameterGet('id', $sourceId);
        $client->setUri(self::GET_SOURCE_URL);
        $response = $client->request();
        if ($response->getBody()) {
            return $this->serializer->unserialize($response->getBody());
        }
        return $response->getStatus();
    }

    /**
     * Get username
     *
     * @return string
     */
    private function getAuthUsername()
    {
        return $this->config->getValue(TransferFactory::XML_API_PUB_KEY);
    }

    /**
     * Get password
     *
     * @return string
     */
    private function getAuthPassword()
    {
        return $this->encryptor->decrypt($this->config->getValue(TransferFactory::XML_API_PUB_SECRET));
    }
}
