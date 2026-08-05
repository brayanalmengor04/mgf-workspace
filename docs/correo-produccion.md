# Correo en producción — referencia rápida

## URLs del proyecto

| Entorno | URL |
|---------|-----|
| **Producción (Railway)** | https://mgfapp.up.railway.app |
| **Panel admin (prod)** | https://mgfapp.up.railway.app/admin |
| **Local (Docker)** | http://localhost:8000 |
| **Panel admin (local)** | http://localhost:8000/admin |

---

## Por qué no Gmail SMTP en Railway

Railway **bloquea SMTP saliente** (puertos 465 y 587). Por eso en producción verás:

```
Connection timed out → smtp.gmail.com:465
```

Tu portfolio en **Netlify** sí puede usar Nodemailer/Gmail; **Railway no**.

**Solución en producción:** API HTTPS (puerto 443), no SMTP.

---

## Stack de correo implementado

| Tecnología | Uso | Paquete Composer |
|------------|-----|------------------|
| **Brevo** | Producción en Railway (recomendado, gratis) | `symfony/brevo-mailer` |
| **Gmail SMTP** | Desarrollo local | Laravel `smtp` nativo |
| **Resend** | Alternativa si tienes dominio propio | `resend/resend-php` |

Registro del transporte Brevo: `app/Providers/AppServiceProvider.php` (`Mail::extend('brevo', ...)`).

Plantillas de correo: `resources/views/mail/` (logo por URL pública `asset()`, no `$message->embed()`).

---

## Producción — Brevo + Gmail (plan gratis)

### 1. Cuenta Brevo

1. https://www.brevo.com → cuenta gratis (~**300 correos/día**)
2. **Remitentes, dominios e IPs** → **Remitentes** → Agregar `brayanalmengorz@gmail.com` → **Verificado**
3. **SMTP y API** → **Claves API** → Generar → copiar `xkeysib-...`

### 2. Variables en Railway

Servicio **mgf-workspace** → **Variables**:

```
MAIL_MAILER=brevo
BREVO_API_KEY=xkeysib-tu_clave
MAIL_FROM_ADDRESS=brayanalmengorz@gmail.com
MAIL_FROM_NAME=MGF Workspace
APP_URL=https://mgfapp.up.railway.app
QUEUE_CONNECTION=sync
```

**No uses en Railway:** `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, `MAIL_USERNAME`, `MAIL_PASSWORD` (SMTP bloqueado).

`MAIL_FROM_ADDRESS` debe coincidir **exactamente** con el remitente verificado en Brevo.

### 3. Límites Brevo (gratis)

| Límite | Valor |
|--------|--------|
| Correos por día | 300 |
| Renovación | Cada día; lo no usado no se acumula |
| Enviar a cualquier correo | Sí (con Gmail verificado) |

Más info: [Límites plan Free de Brevo](https://help.brevo.com/hc/en-us/articles/208580669-FAQs-What-are-the-limits-of-the-Free-plan)

---

## Local — Gmail SMTP

En tu `.env` local (sin cambios respecto al portfolio):

```
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=tu_correo@gmail.com
MAIL_PASSWORD=contraseña_aplicacion_google
MAIL_FROM_ADDRESS=tu_correo@gmail.com
MAIL_FROM_NAME=MGF Workspace
```

---

## Alternativa — Resend (con dominio propio)

Solo si compras y verificas un dominio (ej. `mgfworkspace.com`). `onboarding@resend.dev` **no** sirve para invitar a otros usuarios.

```
MAIL_MAILER=resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=invitaciones@tudominio.com
```

Guía Railway: [Send Emails Without SMTP — Resend](https://railway.com/deploy/send-emails-on-railway-without-smtp-resend-api-starter--resend-email-railway)

---

## Comandos de diagnóstico

| Comando | Qué hace |
|---------|----------|
| `just prod-mail-diagnose` | Muestra config y envía correo de prueba (vars de Railway en Docker local) |
| `just prod-mail-diagnose --to=otro@gmail.com` | Prueba a un destinatario concreto |
| `just prod logs` | Logs de Railway (buscar `mail-probe:`) |
| `php artisan mgf:mail-probe` | Comprueba config sin enviar |
| `php artisan mgf:mail-diagnose` | Diagnóstico completo + envío opcional |

Log esperado al arrancar en Railway:

```
mail-probe: Brevo API configurada (HTTPS — funciona en Railway).
```

---

## Problemas frecuentes

| Síntoma | Causa | Solución |
|---------|--------|----------|
| `Connection timed out` SMTP | Railway bloquea SMTP | Usar `MAIL_MAILER=brevo` |
| `Unsupported mail transport [brevo]` | Transporte no registrado | Deploy con `AppServiceProvider` actualizado |
| Correo no llega a otros | `onboarding@resend.dev` o remitente no verificado | Brevo + Gmail verificado |
| Logo roto en el correo | `$message->embed()` no funciona con Brevo API | Logo vía `asset('images/brand/mgf-icon-512.png')` |
| Mensaje "verifica SMTP" en la app | Texto genérico antiguo | Revisar logs; en prod es Brevo, no SMTP |

---

## Deploy

Push a `main` → GitHub Actions despliega en Railway.

```bash
git push origin main
```

Documentación relacionada: [railway-deploy.md](./railway-deploy.md), [railway-mail-env.txt](./railway-mail-env.txt)
