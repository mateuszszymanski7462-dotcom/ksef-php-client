<?php

declare(strict_types=1);

namespace N1ebieski\KSEFClient\Actions\ValidateXml;

use DOMDocument;
use N1ebieski\KSEFClient\Actions\AbstractHandler;
use N1ebieski\KSEFClient\Exceptions\XmlValidationException;

final class ValidateXmlHandler extends AbstractHandler
{
    public function handle(ValidateXmlAction $action): bool
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadXML($action->document);

        $isValid = $dom->schemaValidate($action->schemaPath->value);

        if ( ! $isValid) {
            $errors = libxml_get_errors();

            libxml_clear_errors();

            throw new XmlValidationException(
                "Document is not valid with xsd: {$action->schemaPath->value}",
                context: [
                    'document' => $action->document,
                    'errors' => $errors
                ]
            );
        }

        libxml_clear_errors();

        return true;
    }
}
