<?php

declare(strict_types=1);

return [
    /** أقصى عدد إعلانات مثبّتة في المؤسسة الواحدة */
    'max_pinned' => (int) env('CONTENT_MAX_PINNED', 3),

    /** أقصى طول لعنوان الإعلان */
    'title_max_length' => 180,
];
