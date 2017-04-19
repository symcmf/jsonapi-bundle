<?php

namespace JsonApiBundle\Services;

use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Factories\Exceptions;
use Symfony\Component\HttpFoundation\Response;

class JSONApiError implements ErrorInterface
{
    const ERRORS = [
        'continue' => Response::HTTP_CONTINUE,
        'switchingProtocols' => Response::HTTP_SWITCHING_PROTOCOLS,
        'processing' => Response::HTTP_PROCESSING,
        'ok' => Response::HTTP_OK,
        'created' => Response::HTTP_CREATED,
        'accepted' => Response::HTTP_ACCEPTED,
        'nonAuthoritativeInformation' => Response::HTTP_NON_AUTHORITATIVE_INFORMATION,
        'noContent' => Response::HTTP_NO_CONTENT,
        'resetContent' => Response::HTTP_RESET_CONTENT,
        'partialContent' => Response::HTTP_PARTIAL_CONTENT,
        'multiStatus' => Response::HTTP_MULTI_STATUS,
        'alreadyReported' => Response::HTTP_ALREADY_REPORTED,
        'imUsed' => Response::HTTP_IM_USED,
        'multipleChoices' => Response::HTTP_MULTIPLE_CHOICES,
        'movedPermanently' => Response::HTTP_MOVED_PERMANENTLY,
        'found' => Response::HTTP_FOUND,
        'seeOther' => Response::HTTP_SEE_OTHER,
        'notModified' => Response::HTTP_NOT_MODIFIED,
        'useProxy' => Response::HTTP_USE_PROXY,
        'reserved' => Response::HTTP_RESERVED,
        'temporaryRedirect' => Response::HTTP_TEMPORARY_REDIRECT,
        'permanentlyRedirect' => Response::HTTP_PERMANENTLY_REDIRECT,
        'badRequest' => Response::HTTP_BAD_REQUEST,
        'unauthorized' => Response::HTTP_UNAUTHORIZED,
        'paymentRequired' => Response::HTTP_PAYMENT_REQUIRED,
        'forbidden' => Response::HTTP_FORBIDDEN,
        'notFound' => Response::HTTP_NOT_FOUND,
        'methodNotAllowed' => Response::HTTP_METHOD_NOT_ALLOWED,
        'notAcceptable' => Response::HTTP_NOT_ACCEPTABLE,
        'proxyAuthenticationRequest' => Response::HTTP_PROXY_AUTHENTICATION_REQUIRED,
        'requestTimeout' => Response::HTTP_REQUEST_TIMEOUT,
        'conflict' => Response::HTTP_CONFLICT,
        'gone' => Response::HTTP_GONE,
        'lengthRequired' => Response::HTTP_LENGTH_REQUIRED,
        'preconditionFailed' => Response::HTTP_PRECONDITION_FAILED,
        'requestEntityToLarge' => Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
        'requestUriTooLong' => Response::HTTP_REQUEST_URI_TOO_LONG,
        'unsupportedMediaType' => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
        'requestedRangeNotSatisfiable' => Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE,
        'expectationFailed' => Response::HTTP_EXPECTATION_FAILED,
        'iAmATeapot' => Response::HTTP_I_AM_A_TEAPOT,
        'misdirectedRequest' => Response::HTTP_MISDIRECTED_REQUEST,
        'unprocessableEntity' => Response::HTTP_UNPROCESSABLE_ENTITY,
        'locked' => Response::HTTP_LOCKED,
        'failedDependency' => Response::HTTP_FAILED_DEPENDENCY,
        'reservedForWebdayAdvancedCollectionsExpiredProposal' =>
            Response::HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL,
        'upgradeRequired' => Response::HTTP_UPGRADE_REQUIRED,
        'preconditionRequired' => Response::HTTP_PRECONDITION_REQUIRED,
        'tooManyRequests' => Response::HTTP_TOO_MANY_REQUESTS,
        'requestHeaderFieldsTooLarge' => Response::HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE,
        'unavailableForLegalReason' => Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS,
        'internalServerError' => Response::HTTP_INTERNAL_SERVER_ERROR,
        'notImplemented' => Response::HTTP_NOT_IMPLEMENTED,
        'badGateway' => Response::HTTP_BAD_GATEWAY,
        'serviceUnavailable' => Response::HTTP_SERVICE_UNAVAILABLE,
        'gatewayTimeout' => Response::HTTP_GATEWAY_TIMEOUT,
        'versionNotSupported' => Response::HTTP_VERSION_NOT_SUPPORTED,
        'variantAlsoNegotiatesExperimental' => Response::HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL,
        'insufficientStorage' => Response::HTTP_INSUFFICIENT_STORAGE,
        'loopDetected' => Response::HTTP_LOOP_DETECTED,
        'notExtended' => Response::HTTP_NOT_EXTENDED,
        'networkAuthenticationRequired' => Response::HTTP_NETWORK_AUTHENTICATION_REQUIRED
    ];

    /** @var int|string|null */
    protected $idx;

    /** @var null|array<string,\Neomerx\JsonApi\Contracts\Schema\LinkInterface> */
    protected $links;

    /** @var string|null */
    protected $status;

    /** @var string|null */
    protected $code;

    /** @var string|null */
    protected $title;

    /** @var string|null */
    protected $detail;

    /** @var array|null */
    protected $source;

    /** @var mixed|null */
    protected $meta;

    /**
     * @param int|string|null    $idx
     * @param LinkInterface|null $aboutLink
     * @param int|string|null    $status
     * @param int|string|null    $code
     * @param string|null        $title
     * @param string|null        $detail
     * @param array|null         $source
     * @param mixed|null         $meta
     */
    public function __construct(
        $idx = null,
        LinkInterface $aboutLink = null,
        $status = null,
        $code = null,
        $title = null,
        $detail = null,
        array $source = null,
        $meta = null
    ) {
        $this->checkIdx($idx);
        $this->checkCode($code);
        $this->checkTitle($title);
        $this->checkStatus($status);
        $this->checkDetail($detail);
    }

    /** @inheritdoc */
    public function getId()
    {
        return $this->idx;
    }

    /** @inheritdoc */
    public function getLinks()
    {
        return $this->links;
    }

    /** @inheritdoc */
    public function getStatus()
    {
        return $this->status;
    }

    /** @inheritdoc */
    public function getCode()
    {
        return $this->code;
    }

    /** @inheritdoc */
    public function getTitle()
    {
        return $this->title;
    }

    /** @inheritdoc */
    public function getDetail()
    {
        return $this->detail;
    }

    /** @inheritdoc */
    public function getSource()
    {
        return $this->source;
    }

    /** @inheritdoc */
    public function getMeta()
    {
        return $this->meta;
    }

    /** @param int|null|string $idx */
    public function setIdx($idx)
    {
        $this->idx = $idx;
    }

    /** @param array|null $aboutLink */
    public function setLinks($aboutLink)
    {
        $this->links = ($aboutLink === null ? null : [DocumentInterface::KEYWORD_ERRORS_ABOUT => $aboutLink]);
    }

    /** @param null|string $status */
    public function setStatus($status)
    {
        $this->status = ($status !== null ? (string)$status : null);
    }

    /** @param null|string $code */
    public function setCode($code)
    {
        $this->code = ($code !== null ? (string)$code : null);
    }

    /** @param null|string $title */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /** @param null|string $detail */
    public function setDetail($detail)
    {
        $this->detail = $detail;
    }

    /** @param array|null $source */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /** @param mixed|null $meta */
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }

    /** @param int|string|null $idx */
    private function checkIdx($idx)
    {
        ($idx === null || is_int($idx) === true ||
            is_string($idx) === true) ?: Exceptions::throwInvalidArgument('idx', $idx);
    }

    /** @param string|null $title */
    private function checkTitle($title)
    {
        ($title === null || is_string($title) === true) ?: Exceptions::throwInvalidArgument('title', $title);
    }

    /** @param string|null $detail */
    private function checkDetail($detail)
    {
        ($detail === null || is_string($detail) === true) ?: Exceptions::throwInvalidArgument('detail', $detail);
    }

    /** @param int|string|null $status */
    private function checkStatus($status)
    {
        $isOk = ($status === null || is_int($status) === true || is_string($status) === true);
        $isOk ?: Exceptions::throwInvalidArgument('status', $status);
    }

    /** @param int|string|null $code */
    private function checkCode($code)
    {
        $isOk = ($code === null || is_int($code) === true || is_string($code) === true);
        $isOk ?: Exceptions::throwInvalidArgument('code', $code);
    }

    /**
     * @param string $errorName
     * @param string $message
     * @param array $source
     * @param string $title
     * @return object
     */
    public function getErrorObjectByErrorName($errorName, $message, array $source, $title = null)
    {
        /** @var JSONApiError $newObject */
        $newObject = new $this;

        $title = ($title) ? $title : $message;
        $code = (array_key_exists($errorName, self::ERRORS)) ? self::ERRORS[$errorName] : self::ERRORS['ok'];

        $newObject->setCode($code);
        $newObject->setTitle($title);
        $newObject->setDetail($message);
        $newObject->setSource($source);

        return $newObject;
    }
}
