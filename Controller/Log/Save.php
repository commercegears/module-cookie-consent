<?php
/**
 * Copyright © CommerceGears. All rights reserved.
 */
declare(strict_types=1);

namespace CommerceGears\CookieConsent\Controller\Log;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;
use CommerceGears\CookieConsent\Model\ConsentLogger;

/**
 * POST swconsent/log/save: records a consent choice (only when the consent log is enabled).
 */
class Save implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly ConsentLogger $consentLogger,
        private readonly RemoteAddress $remoteAddress,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store', true);
        try {
            $categories = array_filter(array_map('trim', explode(',', (string)$this->request->getParam('categories'))));
            $logged = $this->consentLogger->log(
                (string)$this->request->getParam('consent_id'),
                (string)$this->request->getParam('action'),
                $categories,
                (int)$this->request->getParam('version'),
                (string)$this->remoteAddress->getRemoteAddress()
            );
            return $result->setData(['success' => true, 'logged' => $logged]);
        } catch (LocalizedException $e) {
            return $result->setHttpResponseCode(400)->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->logger->error('CommerceGears_CookieConsent: could not log consent: ' . $e->getMessage());
            return $result->setHttpResponseCode(500)->setData(['success' => false]);
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        $result = $this->jsonFactory->create();
        $result->setHttpResponseCode(403)->setData(['success' => false, 'message' => 'Invalid form key.']);
        return new InvalidRequestException($result);
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $this->formKeyValidator->validate($request);
    }
}
