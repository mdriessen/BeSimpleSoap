<?php

namespace BeSimple\SoapCommon\Fault;

use Exception;
use SimpleXMLElement;
use SoapFault;

class SoapFaultParser
{
    /**
     * @param string $soapFaultXmlSource
     * @return SoapFault
     */
    public static function parseSoapFault($soapFaultXmlSource)
    {
        try {
            $simpleXMLElement = new SimpleXMLElement($soapFaultXmlSource);
        } catch (Exception $e) {
            return new SoapFault('Invalid XML', $soapFaultXmlSource);
        }

        $faultCode = $simpleXMLElement->xpath('//faultcode');
        if ($faultCode === false || count($faultCode) === 0) {
            $faultCode = 'Unable to parse faultCode';
        }

        $faultString = $simpleXMLElement->xpath('//faultstring');
        if ($faultString === false || count($faultString) === 0) {
            $faultString = 'Unable to parse faultString';
        }

        return new SoapFault(
            (string)$faultCode[0],
            (string)$faultString[0]
        );
    }
}
