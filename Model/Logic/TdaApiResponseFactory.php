<?php
namespace Bdd88\AssetsDirector\Model\Logic;

/** Creates TDA API response objects. */
class TdaApiResponseFactory
{
    public function create(int $httpCode, string $response): TdaApiResponse
    {
        return new TdaApiResponse($httpCode, $response);
    }
}
