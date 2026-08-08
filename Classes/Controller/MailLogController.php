<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Controller;

use Pluswerk\MailLogger\Domain\Model\MailLog;
use Pluswerk\MailLogger\Domain\Repository\MailLogRepository;
use Pluswerk\MailLogger\Dto\MailLogFilter;
use Pluswerk\MailLogger\Dto\MailStatus;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
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
    public function dashboardAction(
        string $mailTo = '',
        string $mailFrom = '',
        string $subject = '',
        string $status = '',
        int $currentPage = 1,
    ): ResponseInterface {
        $filter = new MailLogFilter(
            $mailTo,
            $mailFrom,
            $subject,
            $this->resolveStatus($status),
        );

        if ($this->request->getMethod() === 'POST') {
            return $this->redirect('dashboard', arguments: $filter->toArray());
        }

        $paginator = new QueryResultPaginator(
            $this->mailLogRepository->findByFilter($filter),
            max(1, $currentPage),
            10,
        );
        $pagination = new SlidingWindowPagination($paginator, 9);

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        // Add required js files.
        $this->pageRenderer->loadJavaScriptModule('@pluswerk/mail-logger/Main.js');

        $moduleTemplate->assignMultiple([
            'filter' => $filter,
            'pagination' => $pagination,
            'paginator' => $paginator,
        ]);

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

    private function resolveStatus(string $status): ?MailStatus
    {
        if ($status === '' || !ctype_digit($status)) {
            return null;
        }

        return MailStatus::tryFrom((int)$status);
    }
}
