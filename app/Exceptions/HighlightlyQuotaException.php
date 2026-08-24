<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Ném khi quota Highlightly cạn (hoặc sắp cạn tới ngưỡng an toàn).
 *
 * Phải là exception chứ không phải trả về null: backfill dài chạy hàng trăm
 * vòng lặp, nếu chỉ trả null thì mỗi ngày còn lại sẽ lặng lẽ ra 0 match và
 * lệnh vẫn báo thành công.
 */
class HighlightlyQuotaException extends RuntimeException
{
    public function __construct(
        public readonly ?int $remaining = null,
        public readonly ?int $limit = null,
    ) {
        parent::__construct(sprintf(
            'Quota Highlightly đã cạn (còn %s/%s request).',
            $remaining ?? '?',
            $limit ?? '?'
        ));
    }
}
