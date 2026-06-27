<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApplicationLogReader;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ApplicationLogController extends Controller
{
    public function __construct(
        private readonly ApplicationLogReader $logReader,
    ) {}

    public function index(Request $request): View
    {
        $lines = (int) $request->query('lines', ApplicationLogReader::DEFAULT_LINES);
        $search = trim((string) $request->query('q', ''));
        $level = strtolower(trim((string) $request->query('level', '')));
        $file = $request->query('file');

        $result = $this->logReader->read(
            is_string($file) ? $file : null,
            $lines,
            $search !== '' ? $search : null,
            $level !== '' ? $level : null,
        );

        return view('tenant.admin.application-log.index', [
            'logFiles' => $this->logReader->listBasenames(),
            'selectedFile' => $result['file'],
            'logLines' => $result['lines'],
            'matchedCount' => $result['matched_count'],
            'truncated' => $result['truncated'],
            'fileSize' => $result['file_size'],
            'fileModifiedAt' => $result['file_modified_at'],
            'lines' => $lines,
            'q' => $search,
            'level' => $level,
        ]);
    }
}
