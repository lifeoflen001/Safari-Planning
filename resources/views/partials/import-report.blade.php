@php($importReport = session('import_report'))
@if(is_array($importReport))
    <div class="alert {{ ($importReport['failed'] ?? []) === [] ? 'alert-success' : 'alert-error' }} import-report" role="status">
        <strong>Import {{ ($importReport['failed'] ?? []) === [] ? 'complete' : 'not applied' }}</strong>
        <span>Total rows processed: {{ $importReport['total'] ?? 0 }}</span>
        <span>Imported: {{ $importReport['imported'] ?? 0 }}</span>
        <span>Skipped duplicates: {{ $importReport['duplicates'] ?? 0 }}</span>
        <span>Failed: {{ count($importReport['failed'] ?? []) }}</span>
        @if(($importReport['failed'] ?? []) !== [])
            <details>
                <summary>Show row errors</summary>
                <ul>
                    @foreach($importReport['failed'] as $failure)
                        <li>Row {{ $failure['line'] ?: '—' }}: {{ $failure['reason'] }}</li>
                    @endforeach
                </ul>
            </details>
        @endif
    </div>
@endif
