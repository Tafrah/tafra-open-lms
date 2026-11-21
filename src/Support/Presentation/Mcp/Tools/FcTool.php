<?php

declare(strict_types=1);

namespace Src\Support\Presentation\Mcp\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Src\Contexts\Identity\Domain\Models\User;
use Src\Support\Domain\Exceptions\DomainException;
use Src\Support\Presentation\Mcp\McpCallLogger;
use Src\Support\Presentation\Mcp\McpSession;
use Src\Support\Presentation\Mcp\ToolOutput;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * الأساس لكل أدوات القالب — الحاجات اللي مينفعش أداة تنساها في مكان واحد:
 *
 *  · الجلسة لازم تكون من ربط حقيقي (`McpSession`) — مش من مدخل
 *  · أداة الكتابة بتترفض على ربط «قراءة» حتى لو حد ناداها بالاسم
 *  · حد معدّل منفصل للكتابة
 *  · كل نداء في سجل النشاط (`mcp`) بمدخلات منقّاة — نجح أو اترفض أو فشل
 *  · الرفض برسالة الـ Policy المترجمة — مش exception ولا stack trace
 *  · الرد محدود الحجم وبيانات في حقول (docs/23 بند ٣ و٥)
 */
abstract class FcTool extends Tool
{
    /**
     * التنفيذ الفعلي — Application Action واحد. `$actor` من الجلسة.
     *
     * @return array<string, mixed>
     */
    abstract protected function run(Request $request, User $actor): array;

    /** أداة بتكتب؟ (إنشاء، تعديل، حذف، إجراء) */
    public function writes(): bool
    {
        return false;
    }

    public function destructive(): bool
    {
        return false;
    }

    public function idempotent(): bool
    {
        return ! $this->writes();
    }

    /**
     * الـ annotations صريحة على **كل** أداة — العميل بيعتمد عليها عشان يطلب
     * تأكيد بشري قبل الكتابة. الأدوات المتولّدة مالهاش attributes على الكلاس،
     * فبنرجّعها هنا بدل `#[IsReadOnly]`.
     *
     * @return array<string, bool>
     */
    public function annotations(): array
    {
        return [
            'readOnlyHint' => ! $this->writes(),
            'destructiveHint' => $this->writes() && $this->destructive(),
            'idempotentHint' => $this->idempotent(),
            'openWorldHint' => false,
        ];
    }

    final public function handle(Request $request): Response|ResponseFactory
    {
        $session = app(McpSession::class);
        $actor = $session->user();
        $logger = app(McpCallLogger::class);
        $arguments = $request->all();

        if (! $session->isActive() || ! $actor instanceof User) {
            return Response::error(__('mcp.errors.unauthenticated'));
        }

        if ($this->writes() && ! $session->canWrite()) {
            $logger->log($this->name(), $arguments, McpCallLogger::DENIED, 'read_only');

            return Response::error(__('mcp.errors.read_only'));
        }

        if ($this->writes() && ! $this->withinWriteLimit($session)) {
            $logger->log($this->name(), $arguments, McpCallLogger::DENIED, 'rate_limited');

            return Response::error(__('mcp.errors.rate_limited'));
        }

        try {
            $payload = $this->run($request, $actor);
        } catch (AuthorizationException $exception) {
            $notFound = $exception->status() === 404;
            $logger->log($this->name(), $arguments, McpCallLogger::DENIED, $notFound ? 'not_found' : 'forbidden');

            return Response::error($notFound
                ? __('mcp.errors.not_found')
                : ($exception->response()->message() ?: __('mcp.errors.forbidden')));
        } catch (ModelNotFoundException) {
            $logger->log($this->name(), $arguments, McpCallLogger::DENIED, 'not_found');

            return Response::error(__('mcp.errors.not_found'));
        } catch (ValidationException $exception) {
            $logger->log($this->name(), $arguments, McpCallLogger::FAILED, 'invalid');

            return Response::error(__('mcp.errors.invalid', [
                'errors' => collect($exception->errors())->flatten()->implode(' '),
            ]));
        } catch (DomainException $exception) {
            $logger->log($this->name(), $arguments, McpCallLogger::FAILED, 'domain');

            return Response::error($exception->getMessage());
        } catch (HttpExceptionInterface $exception) {
            $logger->log($this->name(), $arguments, McpCallLogger::DENIED, 'http_'.$exception->getStatusCode());

            return Response::error($exception->getStatusCode() === 404 ? __('mcp.errors.not_found') : __('mcp.errors.forbidden'));
        } catch (Throwable $exception) {
            report($exception);
            $logger->log($this->name(), $arguments, McpCallLogger::FAILED, 'error');

            return Response::error(__('mcp.errors.failed'));
        }

        $logger->log($this->name(), $arguments, McpCallLogger::SUCCEEDED);

        return Response::structured(ToolOutput::fit($payload));
    }

    private function withinWriteLimit(McpSession $session): bool
    {
        $key = $session->rateLimitKey().':writes';
        $max = (int) config('ai.rate_limit.writes_per_minute', 10);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            return false;
        }

        RateLimiter::hit($key, 60);

        return true;
    }
}
