<?php

declare(strict_types=1);

namespace JustCarmen\Webtrees\Module\SimpleFooter;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\View;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\FlashMessages;
use Psr\Http\Message\ResponseInterface;
use Fisharebest\Localization\Translation;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleFooterTrait;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleFooterInterface;
use Fisharebest\Webtrees\Http\RequestHandlers\ModulesFootersAction;

/**
 * Anonymous class - provide a custom footer (link) and page
 */
return new class extends AbstractModule implements ModuleCustomInterface, ModuleFooterInterface, ModuleConfigInterface, RequestHandlerInterface
{
    use ModuleCustomTrait;
    use ModuleFooterTrait;
    use ModuleConfigTrait;

    protected const ROUTE_URL   = '/tree/{tree}/jc-simple-footer-1/{footer}';

    // Module constants
    public const CUSTOM_AUTHOR = 'JustCarmen';
    public const CUSTOM_VERSION = '1.2';
    public const GITHUB_REPO = 'webtrees-simple-footer';
    public const AUTHOR_WEBSITE = 'https://justcarmen.nl';
    public const CUSTOM_SUPPORT_URL = self::AUTHOR_WEBSITE . '/modules-webtrees-2/simple-footer/';

    /**
     * How should this module be identified in the control panel, etc.?
     *
     * @return string
     */
    public function title(): string
    {
        /* I18N: Name of a module */
        if ($this->getPreference('footer-text')){
            return I18N::translate('Simple footer module') . ' - ' . $this->getPreference('footer-text');
        } else {
            return I18N::translate('Simple footer module');
        }
    }

    /**
     * A sentence describing what this module does.
     *
     * @return string
     */
    public function description(): string
    {
        /* I18N: Description of the “Simple Footer” module */
        return I18N::translate('Add an extra footer and page.');
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleAuthorName()
     */
    public function customModuleAuthorName(): string
    {
        return self::CUSTOM_AUTHOR;
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleVersion()
     */
    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    /**
     * A URL that will provide the latest stable version of this module.
     *
     * @return string
     */
    public function customModuleLatestVersionUrl(): string
    {
        return 'https://raw.githubusercontent.com/' . self::CUSTOM_AUTHOR . '/' . self::GITHUB_REPO . '/main/latest-version.txt';
    }

    /**
     * {@inheritDoc}
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleSupportUrl()
     */
    public function customModuleSupportUrl(): string
    {
        return self::CUSTOM_SUPPORT_URL;
    }


    /**
     * Bootstrap the module
     */
    public function boot(): void
    {
        Registry::routeFactory()->routeMap()
            ->get(static::class, static::ROUTE_URL, $this);

        // Register a namespace for our views.
        View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
    }

    /**
     * Where does this module store its resources
     *
     * @return string
     */
    public function resourcesFolder(): string
    {
        return __DIR__ . '/resources/';
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function getAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        return $this->viewResponse($this->name() . '::edit', [
            'title'             => $this->title(),
            'footer_text'  => $this->getPreference('footer-text'),
            'page_title'        => $this->getPreference('page-title'),
            'page_body'         => $this->getPreference('page-body'),
        ]);
    }

    /**
     * Save the user preference.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function postAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = (array) $request->getParsedBody();

        $this->setPreference('footer-text', $params['footer-text']);
        $this->setPreference('page-title', $params['page-title']);
        $this->setPreference('page-body', $params['page-body']);

        $message = I18N::translate('The preferences for the module “%s” have been updated.', $this->title());
        FlashMessages::addMessage($message, 'success');

        return redirect(route(ModulesFootersAction::class));
    }

    /**
     * A footer, to be added at the bottom of every page.
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function getFooter(ServerRequestInterface $request): string
    {
        $tree = $request->getAttribute('tree');

        $url = route(self::class, [
            'tree'      => $tree ? $tree->name() : null,
            'footer'    => $this->getSlug($this->getPreference('footer-text'))
        ]);

        return view($this->name() . '::footer', [
            'link' => (bool)$this->getPreference('page-title') !== "" && $this->getPreference('page-body') !== "",
            'url' => $url,
            'footer_text' => $this->getPreference('footer-text'),
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $page_title = $this->getPreference('page-title');
        $page_body  = $this->getPreference('page-body');

        return $this->viewResponse($this->name() . '::page', [
            'tree'          => $tree,
            'title'         => $this->title(),
            'module'        => $this->name(),
            'is_admin'      => Auth::isAdmin(),
            'page_title'    => $page_title,
            'page_body'     => $page_body
        ]);
    }

    /**
     * Get the url slug for this page
     */
    public function getSlug($string): String
    {
        return preg_replace('/\s+/', '-', strtolower(preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities($string))));
    }

    /**
     * Additional/updated translations.
     *
     * @param string $language
     *
     * @return array<string>
     */
    public function customTranslations(string $language): array
    {
        $file = $this->resourcesFolder() . 'lang/' . $language . '.php';

        return file_exists($file) ? (new Translation($file))->asArray() : [];
    }
};
