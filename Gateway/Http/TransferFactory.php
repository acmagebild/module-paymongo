<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package MageBild_Paymongo
 */

namespace Magebild\Paymongo\Gateway\Http;

use Magebild\Paymongo\Gateway\Request\SourceDataBuilder;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class TransferFactory
 *
 * @package Magebild\Paymongo\Gateway\Http
 */
class TransferFactory implements \Magento\Payment\Gateway\Http\TransferFactoryInterface
{
    const XML_API_PUB_KEY = 'paymongo/api/public_key';

    const XML_API_PUB_SECRET = 'paymongo/api/secret_key';

    const XML_API_ENV = 'payment/paymongo_section/api/env';

    /**
     * @var \Magento\Payment\Gateway\Http\TransferBuilder $transferBuilder
     */
    protected $transferBuilder;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $config
     */
    protected $config;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    protected $encryptor;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    protected $serializer;

    /**
     * TransferFactory constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Payment\Gateway\Http\TransferBuilder $transferBuilder
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Payment\Gateway\Http\TransferBuilder $transferBuilder,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Serialize\Serializer\Json $serializer
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
        $this->encryptor = $encryptor;
        $this->serializer = $serializer;
    }

    /**
     * Create request for transfer
     *
     * @param array $request
     * @return \Magento\Payment\Gateway\Http\Transfer|\Magento\Payment\Gateway\Http\TransferInterface
     * @throws LocalizedException
     */
    public function create(array $request)
    {
        $username = $this->config->getValue(self::XML_API_PUB_KEY);
        $password = $this->encryptor->decrypt($this->config->getValue(self::XML_API_PUB_SECRET));
        $useSecretKey = isset($request['secret_key_only']) && $request['secret_key_only'] == 1;
        if (!isset($request['api_url'])) {
            throw new LocalizedException(__('Paymongo API url not found'));
        }
        if ($request['api_url'] == SourceDataBuilder::SOURCE_URL) {
            $livemode = $this->config->getValue(self::XML_API_ENV) ? 'true' : 'false';
            $request['livemode'] = $livemode;
        }
        $this->transferBuilder->setUri($request['api_url']);
        unset($request['api_url']);
        unset($request['secret_key_only']);
        $data = ['data' => ['attributes' => $request]];
        if ($useSecretKey) {
            $this->transferBuilder
                ->setAuthUsername($password);
        } else {
            $this->transferBuilder
                ->setAuthUsername($username)
                ->setAuthPassword($password);
        }
        return $this->transferBuilder
            ->setMethod('POST')
            ->setHeaders(['Content-Type' => 'application/json'])
            ->setBody($this->serializer->serialize($data))
            ->build();
    }
}
