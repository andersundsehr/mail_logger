<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\ViewHelpers\Pagination;

use Closure;
use Exception;
use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\PaginationInterface;
use TYPO3\CMS\Core\Pagination\PaginatorInterface;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class PaginateViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('objects', 'mixed', 'array or queryresult', true);
        $this->registerArgument('as', 'string', 'new variable name', true);
        $this->registerArgument('itemsPerPage', 'int', 'items per page', false, 10);
        $this->registerArgument('name', 'string', 'unique identification - will take "as" as fallback', false, '');
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    public static function renderStatic(
        array $arguments,
        Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        if ($arguments['objects'] === null) {
            return $renderChildrenClosure();
        }

        $paginator = self::getPaginator($arguments);
        $templateVariableContainer = $renderingContext->getVariableProvider();
        $templateVariableContainer->add($arguments['as'], [
            'pagination' => GeneralUtility::makeInstance(SimplePagination::class, $paginator),
            'paginator' => $paginator,
            'name' => self::getName($arguments),
        ]);
        $output = $renderChildrenClosure();
        $templateVariableContainer->remove($arguments['as']);
        return $output;
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    protected static function getPaginator(array $arguments): PaginatorInterface
    {
        if (is_array($arguments['objects'])) {
            $paginatorClass = ArrayPaginator::class;
        } elseif (is_a($arguments['objects'], QueryResultInterface::class)) {
            $paginatorClass = QueryResultPaginator::class;
        } else {
            throw new Exception('Given object is not supported for pagination', 1634132847);
        }

        return GeneralUtility::makeInstance(
            $paginatorClass,
            $arguments['objects'],
            self::getPageNumber($arguments),
            $arguments['itemsPerPage']
        );
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    protected static function getPageNumber(array $arguments): int
    {
        $variables = $GLOBALS['TYPO3_REQUEST']->getParsedBody()['tx_maillogger_iocenter'] ?? $GLOBALS['TYPO3_REQUEST']->getQueryParams()['tx_maillogger_iocenter'] ?? null;
        return (int)($variables[self::getName($arguments)]['currentPage'] ?? 1);
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    protected static function getName(array $arguments): string
    {
        return $arguments['name'] ?: $arguments['as'];
    }
}
