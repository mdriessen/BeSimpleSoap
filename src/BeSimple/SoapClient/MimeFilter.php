<?php

/*
 * This file is part of the BeSimpleSoapClient.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapClient;

use BeSimple\SoapCommon\Helper;
use BeSimple\SoapCommon\Mime\MultiPart as MimeMultiPart;
use BeSimple\SoapCommon\Mime\Parser as MimeParser;
use BeSimple\SoapCommon\Mime\Part as MimePart;
use BeSimple\SoapCommon\Mime\Part;
use BeSimple\SoapCommon\SoapRequest;
use BeSimple\SoapCommon\SoapRequestFilter;
use BeSimple\SoapCommon\SoapResponse as CommonSoapResponse;
use BeSimple\SoapCommon\SoapResponseFilter;

/**
 * MIME filter.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 */
class MimeFilter implements SoapRequestFilter, SoapResponseFilter
{
    public function filterRequest(SoapRequest $request, $attachmentType)
    {
        $attachmentsToSend = $request->getAttachments();
        if (count($attachmentsToSend) > 0) {
            $multipart = new MimeMultiPart('Part_' . rand(10, 15) . '_' . uniqid() . '.' . uniqid());
            $soapPart = new MimePart($request->getContent(), 'text/xml', 'utf-8', MimePart::ENCODING_EIGHT_BIT);
            $soapVersion = $request->getVersion();

            if ($soapVersion === SOAP_1_1 && $attachmentType & Helper::ATTACHMENTS_TYPE_MTOM) {
                $multipart->setHeader('Content-Type', 'type', 'application/xop+xml');
                $multipart->setHeader('Content-Type', 'start-info', 'text/xml');
                $soapPart->setHeader('Content-Type', 'application/xop+xml');
                $soapPart->setHeader('Content-Type', 'type', 'text/xml');
            } elseif ($soapVersion === SOAP_1_2) {
                $multipart->setHeader('Content-Type', 'type', 'application/soap+xml');
                $soapPart->setHeader('Content-Type', 'application/soap+xml');
            }

            $multipart->addPart($soapPart, true);
            foreach ($attachmentsToSend as $cid => $attachment) {
                $multipart->addPart($attachment, false);
            }
            $request->setContent($multipart->getMimeMessage());

            $headers = $multipart->getHeadersForHttp();
            list(, $contentType) = explode(': ', $headers[0]);

            $request->setContentType($contentType);
        }

        return $request;
    }

    public function filterResponse(CommonSoapResponse $response, $attachmentType)
    {
        $multiPartMessage = MimeParser::parseMimeMessage(
            $response->getContent(),
            ['Content-Type' => trim($response->getContentType())]
        );
        $soapPart = $multiPartMessage->getMainPart();
        $attachments = $multiPartMessage->getAttachments();

        $response->setContent($soapPart->getContent());
        $response->setContentType($soapPart->getHeader('Content-Type'));
        if (count($attachments) > 0) {
            $response->setAttachments($attachments);
        }

        return $response;
    }
}
