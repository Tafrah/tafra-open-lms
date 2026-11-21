<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Presentation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Src\Contexts\Installer\Application\Actions\CheckRequirementsAction;
use Src\Contexts\Installer\Application\Actions\InstallApplicationAction;
use Src\Contexts\Installer\Application\Actions\TestDatabaseConnectionAction;
use Src\Contexts\Installer\Application\DataObjects\AcademyData;
use Src\Contexts\Installer\Application\DataObjects\DatabaseCredentials;
use Src\Contexts\Installer\Application\DataObjects\InstallationData;
use Src\Contexts\Installer\Domain\Exceptions\AlreadyInstalledException;
use Src\Contexts\Installer\Domain\Exceptions\DatabaseConnectionFailed;
use Src\Contexts\Installer\Domain\Exceptions\InstallationFailed;
use Src\Contexts\Installer\Presentation\Http\Requests\AcademyStepRequest;
use Src\Contexts\Installer\Presentation\Http\Requests\DatabaseStepRequest;

/**
 * خطوات المثبّت: المتطلبات ← القاعدة ← المنصة والمدير ← (التثبيت) ← النهاية.
 *
 * بيانات القاعدة بتتحفظ في الجلسة بين الخطوتين (الجلسة مشفّرة —
 * `SESSION_ENCRYPT=true`) وبتتمسح بعد التثبيت. صفحة النهاية بتترسم في
 * نفس طلب التثبيت: بعده `/install` بقى 404، والجلسة نفسها اتنقلت للقاعدة.
 *
 * كل التحويلات نسبية — راجع `RedirectToInstaller`.
 */
final class InstallerController
{
    private const SESSION = 'installer.database';

    public function __construct(private readonly CheckRequirementsAction $requirements) {}

    public function index(Request $request): RedirectResponse
    {
        return $this->go($request, 'installer.requirements');
    }

    public function requirements(): View
    {
        return view('installer::requirements', [
            'report' => $this->requirements->handle(),
            'step' => 1,
        ]);
    }

    public function database(Request $request): View|RedirectResponse
    {
        if (! $this->requirements->handle()->passes()) {
            return $this->go($request, 'installer.requirements');
        }

        $saved = (array) $request->session()->get(self::SESSION, []);
        unset($saved['db_password']);

        return view('installer::database', [
            'defaults' => [
                'db_driver' => 'mysql',
                'db_host' => 'localhost',
                'db_port' => config('installer.database.default_ports.mysql'),
                'db_database' => '',
                'db_username' => '',
                ...$saved,
            ],
            'drivers' => (array) config('installer.database.drivers'),
            'step' => 2,
        ]);
    }

    public function saveDatabase(DatabaseStepRequest $request, TestDatabaseConnectionAction $test): RedirectResponse
    {
        $credentials = DatabaseCredentials::fromArray($request->validated());

        try {
            $test->handle($credentials);
        } catch (DatabaseConnectionFailed $exception) {
            return back()
                ->withInput($request->except('db_password'))
                ->withErrors(['database' => $exception->getMessage()])
                ->with('installer.details', $exception->details);
        }

        $request->session()->put(self::SESSION, [...$credentials->withoutPassword(), 'db_password' => $credentials->password]);

        return $this->go($request, 'installer.academy');
    }

    public function academy(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(self::SESSION)) {
            return $this->go($request, 'installer.database');
        }

        return view('installer::academy', ['step' => 3]);
    }

    public function install(AcademyStepRequest $request, InstallApplicationAction $install): Response|RedirectResponse
    {
        $saved = $request->session()->get(self::SESSION);

        if (! is_array($saved)) {
            return $this->go($request, 'installer.database');
        }

        $data = new InstallationData(
            academy: AcademyData::fromArray($request->validated()),
            appUrl: $request->getSchemeAndHttpHost().$request->getBaseUrl(),
            database: DatabaseCredentials::fromArray($saved),
            writeEnvironment: true,
            secureCookies: $request->isSecure(),
        );

        try {
            $result = $install->handle($data);
        } catch (AlreadyInstalledException) {
            abort(404);
        } catch (DatabaseConnectionFailed $exception) {
            return $this->go($request, 'installer.database')
                ->withErrors(['database' => $exception->getMessage()])
                ->with('installer.details', $exception->details);
        } catch (InstallationFailed $exception) {
            return back()
                ->withInput($request->except('admin_password', 'admin_password_confirmation'))
                ->withErrors(['install' => $exception->getMessage()])
                ->with('installer.details', $exception->details);
        }

        $request->session()->forget('installer');

        return response()->view('installer::finished', [
            'result' => $result,
            'email' => $data->academy->adminEmail,
            'adminUrl' => $request->getBaseUrl().'/admin',
            'siteUrl' => $request->getBaseUrl().'/',
            'cron' => '* * * * * php '.base_path('artisan').' schedule:run >> /dev/null 2>&1',
            'step' => 4,
        ]);
    }

    public function switchLocale(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put('locale', $locale);

        return back(302, [], route('installer.requirements', [], false));
    }

    private function go(Request $request, string $route): RedirectResponse
    {
        // مش `redirect()->route()`: بيولّد رابط مطلق بـ https الإجباري
        $redirect = new RedirectResponse(route($route, [], false));
        $redirect->setRequest($request);
        $redirect->setSession(app('session.store'));

        return $redirect;
    }
}
