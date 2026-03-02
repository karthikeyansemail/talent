<div style="display:flex;gap:16px;margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid var(--gray-100);last-child:border-bottom:none">
    <div style="flex-shrink:0;width:28px;height:28px;background:var(--primary);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;margin-top:2px">
        {{ $n }}
    </div>
    <div style="flex:1">
        <div style="font-size:14px;font-weight:600;color:var(--gray-800);margin-bottom:8px">{{ $title }}</div>
        <div style="font-size:13px;color:var(--gray-600);line-height:1.6">
            {!! $content !!}
        </div>
    </div>
</div>
