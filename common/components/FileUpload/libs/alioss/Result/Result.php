<?php

namespace Alioss\Result;

use Alioss\Core\OssException;
use Alioss\Http\ResponseCore;


/**
 * Class Result, 操作结果类的基类，不同的请求在处理返回数据的时候有不同的逻辑，
 * 具体的解析逻辑推迟到子类实现
 *
 * @package OSS\Model
 */
abstract class Result
{
    /**
     * Result constructor.
     * @param $response ResponseCore
     * @throws OssException
     */
    public function __construct(ResponseCore $response)
    {
        $this->rawResponse = $response;
        $this->parseResponse();
    }

    /**
     * 获取requestId
     *
     * @return string
     */
    public function getRequestId(): string
    {
        if (isset($this->rawResponse->header['x-oss-request-id'])
        ) {
            return $this->rawResponse->header['x-oss-request-id'];
        } else {
            return '';
        }
    }

    /**
     * 得到返回数据，不同的请求返回数据格式不同
     *
     * $return mixed
     */
    public function getData()
    {
        return $this->parsedData;
    }

    /**
     * 由子类实现，不同的请求返回数据有不同的解析逻辑，由子类实现
     *
     * @return mixed
     */
    abstract protected function parseDataFromResponse(): mixed;

    /**
     * 操作是否成功
     *
     * @return mixed
     */
    public function isOK(): mixed
    {
        return $this->isOk;
    }

    /**
     * @throws OssException
     */
    public function parseResponse(): void
    {
        $this->isOk = $this->isResponseOk();
        if ($this->isOk) {
            $this->parsedData = $this->parseDataFromResponse();
        } else {
            $httpStatus = strval($this->rawResponse->status);
            $requestId = strval($this->getRequestId());
            $code = $this->retrieveErrorCode($this->rawResponse->body);
            $message = $this->retrieveErrorMessage($this->rawResponse->body);
            $body = $this->rawResponse->body;

            $details = array(
                'status' => $httpStatus,
                'request-id' => $requestId,
                'code' => $code,
                'message' => $message,
                'body' => $body
            );
            throw new OssException($details);
        }
    }

    /**
     * 尝试从body中获取错误Message
     *
     * @param $body
     * @return string
     */
    private function retrieveErrorMessage($body): string
    {
        if (empty($body) || !str_contains($body, '<?xml')) {
            return '';
        }
        $xml = simplexml_load_string($body);
        if (isset($xml->Message)) {
            return strval($xml->Message);
        }
        return '';
    }

    /**
     * 尝试从body中获取错误Code
     *
     * @param $body
     * @return string
     */
    private function retrieveErrorCode($body): string
    {
        if (empty($body) || !str_contains($body, '<?xml')) {
            return '';
        }
        $xml = simplexml_load_string($body);
        if (isset($xml->Code)) {
            return strval($xml->Code);
        }
        return '';
    }

    /**
     * 根据返回http状态码判断，[200-299]即认为是OK
     *
     * @return bool
     */
    protected function isResponseOk(): bool
    {
        $status = $this->rawResponse->status;
        if ((int)(intval($status) / 100) == 2) {
            return true;
        }
        return false;
    }

    /**
     * 返回原始的返回数据
     *
     * @return ResponseCore
     */
    public function getRawResponse(): ResponseCore
    {
        return $this->rawResponse;
    }

    /**
     * 标示请求是否成功
     */
    protected bool $isOk = false;
    /**
     * 由子类解析过的数据
     */
    protected array|null $parsedData = null;
    /**
     * 存放auth函数返回的原始Response
     *
     * @var ResponseCore
     */
    protected ResponseCore $rawResponse;
}