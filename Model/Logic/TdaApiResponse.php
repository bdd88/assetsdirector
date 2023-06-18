<?php
namespace Bdd88\AssetsDirector\Model\Logic;

/** JSON Decodes and stores the response from the TDA API. */
class TdaApiResponse
{
    public int $httpdCode;
    public array|object $response;

    public function __construct(int $httpdCode, string $response)
    {
        $this->httpdCode = $httpdCode;
        $this->response = json_decode($response);
    }
}
