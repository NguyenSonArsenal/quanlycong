<h2>Danh sách file log</h2>
<ul>
    @foreach ($logFiles as $file)
        @php
            $parts = explode('.', $file);
            $filename = $parts[0];
            $ext = $parts[1] ?? '';
        @endphp
        <li>
            <a href="{{ route('fe.dk-log.show', ['filename' => $filename, 'ext' => $ext]) }}">
                {{ $file }}
            </a>
        </li>
    @endforeach
</ul>
