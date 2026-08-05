<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Bienvenido/a a {{ $appBrand }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f1f5f9;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background-color:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
                {{-- Header --}}
                <tr>
                    <td align="center" bgcolor="#0f172a" style="background-color:#0f172a;padding:36px 32px 32px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto 18px;">
                            <tr>
                                <td align="center" bgcolor="#ffffff" style="background-color:#ffffff;border-radius:18px;padding:10px;line-height:0;">
                                    <img
                                        src="{{ $message->embed(public_path('images/brand/mgf-icon-512.png')) }}"
                                        alt="{{ $appBrand }}"
                                        width="64"
                                        height="64"
                                        style="display:block;width:64px;height:64px;border:0;border-radius:12px;"
                                    >
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#fbbf24;font-weight:bold;">Invitación de acceso</p>
                        <h1 style="margin:0;font-size:24px;line-height:1.35;color:#ffffff;font-weight:bold;">Bienvenido/a a {{ $appBrand }}</h1>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="padding:32px;background-color:#ffffff;">
                        <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#0f172a;">Hola <strong>{{ $userName }}</strong>,</p>
                        <p style="margin:0 0 28px;font-size:15px;line-height:1.7;color:#475569;">
                            <strong style="color:#0f172a;">{{ $inviterName }}</strong> te ha invitado a unirte a
                            <strong style="color:#0f172a;">{{ $appBrand }}</strong> como
                            <strong style="color:#0f172a;">{{ $roleLabel }}</strong>.
                            Aquí tienes tus credenciales para ingresar al portal.
                        </p>

                        {{-- Credentials card --}}
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:28px;border:2px solid #0f172a;border-radius:12px;overflow:hidden;">
                            <tr>
                                <td bgcolor="#0f172a" style="background-color:#0f172a;padding:12px 20px;">
                                    <p style="margin:0;font-size:12px;letter-spacing:0.1em;text-transform:uppercase;color:#fbbf24;font-weight:bold;">Tus credenciales</p>
                                </td>
                            </tr>
                            <tr>
                                <td bgcolor="#f8fafc" style="background-color:#f8fafc;padding:20px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                        <tr>
                                            <td style="padding:0 0 14px;font-size:13px;color:#64748b;width:100px;vertical-align:top;font-weight:bold;">Correo</td>
                                            <td style="padding:0 0 14px;font-size:15px;font-weight:bold;color:#0f172a;word-break:break-all;">{{ $userEmail }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:0;font-size:13px;color:#64748b;vertical-align:middle;font-weight:bold;">Contraseña</td>
                                            <td style="padding:0;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td bgcolor="#0f172a" style="background-color:#0f172a;border-radius:8px;padding:12px 16px;text-align:center;">
                                                            <span style="font-size:20px;font-weight:bold;letter-spacing:0.08em;color:#ffffff;font-family:'Courier New',Courier,monospace;">{{ $plainPassword }}</span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        {{-- CTA button --}}
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto 24px;">
                            <tr>
                                <td align="center" bgcolor="#ea580c" style="background-color:#ea580c;border-radius:10px;">
                                    <a href="{{ $loginUrl }}" target="_blank" style="display:inline-block;padding:15px 36px;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:10px;mso-padding-alt:0;">
                                        <!--[if mso]><i style="letter-spacing:25px;mso-font-width:-100%;mso-text-raise:30pt">&nbsp;</i><![endif]-->
                                        <span style="color:#ffffff;">Acceder al portal</span>
                                        <!--[if mso]><i style="letter-spacing:25px;mso-font-width:-100%">&nbsp;</i><![endif]-->
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 8px;font-size:13px;line-height:1.7;color:#94a3b8;text-align:center;">
                            Si prefieres elegir tu propia contraseña,
                            <a href="{{ $url }}" target="_blank" style="color:#ea580c;text-decoration:underline;">puedes cambiarla aquí</a>
                            (válido {{ $expireMinutes }} min).
                        </p>
                        <p style="margin:0 0 6px;font-size:12px;line-height:1.5;color:#94a3b8;text-align:center;">
                            También puedes copiar y pegar este enlace en tu navegador:
                        </p>
                        <p style="margin:0 0 24px;font-size:11px;line-height:1.6;word-break:break-all;color:#64748b;text-align:center;font-family:'Courier New',Courier,monospace;">
                            {{ $url }}
                        </p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-top:1px solid #e2e8f0;padding-top:20px;">
                            <tr>
                                <td>
                                    <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#64748b;">
                                        Te recomendamos cambiar tu contraseña después del primer acceso. No compartas estas credenciales.
                                    </p>
                                    <p style="margin:0;font-size:12px;color:#94a3b8;">
                                        Si no esperabas esta invitación, ignora este correo.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td bgcolor="#f8fafc" style="background-color:#f8fafc;padding:18px 32px;text-align:center;border-top:1px solid #e2e8f0;">
                        <p style="margin:0;font-size:12px;color:#94a3b8;">&copy; {{ date('Y') }} {{ $appBrand }}. Todos los derechos reservados.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
