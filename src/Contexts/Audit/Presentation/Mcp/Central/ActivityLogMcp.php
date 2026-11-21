<?php

declare(strict_types=1);

namespace Src\Contexts\Audit\Presentation\Mcp\Central;

use Src\Contexts\Audit\Presentation\Mcp\ActivityLogMcp as TenantActivityLogMcp;

/**
 * سجل النشاط على اللوحة المركزية — نفس الإعلان، والاستعلام من نفس المورد
 * (`forTenant(null)` = كل المؤسسات لفريق التشغيل). قراءة بس زي المؤسسة.
 */
final class ActivityLogMcp extends TenantActivityLogMcp {}
