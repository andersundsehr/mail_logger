<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use Pluswerk\MailLogger\Domain\Model\MailLog;
use Pluswerk\MailLogger\Domain\Repository\MailLogRepository;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;

class MailLogController extends ActionController
{
    public function __construct(
        private readonly MailLogRepository $mailLogRepository,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly PageRenderer $pageRenderer,
    ) {
    }

    /**
     * action dashboard
     */
    public function dashboardAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        // Add required js files.
        $this->pageRenderer->loadJavaScriptModule('@pluswerk/mail-logger/Main.js');

        $moduleTemplate->assign('mailLogs', $this->mailLogRepository->findAll());

        return $moduleTemplate->renderResponse('MailLog/Dashboard');
    }

    /**
     * action show
     */
    public function showAction(MailLog $mailLog): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assign('mailLog', $mailLog);

        return $moduleTemplate->renderResponse('MailLog/Show');
    }
}
