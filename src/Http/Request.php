<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\SymfonyBundle\Http;

use Railt\Http\RequestInterface;
use Railt\Http\Support\ConfigurableRequest;
use Railt\Http\Support\ConfigurableRequestInterface;
use Railt\Http\Support\InteractWithData;
use Railt\Http\Support\JsonContentTypeHelper;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * Class Request
 * @package Railt\SymfonyBundle\Http
 */
class Request implements RequestInterface, ConfigurableRequestInterface
{
    use InteractWithData;
    use ConfigurableRequest;
    use JsonContentTypeHelper;

    /**
     * SymfonyRequest constructor.
     * @param SymfonyRequest $request
     * @throws \LogicException
     */
    public function __construct(SymfonyRequest $request)
    {
        $this->data = $this->isJson($request->headers->get('CONTENT_TYPE') ?? 'text/html')
            ? $this->getJsonQueryAttributes($request)
            : $this->getAllQueryAttributes($request);
    }

    /**
     * @param SymfonyRequest $request
     * @return array
     * @throws \LogicException
     */
    private function getJsonQueryAttributes(SymfonyRequest $request): array
    {
        $input = $request->getContent();

        return (array)json_decode($input, true);
    }

    /**
     * @param SymfonyRequest $request
     * @return array
     */
    private function getAllQueryAttributes(SymfonyRequest $request): array
    {
        return array_merge($request->query->all(), $request->attributes->all(), $request->request->all());
    }
}
