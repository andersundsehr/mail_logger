<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\ViewHelpers\Pagination;

use Override;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Route as SymfonyRoute;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class UriViewHelper extends AbstractTagBasedViewHelper
{
    public function __construct(private readonly UriBuilder $uriBuilder)
    {
        parent::__construct();
    }

    /**
     * Initialize arguments
     */
    #[Override]
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('name', 'string', 'identifier important if more widgets on same page', false, 'widget');
        $this->registerArgument('arguments', 'array', 'Arguments', false, []);
    }

    /**
     * Build an uri to current action with &tx_maillogger_iocenter[currentPage]=2
     *
     * @return string The rendered uri
     */
    #[Override]
    public function render(): string
    {
        $argumentPrefix = 'tx_maillogger_iocenter[' . $this->arguments['name'] . ']';

        $arguments = $this->hasArgument('arguments') ? $this->arguments['arguments'] : [];

        if ($this->hasArgument('action')) {
            $arguments['action'] = $this->arguments['action'];
        }

        if ($this->hasArgument('format') && $this->arguments['format'] !== '') {
            $arguments['format'] = $this->arguments['format'];
        }

        $request = $this->renderingContext->hasAttribute(ServerRequestInterface::class)
            ? $this->renderingContext->getAttribute(ServerRequestInterface::class)
            : $GLOBALS['TYPO3_REQUEST'];

        $route = $request->getAttribute('route');
        if (!$route instanceof Route && !$route instanceof SymfonyRoute) {
            throw new RouteNotFoundException('No route object was given inside the request object', 1691423325);
        }

        return $this->uriBuilder->buildUriFromRoute($route->getOption('_identifier'), [$argumentPrefix => $arguments])->__toString();
    }
}
