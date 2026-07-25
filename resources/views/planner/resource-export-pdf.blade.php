<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} | Safari Planner</title>
    <style>
        :root { color-scheme: light; font-family: Arial, sans-serif; color: #183128; }
        body { margin: 32px; font-size: 12px; }
        header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        h1 { margin: 0 0 6px; font-size: 22px; }
        p { margin: 0; color: #5f6f66; }
        button { border: 1px solid #b9cfc0; border-radius: 5px; padding: 8px 12px; color: #285d47; background: #fff; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 9px 8px; background: #eaf4ee; color: #285d47; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 9px 8px; border-bottom: 1px solid #d7e2da; }
        @media print { body { margin: 12mm; } .print-button { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <header>
        <div>
            <h1>{{ $title }}</h1>
            <p>Safari Planner · Generated {{ $generatedAt }}</p>
        </div>
        <button class="print-button" type="button" onclick="window.print()">Print / save as PDF</button>
    </header>
    <table>
        <thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) }}">No records found for the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
