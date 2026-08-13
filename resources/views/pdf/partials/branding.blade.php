<style>
    .pdf-developer-footer {
        position: fixed;
        bottom: 16px;
        left: 0;
        right: 0;
        padding-top: 10px;
        border-top: 1px solid #e2e8f0;
        text-align: center;
        font-size: 8.5px;
        color: #64748b;
        line-height: 1.55;
        background: #fff;
    }
    .pdf-developer-footer table {
        margin: 0 auto;
    }
    .pdf-developer-footer img {
        display: block;
        width: 12px;
        height: 12px;
    }
    .pdf-developer-footer a {
        color: #475569;
        text-decoration: none;
    }
    .pdf-developer-footer .pdf-dev-name {
        font-weight: bold;
        color: #334155;
    }
    body {
        padding-bottom: 52px;
    }
</style>

<div class="pdf-developer-footer">
    <div style="margin-bottom: 4px;">
        {{ $pdfDocumentLabel ?? 'Documento' }} generado en
        <a href="{{ $appUrl }}">{{ $appUrlDisplay }}</a>
    </div>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="vertical-align: middle; padding-right: 5px;">
                <img src="{{ $githubIconDataUri }}" alt="GitHub">
            </td>
            <td style="vertical-align: middle;">
                Desarrollado por
                <span class="pdf-dev-name">{{ $developerName }}</span>
                ·
                <a href="{{ $githubUrl }}">github.com/brayanalmengor04</a>
            </td>
        </tr>
    </table>
</div>
