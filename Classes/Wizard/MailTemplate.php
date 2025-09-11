<?php

declare(strict_types=1);

namespace Pluswerk\MailLogger\Wizard;

use Pluswerk\MailLogger\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\SingletonInterface;

class MailTemplate implements SingletonInterface
{
    /**
     * @param array{items: list<array{value: string, label: string}> } $config
     */
    public function getTypoScriptKeys(array &$config): void
    {
        $items = [['label' => '', 'value' => '']];
        $settings = ConfigurationUtility::getCurrentModuleConfiguration('settings');
        foreach ($settings['mailTemplates'] ?? [] as $key => $value) {
            $items[] = ['label' => (string)($value['label'] ?: $key), 'value' => (string)$key];
        }

        $config['items'] = array_merge($config['items'], $items);
    }

    /**
     * @param array{items: list<array{value: string, label: string}> } $config
     */
    public function getDkimKeys(array &$config): void
    {
        $items = [['label' => '', 'value' => '']];
        $settings = ConfigurationUtility::getCurrentModuleConfiguration('settings');
        foreach ($settings['dkim'] ?? [] as $key => $value) {
            $items[] = ['label' => (string)($value['domain'] ?: $key), 'value' => (string)$key];
        }

        $config['items'] = array_merge($config['items'], $items);
    }

    /**
     * @param array{items: list<array{value: string, label: string}> } $config
     */
    public function getTemplatePathKeys(array &$config): void
    {
        $items = [];
        $settings = ConfigurationUtility::getCurrentModuleConfiguration('settings');
        if (!empty($settings['templateOverrides'])) {
            foreach ($settings['templateOverrides'] as $key => $value) {
                $items[] = ['label' => (string)($value['title'] ?: $key), 'value' => (string)$key];
            }
        }

        $config['items'] = [...$config['items'], ...$items];
    }
}
