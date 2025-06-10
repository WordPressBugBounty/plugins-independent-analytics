<div>
    <h1>Independent Analytics Debugging</h1>

    <section class="settings-container">
        <h2>IP Address Debugging</h2>
        <div class="ip-addresses">
            <p {{ $detected_ip ? "" : 'class=empty' }}><span>Detected IP:</span> <span>{{ $detected_ip }}</span></p>
            <p {{ $custom_ip_header ? "" : 'class=empty' }}><span>Custom IP header:</span> <span>{{ $custom_ip_header }}</span></p>
                @foreach($header_details as $detail)
                    <p {{ $detail[1] ? "" : 'class=empty' }}><span>{{ $detail[0] }}:</span> <span>{{ $detail[1] }}</span></p>
                @endforeach
        </div>
    </section>
</div>
