<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Restablecer contraseña — {{ $appBrand }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f1f5f9;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;border:1px solid #e2e8f0;">
                <tr>
                    <td align="center" bgcolor="#0f172a" style="background-color:#0f172a;padding:32px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto 16px;">
                            <tr>
                                <td align="center" bgcolor="#ffffff" style="background-color:#ffffff;border-radius:16px;padding:8px;line-height:0;">
                                    <img
                                        src="{{ asset('images/brand/mgf-icon-512.png') }}"
                                        alt="{{ $appBrand }}"
                                        width="56"
                                        height="56"
                                        style="display:block;width:56px;height:56px;border:0;border-radius:10px;"
                                    >
                                </td>
                            </tr>
                        </table>
                        <h1 style="margin:0;font-size:22px;color:#ffffff;font-weight:bold;">Restablecer contraseña</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;background-color:#ffffff;">
                        <p style="margin:0 0 16px;font-size:16px;color:#0f172a;">Hola <strong>{{ $userName }}</strong>,</p>
                        <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#475569;">
                            Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong style="color:#0f172a;">{{ $appBrand }}</strong>.
                            El enlace caduca en <strong style="color:#0f172a;">{{ $expireMinutes }} minutos</strong>.
                        </p>
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto 24px;">
                            <tr>
                                <td align="center" bgcolor="#ea580c" style="background-color:#ea580c;border-radius:10px;">
                                    <a href="{{ $url }}" target="_blank" style="display:inline-block;padding:15px 36px;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;">
                                        <span style="color:#ffffff;">Restablecer contraseña</span>
                                    </a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0;font-size:13px;line-height:1.6;word-break:break-all;color:#64748b;text-align:center;">
                            <a href="{{ $url }}" target="_blank" style="color:#ea580c;font-weight:bold;">{{ $url }}</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
