@php
    use App\Support\PdfBranding;

    $tone = $pdfBrandTone ?? PdfBranding::brandToneForBackground($brandBackground ?? null);
    $onDark = $tone === 'on-dark';
    $iconUri = $onDark
        ? ($appIconLightDataUri ?? $appIconDataUri ?? $appLogoDataUri)
        : ($appIconDataUri ?? $appLogoDataUri);
    $primaryTextColor = $onDark ? '#fffbeb' : '#1e3347';
    $secondaryTextColor = $onDark ? 'rgba(255,251,235,0.82)' : '#475569';
@endphp

@if(!empty($iconUri))
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 14px;">
        <tr>
            <td style="vertical-align: middle; padding-right: 12px;">
                <img
                    src="{{ $iconUri }}"
                    alt=""
                    width="44"
                    height="44"
                    style="display: block; width: 44px; height: 44px;"
                >
            </td>
            <td style="vertical-align: middle;">
                <div style="font-family: DejaVu Sans, sans-serif; font-size: 17px; font-weight: bold; color: {{ $primaryTextColor }}; letter-spacing: 0.04em; line-height: 1.1;">
                    {{ $appBrandPrimary ?? $appBrand }}
                </div>
                @if(filled($appBrandSecondary ?? null))
                    <div style="font-family: DejaVu Sans, sans-serif; font-size: 10.5px; font-weight: normal; color: {{ $secondaryTextColor }}; letter-spacing: 0.18em; margin-top: 3px; line-height: 1.1;">
                        {{ strtoupper($appBrandSecondary) }}
                    </div>
                @endif
            </td>
        </tr>
    </table>
@endif
