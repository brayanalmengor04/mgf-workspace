<?php

namespace App\Support;

final class CrmNavigation
{
    public const INICIO = 'Inicio';

    public const MODULO_PRESUPUESTO = 'Módulo Presupuesto';

    public const MODULO_AHORROS = 'Módulo Ahorros';

    public const MODULO_COTIZACIONES = 'Módulo Cotizaciones';

    public const CONFIGURACION = 'Configuración';

    /** @deprecated Use MODULO_PRESUPUESTO */
    public const GESTION_PRESUPUESTAL = self::MODULO_PRESUPUESTO;

    /** @deprecated Use MODULO_COTIZACIONES */
    public const DOCUMENTOS_COMERCIALES = self::MODULO_COTIZACIONES;
}
