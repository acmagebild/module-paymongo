<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */

namespace Magebild\Paymongo\Gateway\Http\Client;

/**
 * Class Authorization
 *
 * @package Magebild\Paymongo\Gateway\Http\Client
 */
class Authorization implements \Magento\Payment\Gateway\Http\ClientInterface
{
    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory $clientFactory
     */
    protected $clientFactory;

    /**
     * @var \Magento\Payment\Gateway\Http\ConverterInterface $converter
     */
    protected $converter;

    /**
     * Authorization constructor.
     *
     * @param \Magento\Framework\HTTP\ZendClientFactory $clientFactory
     * @param \Magento\Payment\Gateway\Http\ConverterInterface $converter
     */
    public function __construct(
        \Magento\Framework\HTTP\ZendClientFactory $clientFactory,
        \Magento\Payment\Gateway\Http\ConverterInterface $converter = null
    ) {
        $this->clientFactory = $clientFactory;
        $this->converter = $converter;
    }

    /**
     * Interface implementation
     *
     * @param \Magento\Payment\Gateway\Http\TransferInterface $transferObject
     * @return array
     * @throws \Magento\Payment\Gateway\Http\ClientException
     * @throws \Magento\Payment\Gateway\Http\ConverterException
     * @throws \Zend_Http_Client_Exception
     */
    public function placeRequest(
        \Magento\Payment\Gateway\Http\TransferInterface $transferObject
    ) {
        $client = $this->clientFactory->create();
        $client->setAuth($transferObject->getAuthUsername(), $transferObject->getAuthPassword());
        $client->setMethod($transferObject->getMethod());

        $client->setRawData($transferObject->getBody());

        $client->setHeaders($transferObject->getHeaders());
        $client->setUri($transferObject->getUri());
        $client->setUrlEncodeBody(false);

        try {
            $response = $client->request();
            $result = $this->converter
                ? $this->converter->convert($response->getBody())
                : [$response->getBody()];
        } catch (\Zend_Http_Client_Exception $e) {
            throw new \Magento\Payment\Gateway\Http\ClientException(
                __($e->getMessage())
            );
        } catch (\Magento\Payment\Gateway\Http\ConverterException $e) {
            throw $e;
        } finally {
        }

        return $result;
    }
}
