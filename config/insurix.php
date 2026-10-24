<?php

declare(strict_types=1);

return [

    /*
    | How much file storage each workspace gets for documents, in bytes. Plans can raise it later.
    */

    'storage_limit_bytes' => (int) env('INSURIX_STORAGE_LIMIT_BYTES', 1024 * 1024 * 1024),

];
