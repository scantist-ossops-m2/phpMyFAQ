<?php

/**
 * The Setup Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-06-01
 */

namespace phpMyFAQ\Controller\Frontend;

use Elastic\Elasticsearch\Exception\AuthenticationException;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Setup\Installer;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\System;
use phpMyFAQ\Template\TemplateException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SetupController extends AbstractController
{
    /**
     * @throws TemplateException
     * @throws \Exception
     */
    #[Route('/setup', name: 'public.setup.update')]
    public function index(): Response
    {
        $system = new System();
        $installer = new Installer($system);

        $checkBasicError = '';

        try {
            $installer->checkBasicStuff();
        } catch (Exception $e) {
            $checkBasicError = $e->getMessage();
        }

        return $this->render(
            'setup/index.twig',
            [
                'newVersion' => System::getVersion(),
                'setupType' => 'Setup',
                'currentYear' => date('Y'),
                'currentLanguage' => 'en',
                'documentationUrl' => System::getDocumentationUrl(),
                'checkBasicError' => $checkBasicError,
                'nonCriticalSettings' => $installer->checkNoncriticalSettings(),
                'supportedDatabases' => $system->getSupportedSafeDatabases(),
                'currentPath' => dirname(__DIR__, 4),
                'isLdapEnabled' => $installer->hasLdapSupport(),
                'isElasticsearchEnabled' => $installer->hasElasticsearchSupport(),
                'supportedTranslations' => LanguageCodes::getAllSupported(),
            ]
        );
    }

    /**
     * @throws TemplateException
     * @throws \Exception
     */
    #[Route('/setup/install', name: 'public.setup.install')]
    public function install(): Response
    {
        $system = new System();
        $installer = new Installer($system);

        $checkBasicError = $installationError = '';

        try {
            $installer->checkBasicStuff();
        } catch (Exception $e) {
            $checkBasicError = $e->getMessage();
        }

        try {
            $installer->startInstall();
        } catch (Exception | AuthenticationException $e) {
            $installationError = $e->getMessage();
        }

        return $this->render(
            'setup/install.twig',
            [
                'newVersion' => System::getVersion(),
                'setupType' => 'Setup',
                'currentYear' => date('Y'),
                'documentationUrl' => System::getDocumentationUrl(),
                'checkBasicError' => $checkBasicError,
                'installationError' => $installationError,
            ]
        );
    }

    /**
     * @throws TemplateException
     */
    #[Route('/setup/update', name: 'public.setup.update')]
    public function update(Request $request): Response
    {
        $currentStep = Filter::filterVar($request->get('step'), FILTER_VALIDATE_INT);

        $update = new Update(new System(), $this->configuration);

        return $this->render(
            'setup/update.twig',
            [
                'currentStep' => $currentStep ?? 1,
                'installedVersion' => System::getVersion(),
                'newVersion' => System::getVersion(),
                'currentYear' => date('Y'),
                'documentationUrl' => System::getDocumentationUrl(),
                'configTableNotAvailable' => $update->isConfigTableNotAvailable($this->configuration->getDb()),
            ]
        );
    }
}
