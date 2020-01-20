<?php

namespace BeSimple\SoapClient;

use BeSimple\SoapCommon\FilterHelper;
use BeSimple\SoapCommon\Helper;
use BeSimple\SoapCommon\SoapRequest;
use BeSimple\SoapCommon\SoapRequestFilter;

/**
 * XML MIME filter that fixes the namespace of xmime:contentType attribute.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 */
class XmlMimeFilter implements SoapRequestFilter
{
    public function filterRequest(SoapRequest $request, $attachmentType)
    {
        // get \DOMDocument from SOAP request
        $dom = $request->getContentDocument();

        // create FilterHelper
        $filterHelper = new FilterHelper($dom);

        // add the neccessary namespaces
        $filterHelper->addNamespace(Helper::PFX_XMLMIME, Helper::NS_XMLMIME);

        // get xsd:base64binary elements
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('XOP', Helper::NS_XOP);
        $query = '//XOP:Include/..';
        $nodes = $xpath->query($query);

        // exchange attributes
        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                if ($node->hasAttribute('contentType')) {
                    $contentType = $node->getAttribute('contentType');
                    $node->removeAttribute('contentType');
                    $filterHelper->setAttribute($node, Helper::NS_XMLMIME, 'contentType', $contentType);
                }
            }
        }

        $request->setContent($dom->saveXML());

        return $request;
    }
}
