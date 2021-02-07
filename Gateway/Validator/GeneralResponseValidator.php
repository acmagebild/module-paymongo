<?php
/**
 * @author MageBild Team
 * @copyright Copyright (c) 2019 Magebild
 * @package Magebild_Paymongo
 */
namespace Magebild\Paymongo\Gateway\Validator;

/**
 * Class GeneralResponseValidator
 *
 * @package Magebild\Paymongo\Gateway\Validator
 */
class GeneralResponseValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    /**
     * @var \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader
     */
    protected $subjectReader;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    protected $serializer;

    /**
     * GeneralResponseValidator constructor.
     * @param \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
     * @param \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \Magento\Payment\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Framework\Serialize\Serializer\Json $serializer
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
        $this->serializer = $serializer;
    }

    /**
     * Implementation
     *
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response =  $this->subjectReader->readResponse($validationSubject);
        $descriptions = [];
        $codes = [];
        $isValid = true;
        if (is_array($response) && count($response) > 0) {
            $object = $this->serializer->unserialize($response[0]);
            if (isset($object['errors'])) {
                $isValid = false;
                foreach ($object['errors'] as $error) {
                    $descriptions[] = $error['detail'];
                    $codes[] = $error['code'];
                }
            }
        }
        return $this->createResult(true, $descriptions, $codes);
    }
}
